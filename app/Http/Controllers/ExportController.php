<?php

namespace App\Http\Controllers;

use App\Exports\FixtureExport;
use App\Exports\RankingExport;
use App\Exports\ResultExport;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Tournament;
use App\Services\RankingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
            new Middleware('verified'),
        ];
    }

    // ─── FIXTURES ───

    public function fixturesPdf(Request $request)
    {
        $org = $request->user()->organization;
        $eventId = $request->query('event_id');

        $query = Fixture::where('organization_id', $org->id)
            ->with(['event.tournament.session', 'homeParticipant', 'awayParticipant']);

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        $fixtures = $query->orderBy('scheduled_at')->get();

        $headings = ['#', 'Tournament', 'Session', 'Event', 'Match No', 'Home', 'Away', 'Venue', 'Date', 'Status'];
        $rows = $fixtures->map(function ($f, $i) {
            return [
                $i + 1,
                $f->event?->tournament?->name ?? '-',
                $f->event?->tournament?->session?->name ?? '-',
                $f->event?->name ?? '-',
                $f->match_number ?? '-',
                $f->homeParticipant?->name ?? 'TBD',
                $f->awayParticipant?->name ?? 'TBD',
                $f->venue ?? '-',
                $f->scheduled_at?->format('d M Y H:i') ?? '-',
                $f->status ?? 'pending',
            ];
        });

        $pdf = Pdf::loadView('exports.pdf', [
            'title' => 'Fixture Schedule',
            'subtitle' => $org->name . ($eventId ? ' — Filtered by Event' : ''),
            'headings' => $headings,
            'rows' => $rows,
        ]);

        return $pdf->download('fixtures-' . now()->format('Y-m-d') . '.pdf');
    }

    public function fixturesExcel(Request $request)
    {
        $org = $request->user()->organization;
        $eventId = $request->query('event_id');

        return Excel::download(new FixtureExport($org, $eventId), 'fixtures-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ─── RESULTS ───

    public function resultsPdf(Request $request)
    {
        $org = $request->user()->organization;
        $eventId = $request->query('event_id');

        $query = \App\Models\Result::where('organization_id', $org->id)
            ->with(['match.event.tournament', 'match.homeParticipant', 'match.awayParticipant', 'winner']);

        if ($eventId) {
            $query->whereHas('match', fn ($q) => $q->where('event_id', $eventId));
        }

        $results = $query->orderByDesc('created_at')->get();

        $headings = ['#', 'Tournament', 'Event', 'Match #', 'Home', 'Score', 'Away', 'Winner'];
        $rows = $results->map(function ($r, $i) {
            return [
                $i + 1,
                $r->match?->event?->tournament?->name ?? '-',
                $r->match?->event?->name ?? '-',
                $r->match?->match_number ?? '-',
                $r->match?->homeParticipant?->name ?? '-',
                ($r->score_home ?? 0) . ' - ' . ($r->score_away ?? 0),
                $r->match?->awayParticipant?->name ?? '-',
                $r->winner?->name ?? 'Draw',
            ];
        });

        $pdf = Pdf::loadView('exports.pdf', [
            'title' => 'Match Results',
            'subtitle' => $org->name . ($eventId ? ' — Filtered by Event' : ''),
            'headings' => $headings,
            'rows' => $rows,
        ]);

        return $pdf->download('results-' . now()->format('Y-m-d') . '.pdf');
    }

    public function resultsExcel(Request $request)
    {
        $org = $request->user()->organization;
        $eventId = $request->query('event_id');

        return Excel::download(new ResultExport($org, $eventId), 'results-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ─── RANKINGS ───

    public function rankingsPdf(Request $request, string $tournamentId)
    {
        $org = $request->user()->organization;

        $tournament = Tournament::where('organization_id', $org->id)
            ->with(['session'])
            ->findOrFail($tournamentId);

        $service = new RankingService();
        $rankings = $service->calculateForTournament($tournament);
        $strategy = $tournament->ranking_strategy ?? 'points';

        $headings = ['#', 'Participant', 'Played', 'W', 'D', 'L'];
        if ($strategy === 'points') {
            array_push($headings, 'GF', 'GA', 'GD', 'Pts');
        } elseif ($strategy === 'win_rate') {
            array_push($headings, 'Win %');
        } else {
            array_push($headings, 'GF', 'GA', 'Pts', 'G', 'S', 'B');
        }

        $rows = $rankings->map(function ($r) use ($strategy) {
            $row = [
                $r['rank'],
                $r['participant_name'],
                $r['matches_played'],
                $r['wins'],
                $r['draws'],
                $r['losses'],
            ];
            if ($strategy === 'points') {
                $row = array_merge($row, [$r['score_for'], $r['score_against'], $r['goal_difference'], $r['points']]);
            } elseif ($strategy === 'win_rate') {
                $row[] = $r['win_rate'];
            } else {
                $row = array_merge($row, [$r['score_for'], $r['score_against'], $r['points'], $r['gold'], $r['silver'], $r['bronze']]);
            }
            return $row;
        });

        $pdf = Pdf::loadView('exports.pdf', [
            'title' => 'Rankings — ' . $tournament->name,
            'subtitle' => $org->name . ' • Strategy: ' . ucfirst(str_replace('_', ' ', $strategy)),
            'headings' => $headings,
            'rows' => $rows,
        ]);

        return $pdf->download('rankings-' . $tournament->slug . '-' . now()->format('Y-m-d') . '.pdf');
    }

    public function rankingsExcel(Request $request, string $tournamentId)
    {
        $org = $request->user()->organization;

        return Excel::download(new RankingExport($org, $tournamentId), 'rankings-' . now()->format('Y-m-d') . '.xlsx');
    }

    // ─── MATCH SHEET ───

    public function matchSheet(Request $request, string $fixtureId)
    {
        $org = $request->user()->organization;

        $fixture = Fixture::where('organization_id', $org->id)
            ->with(['event.tournament', 'homeParticipant', 'awayParticipant', 'result'])
            ->findOrFail($fixtureId);

        $result = $fixture->result;

        $pdf = Pdf::loadView('exports.match-sheet', [
            'fixture' => $fixture,
            'result' => $result,
        ]);

        return $pdf->download('match-sheet-' . ($fixture->match_number ?? 'draft') . '.pdf');
    }
}
