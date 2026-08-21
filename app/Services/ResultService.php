<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\MatchScoringEvent;
use App\Models\Organization;
use App\Models\Result;
use App\Models\SquadMember;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ResultService
{
    public function getAllByOrganization(Organization $organization, array $filters = []): LengthAwarePaginator
    {
        return $this->baseQuery($organization)
            ->with(['match.event.sport', 'match.pool', 'match.homeParticipant', 'match.awayParticipant', 'winner', 'scoringEvents.squadMember'])
            ->when($filters['event_id'] ?? null, fn ($q, $eventId) => $q->whereHas('match', fn ($m) => $m->where('event_id', $eventId)))
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function getAllByOrganizationForSelect(Organization $organization): Collection
    {
        return $this->baseQuery($organization)
            ->with(['match'])
            ->get(['id', 'match_id', 'score_home', 'score_away']);
    }

    public function getById(Organization $organization, string $id): Result
    {
        return $this->baseQuery($organization)
            ->with(['match.event.sport', 'match.homeParticipant', 'match.awayParticipant', 'winner', 'scoringEvents.squadMember'])
            ->where('results.id', $id)
            ->firstOrFail();
    }

    public function getByMatchId(Organization $organization, string $matchId): ?Result
    {
        return $this->baseQuery($organization)
            ->with(['winner'])
            ->where('match_id', $matchId)
            ->first();
    }

    public function create(Organization $organization, array $data): Result
    {
        $result = DB::transaction(function () use ($organization, $data) {
            $data['organization_id'] = $organization->id;
            $scoringEvents = $data['scoring_events'] ?? null;
            unset($data['scoring_events']);
            $result = Result::create($data);
            if ($scoringEvents !== null) {
                $this->syncScoringEvents($organization, $result, $scoringEvents);
            }
            $this->markMatchCompleted($result->match_id);
            $this->advanceKnockoutStage($result->match_id);
            Log::info('Result created', ['id' => $result->id, 'match_id' => $result->match_id, 'org_id' => $organization->id]);

            return $result;
        });

        app(PublicPortalService::class)->forgetForOrganization($organization->id);

        return $result;
    }

    public function update(Organization $organization, string $id, array $data): Result
    {
        $result = DB::transaction(function () use ($organization, $id, $data) {
            $result = $this->getById($organization, $id);
            $scoringEvents = $data['scoring_events'] ?? null;
            unset($data['scoring_events']);
            $result->update($data);
            if ($scoringEvents !== null) {
                $this->syncScoringEvents($organization, $result, $scoringEvents);
            }
            $this->markMatchCompleted($result->match_id);
            $this->advanceKnockoutStage($result->match_id);
            Log::info('Result updated', ['id' => $id, 'org_id' => $organization->id]);

            return $result->fresh();
        });

        app(PublicPortalService::class)->forgetForOrganization($organization->id);

        return $result;
    }

    /**
     * A recorded result marks the related match as completed so the
     * league table / rankings pick it up automatically.
     */
    protected function markMatchCompleted(string $matchId): void
    {
        $match = Fixture::query()->find($matchId);

        if ($match && $match->status !== 'completed') {
            $match->update(['status' => 'completed']);
        }
    }

    /**
     * Progress the tournament flow after a result is recorded:
     *  - A completed knockout match resolves Bronze/Final participants.
     *  - A completed league generates the knockout stage automatically.
     */
    protected function advanceKnockoutStage(string $matchId): void
    {
        $match = Fixture::query()->with('event.pools')->find($matchId);

        if (! $match || ! $match->event) {
            return;
        }

        $event = $match->event;
        $knockout = app(KnockoutStageService::class);

        if (in_array($match->stage, KnockoutStageService::KNOCKOUT_STAGES, true)) {
            $knockout->syncBracket($event);

            return;
        }

        if ($knockout->leagueComplete($event) && ! $knockout->hasKnockoutStage($event)) {
            try {
                $knockout->generate($event);
            } catch (\InvalidArgumentException) {
                // League size may not support a knockout stage (e.g. single group).
            }
        }
    }

    public function delete(Organization $organization, string $id): void
    {
        DB::transaction(function () use ($organization, $id) {
            $result = $this->getById($organization, $id);
            $result->delete();
            Log::info('Result deleted', ['id' => $id, 'org_id' => $organization->id]);
        });

        app(PublicPortalService::class)->forgetForOrganization($organization->id);
    }

    public function countByOrganization(Organization $organization): int
    {
        return $this->baseQuery($organization)->count();
    }

    protected function baseQuery(Organization $organization)
    {
        return Result::where('organization_id', $organization->id);
    }

    /** @param array<int, array<string, mixed>> $events */
    protected function syncScoringEvents(Organization $organization, Result $result, array $events): void
    {
        $match = Fixture::query()->with(['event.sport'])->findOrFail($result->match_id);
        if (($match->event?->sport?->scoring_mode ?? 'none') !== 'individual') {
            if ($events !== []) {
                throw ValidationException::withMessages(['scoring_events' => 'Scoring events are not enabled for this sport.']);
            }

            return;
        }

        $homeId = $match->home_participant_id;
        $awayId = $match->away_participant_id;
        $allowedParticipantIds = collect([$homeId, $awayId])->filter()->values();
        $memberIds = collect($events)->pluck('squad_member_id')->filter()->values();
        $members = SquadMember::query()
            ->whereIn('id', $memberIds)
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->whereIn('role', SquadMember::ATHLETE_ROLES)
            ->whereHas('eventParticipant', fn ($query) => $query
                ->where('event_id', $match->event_id)
                ->where('status', 'confirmed')
                ->whereIn('participant_id', $allowedParticipantIds))
            ->with('eventParticipant:id,participant_id')
            ->get()
            ->keyBy('id');

        $totals = [$homeId => 0, $awayId => 0];
        $rows = [];
        foreach ($events as $event) {
            $member = $members->get($event['squad_member_id'] ?? null);
            $participantId = $event['participant_id'] ?? null;
            if (! $member || ! $participantId || $member->eventParticipant?->participant_id !== $participantId) {
                throw ValidationException::withMessages(['scoring_events' => 'Every scorer must be an active athlete from the confirmed match roster.']);
            }

            $points = 1;
            $totals[$participantId] = ($totals[$participantId] ?? 0) + $points;
            $rows[] = [
                'organization_id' => $organization->id,
                'result_id' => $result->id,
                'match_id' => $match->id,
                'participant_id' => $participantId,
                'squad_member_id' => $member->id,
                'event_type' => $event['event_type'] ?? 'goal',
                'period' => $event['period'] ?? null,
                'minute' => $event['minute'] ?? null,
                'second' => $event['second'] ?? null,
                'points' => $points,
                'notes' => $event['notes'] ?? null,
            ];
        }

        $expected = [$homeId => (int) ($result->score_home ?? 0), $awayId => (int) ($result->score_away ?? 0)];
        foreach ($expected as $participantId => $score) {
            if (($totals[$participantId] ?? 0) !== $score) {
                throw ValidationException::withMessages(['scoring_events' => 'Scorer total must match both team scores.']);
            }
        }

        $result->scoringEvents()->delete();
        foreach ($rows as $row) {
            MatchScoringEvent::create($row);
        }
    }
}
