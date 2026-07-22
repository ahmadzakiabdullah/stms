<?php

namespace App\Http\Controllers;

use App\Actions\Matches\DeleteMatch;
use App\Actions\Matches\StoreMatch;
use App\Actions\Matches\UpdateMatch;
use App\Http\Requests\Match\StoreMatchRequest;
use App\Http\Requests\Match\UpdateMatchRequest;
use App\Models\Event;
use App\Models\Fixture;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    public function index(): Response
    {
        $dataLoadFailed = false;

        $matches = $this->safePaginatedQuery(function () {
            return Fixture::with(['event', 'homeParticipant', 'awayParticipant'])
                ->orderByDesc('scheduled_at')
                ->paginate(15)
                ->withQueryString();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
                'path' => request()->url(),
            ]);
        });

        $response = Inertia::render('Matches/Index', [
            'matches' => $matches,
            'events' => Event::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'participants' => Participant::query()->active()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);

        if ($dataLoadFailed) {
            $response->with('error', 'Failed to load some data. Please run "php artisan migrate" on the server (database may be out of date).');
        }

        return $response;
    }

    public function store(StoreMatchRequest $request, StoreMatch $action): RedirectResponse
    {
        Gate::authorize('create', Fixture::class);

        $action->handle(
            auth()->user()->organization,
            $request->validated()
        );

        return redirect()->route('matches.index')
            ->with('success', 'Match created successfully.');
    }

    public function update(UpdateMatchRequest $request, Fixture $match, UpdateMatch $action): RedirectResponse
    {
        Gate::authorize('update', $match);

        $action->handle(
            auth()->user()->organization,
            $match->id,
            $request->validated()
        );

        return redirect()->route('matches.index')
            ->with('success', 'Match updated successfully.');
    }

    public function destroy(Fixture $match, DeleteMatch $action): RedirectResponse
    {
        Gate::authorize('delete', $match);

        $action->handle(
            auth()->user()->organization,
            $match->id
        );

        return redirect()->route('matches.index')
            ->with('success', 'Match deleted successfully.');
    }
}
