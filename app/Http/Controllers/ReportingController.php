<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\DataTransfer;
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
                'governance' => $this->emptyGovernance(),
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
            'governance' => $this->governanceSummary($org->id),
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

    private function emptyGovernance(): array
    {
        return [
            'comparison' => [
                'window_days' => 7,
                'fixtures_completed_current' => 0,
                'fixtures_completed_previous' => 0,
                'results_recorded_current' => 0,
                'results_recorded_previous' => 0,
                'completion_delta' => 0,
                'results_delta' => 0,
            ],
            'exports' => [
                'window_days' => 30,
                'total' => 0,
                'completed' => 0,
                'failed' => 0,
                'running' => 0,
                'last_export_at' => null,
                'policy' => 'Exports are tenant-scoped, permission-gated and requester-only for queued downloads.',
            ],
            'retention' => [
                'backup_retention_days' => (int) config('app.backup.retention_days', 14),
                'transfer_retention_days' => 30,
                'archive_owner' => 'Organization administrator',
                'policy' => 'Keep operational exports only while needed for competition administration, then archive official records and remove working files.',
            ],
            'ownership' => [
                ['area' => 'Competition records', 'owner' => 'Tournament manager', 'access' => 'Create/update fixtures, results and draw operations within tenant scope.'],
                ['area' => 'Participant data', 'owner' => 'Organization administrator', 'access' => 'Manage participant records, registrations and imports for the organization.'],
                ['area' => 'Exports and reports', 'owner' => 'Report/export role holders', 'access' => 'Export permission required; queued files are requester-only.'],
                ['area' => 'Audit and retention', 'owner' => 'System administrator', 'access' => 'Review activity logs, retention evidence and operational backups.'],
            ],
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

    private function governanceSummary(string $organizationId): array
    {
        $now = now();
        $currentStart = $now->copy()->subDays(7);
        $previousStart = $now->copy()->subDays(14);

        $completedCurrent = Fixture::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$currentStart, $now])
            ->count();
        $completedPrevious = Fixture::query()
            ->where('organization_id', $organizationId)
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$previousStart, $currentStart])
            ->count();
        $resultsCurrent = Result::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('created_at', [$currentStart, $now])
            ->count();
        $resultsPrevious = Result::query()
            ->where('organization_id', $organizationId)
            ->whereBetween('created_at', [$previousStart, $currentStart])
            ->count();

        $transferWindow = $now->copy()->subDays(30);
        $exportTypes = [
            DataTransfer::TYPE_EXPORT_FIXTURES,
            DataTransfer::TYPE_EXPORT_RESULTS,
            DataTransfer::TYPE_EXPORT_RANKINGS,
            DataTransfer::TYPE_EXPORT_MEDALS,
        ];
        $exportQuery = DataTransfer::query()
            ->where('organization_id', $organizationId)
            ->whereIn('type', $exportTypes)
            ->where('created_at', '>=', $transferWindow);

        return array_replace_recursive($this->emptyGovernance(), [
            'comparison' => [
                'fixtures_completed_current' => $completedCurrent,
                'fixtures_completed_previous' => $completedPrevious,
                'results_recorded_current' => $resultsCurrent,
                'results_recorded_previous' => $resultsPrevious,
                'completion_delta' => $completedCurrent - $completedPrevious,
                'results_delta' => $resultsCurrent - $resultsPrevious,
            ],
            'exports' => [
                'total' => (clone $exportQuery)->count(),
                'completed' => (clone $exportQuery)->whereIn('status', [DataTransfer::STATUS_COMPLETED, DataTransfer::STATUS_COMPLETED_WITH_ERRORS])->count(),
                'failed' => (clone $exportQuery)->where('status', DataTransfer::STATUS_FAILED)->count(),
                'running' => (clone $exportQuery)->whereIn('status', [DataTransfer::STATUS_PENDING, DataTransfer::STATUS_RUNNING])->count(),
                'last_export_at' => (clone $exportQuery)->latest('created_at')->value('created_at'),
            ],
        ]);
    }
}
