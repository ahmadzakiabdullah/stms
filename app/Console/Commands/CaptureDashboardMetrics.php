<?php

namespace App\Console\Commands;

use App\Models\DashboardMetricSnapshot;
use App\Models\Session;
use App\Models\Tournament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CaptureDashboardMetrics extends Command
{
    protected $signature = 'stms:dashboard-snapshot';

    protected $description = 'Capture daily platform metrics for dashboard trends';

    public function handle(): int
    {
        $row = (array) DB::query()->selectRaw("(SELECT COUNT(*) FROM organizations WHERE is_active = 1) AS organizations,
            (SELECT COUNT(*) FROM users) AS users,
            (SELECT COUNT(*) FROM events WHERE is_active = 1 AND deleted_at IS NULL) AS events,
            (SELECT COUNT(*) FROM matches WHERE deleted_at IS NULL) AS matches")->first();

        $this->storeSnapshot('global', $row);

        foreach (Session::query()->withoutOrganizationScope()->get(['id']) as $session) {
            $this->storeSnapshot('session:'.$session->id, array_merge($row, $this->metricsForSession($session->id)));
        }

        foreach (Tournament::query()->withoutOrganizationScope()->get(['id']) as $tournament) {
            $this->storeSnapshot('tournament:'.$tournament->id, array_merge($row, $this->metricsForTournament($tournament->id)));
        }

        $this->info('Dashboard metrics captured for '.today()->toDateString().'.');

        return self::SUCCESS;
    }

    private function storeSnapshot(string $scopeKey, array $values): void
    {
        DashboardMetricSnapshot::query()->updateOrCreate(
            ['snapshot_date' => today(), 'scope_key' => $scopeKey],
            [
                'organizations' => (int) ($values['organizations'] ?? 0),
                'users' => (int) ($values['users'] ?? 0),
                'events' => (int) ($values['events'] ?? 0),
                'matches' => (int) ($values['matches'] ?? 0),
            ],
        );
    }

    private function metricsForSession(string $sessionId): array
    {
        return (array) DB::table('event_sessions')->where('event_sessions.id', $sessionId)
            ->selectRaw('(SELECT COUNT(*) FROM events e JOIN tournaments t ON t.id = e.tournament_id WHERE t.session_id = ? AND e.is_active = 1 AND e.deleted_at IS NULL) AS events,
                (SELECT COUNT(*) FROM matches m JOIN events e ON e.id = m.event_id JOIN tournaments t ON t.id = e.tournament_id WHERE t.session_id = ? AND m.deleted_at IS NULL) AS matches', [$sessionId, $sessionId])
            ->first();
    }

    private function metricsForTournament(string $tournamentId): array
    {
        return (array) DB::table('tournaments')->where('tournaments.id', $tournamentId)
            ->selectRaw('(SELECT COUNT(*) FROM events e WHERE e.tournament_id = ? AND e.is_active = 1 AND e.deleted_at IS NULL) AS events,
                (SELECT COUNT(*) FROM matches m JOIN events e ON e.id = m.event_id WHERE e.tournament_id = ? AND m.deleted_at IS NULL) AS matches', [$tournamentId, $tournamentId])
            ->first();
    }
}
