<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class ReportingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('verified'),
            new Middleware('can:view-reports'),
        ];
    }

    public function index(Request $request)
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
            ]);
        }

        $safeCount = function (string $modelClass, $query = null) use ($org) {
            try {
                $builder = $modelClass::where('organization_id', $org->id);
                if ($query) {
                    $builder = $query($builder);
                }

                return $builder->count();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        $stats = [
            'total_fixtures' => $safeCount(Fixture::class),
            'completed_fixtures' => $safeCount(Fixture::class, fn ($q) => $q->where('status', 'completed')),
            'pending_fixtures' => $safeCount(Fixture::class, fn ($q) => $q->where('status', 'pending')),
            'in_progress_fixtures' => $safeCount(Fixture::class, fn ($q) => $q->where('status', 'in_progress')),
            'total_results' => $safeCount(Result::class),
            'total_participants' => $safeCount(Participant::class),
            'total_registrations' => $safeCount(Registration::class),
            'total_tournaments' => $safeCount(Tournament::class),
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
            $fixturesByTournament = collect();
        }

        return Inertia::render('Reports/Index', [
            'stats' => $stats,
            'fixturesByStatus' => $fixturesByStatus,
            'recentResults' => $recentResults,
            'fixturesByTournament' => $fixturesByTournament,
        ]);
    }
}
