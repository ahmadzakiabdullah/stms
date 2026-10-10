<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataQualityService
{
    public function summary(Organization $organization): array
    {
        $checks = [
            $this->duplicateParticipants($organization),
            $this->missingParentRelations($organization),
            $this->orphanResults($organization),
            $this->invalidTimelines($organization),
        ];

        return [
            'status' => collect($checks)->every(fn (array $check) => $check['count'] === 0) ? 'ok' : 'attention',
            'total_issues' => collect($checks)->sum('count'),
            'checks' => $checks,
        ];
    }

    private function duplicateParticipants(Organization $organization): array
    {
        $count = DB::table('participants')
            ->selectRaw('LOWER(name) as normalized_name, session_id, COUNT(*) as duplicates')
            ->where('organization_id', $organization->id)
            ->whereNull('deleted_at')
            ->groupByRaw('LOWER(name), session_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->sum(fn ($row) => max(0, ((int) $row->duplicates) - 1));

        return [
            'key' => 'duplicate_participants',
            'label' => 'Duplicate participants',
            'count' => (int) $count,
            'severity' => $count > 0 ? 'warning' : 'ok',
            'description' => 'Active participants sharing the same name inside the same session.',
        ];
    }

    private function missingParentRelations(Organization $organization): array
    {
        $eventsWithoutTournament = DB::table('events')
            ->leftJoin('tournaments', 'tournaments.id', '=', 'events.tournament_id')
            ->where('events.organization_id', $organization->id)
            ->whereNull('events.deleted_at')
            ->where(fn ($query) => $query
                ->whereNull('tournaments.id')
                ->orWhereColumn('tournaments.organization_id', '!=', 'events.organization_id'))
            ->count();

        $registrationsWithoutParent = DB::table('event_participants')
            ->leftJoin('events', 'events.id', '=', 'event_participants.event_id')
            ->leftJoin('participants', 'participants.id', '=', 'event_participants.participant_id')
            ->where('event_participants.organization_id', $organization->id)
            ->whereNull('event_participants.deleted_at')
            ->where(fn ($query) => $query
                ->whereNull('events.id')
                ->orWhereNull('participants.id')
                ->orWhereColumn('events.organization_id', '!=', 'event_participants.organization_id')
                ->orWhereColumn('participants.organization_id', '!=', 'event_participants.organization_id'))
            ->count();

        $count = $eventsWithoutTournament + $registrationsWithoutParent;

        return [
            'key' => 'missing_parent_relations',
            'label' => 'Missing parent relations',
            'count' => (int) $count,
            'severity' => $count > 0 ? 'error' : 'ok',
            'description' => 'Events or registrations whose parent record is missing or belongs to another tenant.',
        ];
    }

    private function orphanResults(Organization $organization): array
    {
        $count = DB::table('results')
            ->leftJoin('matches', 'matches.id', '=', 'results.match_id')
            ->where('results.organization_id', $organization->id)
            ->whereNull('results.deleted_at')
            ->where(fn ($query) => $query
                ->whereNull('matches.id')
                ->orWhereNotNull('matches.deleted_at')
                ->orWhereColumn('matches.organization_id', '!=', 'results.organization_id'))
            ->count();

        return [
            'key' => 'orphan_results',
            'label' => 'Orphan results',
            'count' => (int) $count,
            'severity' => $count > 0 ? 'error' : 'ok',
            'description' => 'Results whose match is missing, deleted, or outside the current organization.',
        ];
    }

    private function invalidTimelines(Organization $organization): array
    {
        $eventTimelines = DB::table('events')
            ->leftJoin('tournaments', 'tournaments.id', '=', 'events.tournament_id')
            ->leftJoin('event_sessions', 'event_sessions.id', '=', 'tournaments.session_id')
            ->where('events.organization_id', $organization->id)
            ->whereNull('events.deleted_at')
            ->where(function ($query) {
                $query->whereColumn('events.end_date', '<', 'events.start_date')
                    ->orWhereColumn('events.start_date', '<', 'tournaments.start_date')
                    ->orWhereColumn('events.end_date', '>', 'tournaments.end_date')
                    ->orWhereColumn('tournaments.start_date', '<', 'event_sessions.start_date')
                    ->orWhereColumn('tournaments.end_date', '>', 'event_sessions.end_date');
            })
            ->count();

        $matchTimelines = DB::table('matches')
            ->leftJoin('events', 'events.id', '=', 'matches.event_id')
            ->where('matches.organization_id', $organization->id)
            ->whereNull('matches.deleted_at')
            ->whereNotNull('matches.scheduled_at')
            ->where(function ($query) {
                $query->whereDate('matches.scheduled_at', '<', DB::raw('events.start_date'))
                    ->orWhereDate('matches.scheduled_at', '>', DB::raw('events.end_date'));
            })
            ->count();

        $registrationTimelines = Schema::hasColumn('events', 'registration_deadline')
            ? DB::table('events')
                ->where('organization_id', $organization->id)
                ->whereNull('deleted_at')
                ->whereNotNull('registration_deadline')
                ->whereDate('registration_deadline', '>', DB::raw('start_date'))
                ->count()
            : 0;

        $count = $eventTimelines + $matchTimelines + $registrationTimelines;

        return [
            'key' => 'invalid_timeline',
            'label' => 'Invalid timeline',
            'count' => (int) $count,
            'severity' => $count > 0 ? 'warning' : 'ok',
            'description' => 'Events, matches, or deadlines outside their configured tournament/session dates.',
        ];
    }
}
