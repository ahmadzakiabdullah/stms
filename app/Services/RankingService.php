<?php

namespace App\Services;

use App\Models\Result;
use App\Models\Session;
use App\Models\Tournament;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RankingService
{
    public function __construct(
        private readonly RankingStrategyRegistry $strategies,
    ) {}

    public function calculateForSession(Session $session): Collection
    {
        $strategy = $session->ranking_strategy ?: config('ranking.default');
        $tournaments = $session->tournaments()
            ->where('organization_id', $session->organization_id)
            ->with('events')
            ->get();
        $results = $this->resultsForTournaments($session->organization_id, $tournaments->pluck('id'));
        $rankings = $this->rank($strategy, $this->aggregateStats($results), $session->ranking_rules, $tournaments);

        Log::info('Rankings calculated for session', [
            'session_id' => $session->id,
            'organization_id' => $session->organization_id,
            'strategy' => $strategy,
            'participant_count' => $rankings->count(),
        ]);

        return $rankings;
    }

    public function calculateMedalTallyForSession(Session $session): Collection
    {
        $tournaments = $session->tournaments()
            ->where('organization_id', $session->organization_id)
            ->with('events')
            ->get();

        return $this->rank(
            'medal_tally',
            $this->aggregateStats($this->resultsForTournaments($session->organization_id, $tournaments->pluck('id'))),
            $session->ranking_rules,
            $tournaments,
        );
    }

    public function calculateForTournament(Tournament $tournament): Collection
    {
        $strategy = $tournament->ranking_strategy ?: config('ranking.default');
        $tournaments = collect([$tournament->load('events')]);
        $results = $this->resultsForTournaments($tournament->organization_id, collect([$tournament->id]));
        $rankings = $this->rank($strategy, $this->aggregateStats($results), $tournament->ranking_rules, $tournaments);

        Log::info('Rankings calculated for tournament', [
            'tournament_id' => $tournament->id,
            'organization_id' => $tournament->organization_id,
            'strategy' => $strategy,
            'participant_count' => $rankings->count(),
        ]);

        return $rankings;
    }

    public function calculateForEvent(string $eventId): Collection
    {
        $results = Result::query()
            ->whereHas('match', fn ($query) => $query->where('event_id', $eventId))
            ->with(['match.homeParticipant', 'match.awayParticipant', 'winner'])
            ->get();
        $rankings = $this->rank('points', $this->aggregateStats($results));

        Log::info('Rankings calculated for event', [
            'event_id' => $eventId,
            'participant_count' => $rankings->count(),
        ]);

        return $rankings;
    }

    public function getAvailableStrategies(): array
    {
        return $this->strategies->labels();
    }

    private function resultsForTournaments(string $organizationId, Collection $tournamentIds): Collection
    {
        return Result::query()
            ->where('organization_id', $organizationId)
            ->whereHas('match.event', fn ($query) => $query
                ->where('organization_id', $organizationId)
                ->whereIn('tournament_id', $tournamentIds))
            ->with(['match.homeParticipant', 'match.awayParticipant', 'winner'])
            ->get();
    }

    private function rank(
        string $strategy,
        Collection $stats,
        ?array $storedRules = null,
        ?Collection $tournaments = null,
    ): Collection {
        $overrides = $storedRules[$strategy] ?? [];
        $rules = $this->strategies->rules($strategy, is_array($overrides) ? $overrides : []);

        return $this->strategies->get($strategy)->rank($stats, $rules, $tournaments);
    }

    private function aggregateStats(Collection $results): Collection
    {
        $stats = collect();

        foreach ($results as $result) {
            $homeId = $result->match?->home_participant_id;
            $awayId = $result->match?->away_participant_id;
            $winnerId = $result->winner_participant_id;

            if (! $homeId || ! $awayId) {
                continue;
            }

            foreach ([$homeId, $awayId] as $participantId) {
                if (! $stats->has($participantId)) {
                    $participant = $participantId === $homeId
                        ? $result->match->homeParticipant
                        : $result->match->awayParticipant;
                    $stats->put($participantId, [
                        'participant_id' => $participantId,
                        'participant_name' => $participant?->name ?? 'Unknown',
                        'participant_type' => $participant?->participant_type ?? 'individual',
                        'team_name' => $participant?->team_name,
                        'logo_url' => $participant?->logo_url,
                        'inverse_logo_url' => $participant?->inverse_logo_url,
                        'matches_played' => 0,
                        'wins' => 0,
                        'draws' => 0,
                        'losses' => 0,
                        'score_for' => 0,
                        'score_against' => 0,
                        'gold' => 0,
                        'silver' => 0,
                        'bronze' => 0,
                    ]);
                }

                $row = $stats->get($participantId);
                $row['matches_played']++;
                $isHome = $participantId === $homeId;
                $row['score_for'] += $isHome ? ($result->score_home ?? 0) : ($result->score_away ?? 0);
                $row['score_against'] += $isHome ? ($result->score_away ?? 0) : ($result->score_home ?? 0);

                if ($result->score_home === $result->score_away && $result->score_home !== null) {
                    $row['draws']++;
                } elseif ($winnerId === $participantId) {
                    $row['wins']++;
                } else {
                    $row['losses']++;
                }

                $stats->put($participantId, $row);
            }
        }

        return $stats;
    }
}
