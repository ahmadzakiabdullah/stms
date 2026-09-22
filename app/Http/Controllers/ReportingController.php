<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Result;
use App\Services\DataQualityService;
use App\Services\SystemHealthService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ReportingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('can:view-reports'),
        ];
    }

    public function index(Request $request, DataQualityService $dataQuality, SystemHealthService $health)
    {
        $org = $request->user()->organization;

        if (! $org) {
            return Inertia::render('Reports/Index', [
                'stats' => [
                    'total_fixtures' => 0, 'completed_fixtures' => 0, 'pending_fixtures' => 0,
                    'in_progress_fixtures' => 0, 'total_results' => 0, 'total_participants' => 0,
                    'total_registrations' => 0, 'total_tournaments' => 0,
                ],
                'fixturesByStatus' => [],
                'recentResults' => collect(),
                'fixturesByTournament' => collect(),
                'operations' => $this->emptyOperations(),
                'dataQuality' => ['status' => 'ok', 'total_issues' => 0, 'checks' => []],
            ]);
        }

        try {
            $fixtureStats = DB::table('matches')
                ->selectRaw('COUNT(*) as total_fixtures')
                ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_fixtures")
                ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_fixtures")
                ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_fixtures")
                ->where('organization_id', $org->id)
                ->whereNull('deleted_at')
                ->first();
            $domainStats = DB::selectOne(
                'select
                    (select count(*) from results where organization_id = ? and deleted_at is null) as total_results,
                    (select count(*) from participants where organization_id = ? and deleted_at is null) as total_participants,
                    (select count(*) from registrations where organization_id = ? and deleted_at is null) as total_registrations,
                    (select count(*) from tournaments where organization_id = ? and deleted_at is null) as total_tournaments',
                [$org->id, $org->id, $org->id, $org->id]
            );
        } catch (\Throwable $e) {
            Log::warning('Report count fallback activated.', [
                'exception' => $e,
                'correlation_id' => $request->attributes->get('correlation_id'),
                'user_id' => $request->user()?->uuid,
                'organization_id' => $org->id,
                'route' => $request->path(),
            ]);
            $fixtureStats = (object) [];
            $domainStats = (object) [];
        }

        $stats = [
            'total_fixtures' => (int) ($fixtureStats->total_fixtures ?? 0),
            'completed_fixtures' => (int) ($fixtureStats->completed_fixtures ?? 0),
            'pending_fixtures' => (int) ($fixtureStats->pending_fixtures ?? 0),
            'in_progress_fixtures' => (int) ($fixtureStats->in_progress_fixtures ?? 0),
            'total_results' => (int) ($domainStats->total_results ?? 0),
            'total_participants' => (int) ($domainStats->total_participants ?? 0),
            'total_registrations' => (int) ($domainStats->total_registrations ?? 0),
            'total_tournaments' => (int) ($domainStats->total_tournaments ?? 0),
        ];

        $fixturesByStatus = [
            ['status' => 'Pending', 'count' => $stats['pending_fixtures']],
            ['status' => 'In Progress', 'count' => $stats['in_progress_fixtures']],
            ['status' => 'Completed', 'count' => $stats['completed_fixtures']],
        ];

        try {
            $recentResults = Result::where('organization_id', $org->id)
                ->with(['match.homeParticipant', 'match.awayParticipant', 'match.event.tournament'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'home' => $r->match?->homeParticipant?->name ?? '-',
                    'away' => $r->match?->awayParticipant?->name ?? '-',
                    'score' => ($r->score_home ?? 0).' - '.($r->score_away ?? 0),
                    'tournament' => $r->match?->event?->tournament?->name ?? '-',
                    'created_at' => $r->created_at->format('d M Y H:i'),
                ]);
        } catch (\Throwable $e) {
            Log::warning('Report recent results fallback activated.', [
                'exception' => $e,
                'correlation_id' => $request->attributes->get('correlation_id'),
                'user_id' => $request->user()?->uuid,
                'organization_id' => $org->id,
                'route' => $request->path(),
            ]);
            $recentResults = collect();
        }

        try {
            $fixturesByTournament = Fixture::where('organization_id', $org->id)
                ->selectRaw('event_id, status, count(*) as count')
                ->with(['event.tournament'])
                ->groupBy('event_id', 'status')
                ->get()
                ->groupBy(fn ($f) => $f->event?->tournament?->name ?? 'Unknown')
                ->map(function ($group) {
                    return $group->map(fn ($f) => [
                        'status' => $f->status,
                        'count' => $f->count,
                    ])->values();
                });
        } catch (\Throwable $e) {
            Log::warning('Report tournament breakdown fallback activated.', [
                'exception' => $e,
                'correlation_id' => $request->attributes->get('correlation_id'),
                'user_id' => $request->user()?->uuid,
                'organization_id' => $org->id,
                'route' => $request->path(),
            ]);
            $fixturesByTournament = collect();
        }

        return Inertia::render('Reports/Index', [
            'stats' => $stats,
            'fixturesByStatus' => $fixturesByStatus,
            'recentResults' => $recentResults,
            'fixturesByTournament' => $fixturesByTournament,
            'operations' => $this->operationsSummary($org->id, $health->check()),
            'dataQuality' => $dataQuality->summary($org),
        ]);
    }

    private function emptyOperations(): array
    {
        return [
            'status' => 'ok',
            'queue' => ['pending' => 0, 'failed' => 0, 'status' => 'ok'],
            'data_freshness' => ['last_match_update' => null, 'last_result_update' => null],
            'active_sessions' => ['domain' => 0, 'application' => null],
            'incident_signal' => 'No organization context.',
        ];
    }

    private function operationsSummary(string $organizationId, array $health): array
    {
        $queue = $health['components']['queue'] ?? ['pending' => 0, 'failed' => 0, 'status' => 'ok'];
        $freshness = DB::selectOne(
            'select
                (select max(updated_at) from matches where organization_id = ? and deleted_at is null) as last_match_update,
                (select max(updated_at) from results where organization_id = ? and deleted_at is null) as last_result_update',
            [$organizationId, $organizationId]
        );

        $applicationSessions = Schema::hasTable('sessions')
            ? DB::table('sessions')->count()
            : null;
        $domainSessions = DB::table('event_sessions')
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->count();

        return [
            'status' => $health['status'] ?? 'degraded',
            'queue' => [
                'pending' => (int) ($queue['pending'] ?? 0),
                'failed' => (int) ($queue['failed'] ?? 0),
                'status' => $queue['status'] ?? 'ok',
            ],
            'data_freshness' => [
                'last_match_update' => $freshness->last_match_update ?? null,
                'last_result_update' => $freshness->last_result_update ?? null,
            ],
            'active_sessions' => [
                'domain' => (int) $domainSessions,
                'application' => $applicationSessions === null ? null : (int) $applicationSessions,
            ],
            'incident_signal' => ($health['status'] ?? 'degraded') === 'ok'
                ? 'No active incident signal from repository health checks.'
                : 'Health checks report degraded components; inspect queue, cache, database and disk.',
        ];
    }
}
