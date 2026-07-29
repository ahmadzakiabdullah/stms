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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {

        $events = Event::query()
            ->with(['tournament:id,name', 'sport:id,name', 'sportCategory:id,name'])
            ->withCount('pools')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'tournament_id', 'sport_id', 'sport_category_id']);

        $drawnEventIds = $events->filter(fn ($e) => $e->pools_count > 0)->pluck('id')->values()->toArray();
        $selectedEvent = $events->firstWhere('slug', $request->string('event')->toString());

        // Continue accepting bookmarked URLs that used the UUID query parameter.
        if (! $selectedEvent && $request->filled('event_id')) {
            $selectedEvent = $events->firstWhere('id', $request->string('event_id')->toString());

            if ($selectedEvent) {
                return redirect()->route('matches.index', ['event' => $selectedEvent->slug]);
            }
        }

        if ($selectedEvent && ! in_array($selectedEvent->id, $drawnEventIds, true)) {
            $selectedEvent = null;
        }

        $pools = collect();
        if ($selectedEvent) {
            $pools = $selectedEvent->pools()
                ->with([
                    'eventParticipants.participant',
                    'fixtures' => function ($q) {
                        $q->with(['homeParticipant', 'awayParticipant'])
                            ->orderBy('round')
                            ->orderBy('match_number');
                    },
                ])
                ->orderBy('sort_order')
                ->get();
        }

        $allFixtures = Fixture::with(['event', 'homeParticipant', 'awayParticipant'])
            ->orderByDesc('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        $response = Inertia::render('Matches/Index', [
            'events' => $events,
            'drawnEventIds' => $drawnEventIds,
            'selectedEventId' => $selectedEvent?->id,
            'nextMatchNumber' => $selectedEvent
                ? ((int) Fixture::query()->where('event_id', $selectedEvent->id)->max('match_number')) + 1
                : 1,
            'pools' => $pools,
            'allFixtures' => $allFixtures,
            'participants' => Participant::query()->active()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);

        return $response;
    }

    public function store(StoreMatchRequest $request, StoreMatch $action): RedirectResponse
    {
        Gate::authorize('create', Fixture::class);

        $action->handle(
            auth()->user()->organization,
            $request->validated()
        );

        return redirect()->back()->with('success', 'Match created successfully.');
    }

    public function update(UpdateMatchRequest $request, Fixture $match, UpdateMatch $action): RedirectResponse
    {
        Gate::authorize('update', $match);

        $action->handle(
            auth()->user()->organization,
            $match->id,
            $request->validated()
        );

        return redirect()->back()->with('success', 'Match updated successfully.');
    }

    public function destroy(Fixture $match, DeleteMatch $action): RedirectResponse
    {
        Gate::authorize('delete', $match);

        $action->handle(
            auth()->user()->organization,
            $match->id
        );

        return redirect()->back()->with('success', 'Match deleted successfully.');
    }
}
