<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Session;
use App\Models\Tournament;
use App\Services\RankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function __construct(
        protected RankingService $rankingService,
    ) {}

    public function index(): Response
    {
        Gate::authorize('view-rankings');

        $sessions = Session::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'ranking_strategy', 'ranking_rules']);

        $selectedSessionSlug = request('session');

        $rankings = collect();
        $tournaments = collect();
        $selectedTournamentSlug = request('tournament');

        $selectedSession = null;

        if ($selectedSessionSlug) {
            $selectedSession = Session::where('slug', $selectedSessionSlug)->firstOrFail();

            $tournaments = $selectedSession->tournaments()
                ->orderBy('start_date')
                ->get(['id', 'name', 'slug', 'ranking_strategy', 'ranking_rules']);

            if ($selectedTournamentSlug) {
                $tournament = $tournaments->firstWhere('slug', $selectedTournamentSlug);
                if ($tournament) {
                    $rankings = $this->rankingService->calculateForTournament($tournament);
                }
            } else {
                $rankings = $this->rankingService->calculateForSession($selectedSession);
            }
        }

        $events = Event::query()
            ->whereIn('tournament_id', $tournaments->pluck('id'))
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Rankings/Index', [
            'sessions' => $sessions,
            'session' => $selectedSession,
            'selectedSession' => $selectedSessionSlug,
            'tournaments' => $tournaments,
            'selectedTournament' => $selectedTournamentSlug,
            'rankings' => $rankings,
            'events' => $events,
            'strategies' => $this->rankingService->getAvailableStrategies(),
        ]);
    }

    public function updateSessionStrategy(Session $session): RedirectResponse
    {
        Gate::authorize('update', $session);

        $validated = $this->validateConfiguration();

        $session->update($validated);

        activity()
            ->performedOn($session)
            ->causedBy(request()->user())
            ->event('updated')
            ->withProperties(['ranking_strategy' => $validated['ranking_strategy'], 'ranking_rules' => $validated['ranking_rules'] ?? null])
            ->log("Ranking strategy changed to '{$validated['ranking_strategy']}' for session '{$session->name}'");

        return redirect()->route('rankings.index', ['session' => $session->slug])
            ->with('success', 'Session ranking strategy updated.');
    }

    public function updateStrategy(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

        $validated = $this->validateConfiguration();

        $tournament->update($validated);

        activity()
            ->performedOn($tournament)
            ->causedBy(request()->user())
            ->event('updated')
            ->withProperties(['ranking_strategy' => $validated['ranking_strategy'], 'ranking_rules' => $validated['ranking_rules'] ?? null])
            ->log("Ranking strategy changed to '{$validated['ranking_strategy']}' for '{$tournament->name}'");

        return redirect()->route('rankings.index', ['session' => $tournament->session?->slug, 'tournament' => $tournament->slug])
            ->with('success', 'Ranking strategy updated.');
    }

    private function validateConfiguration(): array
    {
        return request()->validate([
            'ranking_strategy' => ['required', 'string', Rule::in(['points', 'win_rate', 'medal_tally'])],
            'ranking_rules' => ['sometimes', 'array'],
            'ranking_rules.points' => ['sometimes', 'array'],
            'ranking_rules.points.win_points' => ['required_with:ranking_rules.points', 'integer', 'between:-100,100'],
            'ranking_rules.points.draw_points' => ['required_with:ranking_rules.points', 'integer', 'between:-100,100'],
            'ranking_rules.points.loss_points' => ['required_with:ranking_rules.points', 'integer', 'between:-100,100'],
            'ranking_rules.points.tiebreakers' => ['required_with:ranking_rules.points', 'array', 'min:1', 'max:4'],
            'ranking_rules.points.tiebreakers.*' => ['string', Rule::in(['points', 'goal_difference', 'score_for', 'wins'])],
            'ranking_rules.win_rate.tiebreakers' => ['sometimes', 'array', 'min:1', 'max:4'],
            'ranking_rules.win_rate.tiebreakers.*' => ['string', Rule::in(['win_rate', 'wins', 'goal_difference', 'score_for'])],
            'ranking_rules.medal_tally.tiebreakers' => ['sometimes', 'array', 'size:3'],
            'ranking_rules.medal_tally.tiebreakers.*' => ['string', 'distinct', Rule::in(['gold', 'silver', 'bronze'])],
        ]);
    }
}
