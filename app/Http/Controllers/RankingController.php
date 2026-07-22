<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Tournament;
use App\Services\RankingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function __construct(
        protected RankingService $rankingService,
    ) {}

    public function index(): Response
    {
        $tournaments = \App\Models\Tournament::query()
            ->with('session:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'session_id', 'ranking_strategy']);

        $selectedTournament = request('tournament');
        $rankings = collect();
        $events = collect();

        if ($selectedTournament) {
            $tournament = \App\Models\Tournament::where('slug', $selectedTournament)->firstOrFail();
            $rankings = $this->rankingService->calculateForTournament($tournament);
            $events = Event::where('tournament_id', $tournament->id)->get(['id', 'name', 'slug']);
        }

        return Inertia::render('Rankings/Index', [
            'tournaments' => $tournaments,
            'rankings' => $rankings,
            'selectedTournament' => $selectedTournament,
            'events' => $events,
            'strategies' => $this->rankingService->getAvailableStrategies(),
        ]);
    }

    public function updateStrategy(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

        $validated = request()->validate([
            'ranking_strategy' => ['required', 'string', \Illuminate\Validation\Rule::in(['points', 'win_rate', 'medal_tally'])],
        ]);

        $tournament->update($validated);

        return redirect()->route('rankings.index', ['tournament' => $tournament->slug])
            ->with('success', 'Ranking strategy updated.');
    }
}
