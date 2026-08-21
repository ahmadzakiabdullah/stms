<?php

namespace App\Http\Controllers;

use App\Actions\Tournaments\CreateTournament;
use App\Actions\Tournaments\DeleteTournament;
use App\Actions\Tournaments\UpdateTournament;
use App\Http\Requests\Tournament\StoreTournamentRequest;
use App\Http\Requests\Tournament\UpdateTournamentRequest;
use App\Models\Session;
use App\Models\Sport;
use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class TournamentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Tournament::class);

        $user = Auth::user();

        // Defensive queries (prevent 500 when prod DB is not migrated)
        $tournaments = $this->safePaginatedQuery(function () use ($request) {
            return Tournament::with(['session', 'organization', 'sports'])
                ->when($request->filled('search'), fn ($query) => $query->where(function ($q) use ($request) {
                    $search = trim($request->string('search')->toString());
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('session', fn ($session) => $session->where('name', 'like', "%{$search}%"));
                }))
                ->orderBy('start_date', 'desc')
                ->paginate(15)
                ->withQueryString();
        });

        $sessions = $this->safeCollectionQuery(function () {
            return Session::query()
                ->orderBy('start_date', 'desc')
                ->get(['id', 'name', 'slug']);
        });

        $sports = $this->safeCollectionQuery(function () {
            return Sport::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        });

        return Inertia::render('Tournaments/Index', [
            'tournaments' => $tournaments,
            'sessions' => $sessions,
            'sports' => $sports,
        ]);
    }

    public function store(StoreTournamentRequest $request, CreateTournament $action): RedirectResponse
    {
        Gate::authorize('create', Tournament::class);

        $action->handle($request->validated());

        return redirect()->route('tournaments.index')
            ->with('success', 'Tournament created successfully.');
    }

    public function update(UpdateTournamentRequest $request, Tournament $tournament, UpdateTournament $action): RedirectResponse
    {
        Gate::authorize('update', $tournament);

        $action->handle($tournament, $request->validated());

        return redirect()->route('tournaments.index')
            ->with('success', 'Tournament updated successfully.');
    }

    public function destroy(Tournament $tournament, DeleteTournament $action): RedirectResponse
    {
        Gate::authorize('delete', $tournament);

        $action->handle($tournament);

        return redirect()->route('tournaments.index')
            ->with('success', 'Tournament deleted successfully.');
    }

    public function generateEvents(Tournament $tournament, TournamentService $service): RedirectResponse
    {
        Gate::authorize('update', $tournament);

        try {
            $count = $service->generateEventsFromCategories($tournament);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            Log::error('Generate events failed', [
                'tournament_id' => $tournament->id,
                'error' => $message,
            ]);

            return redirect()->route('tournaments.index')
                ->with('error', "Failed to generate events: {$message}");
        }

        if ($count > 0) {
            return redirect()->route('tournaments.index')
                ->with('success', "{$count} events generated from sport categories.");
        }

        return redirect()->route('tournaments.index')
            ->with('error', 'No new events created. Either all sport categories already have events, or no sports/categories are linked to this tournament.');
    }
}
