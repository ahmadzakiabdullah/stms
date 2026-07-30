<?php

namespace App\Http\Controllers;

use App\Actions\Participants\RegisterParticipantToEvent;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\User;
use App\Notifications\NewEventRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventParticipantController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $dataLoadFailed = false;

        $hasParticipant = ! is_null($user->participant_id);
        $isWakil = $hasParticipant && $user->hasRole('faculty-representative');

        $search = $request->input('search');
        $sportId = $request->input('sport_id');
        $categoryId = $request->input('category_id');
        $participantId = $request->input('participant_id');

        $participants = $this->safePaginatedQuery(function () use ($hasParticipant, $user, $search, $sportId, $categoryId, $participantId) {
            $query = Participant::query()
                ->with(['eventParticipants.event.sport', 'eventParticipants.event.sportCategory', 'eventParticipants.event.tournament'])
                ->where('is_active', true);

            if ($hasParticipant) {
                $query->where('id', $user->participant_id);
            } elseif (! $user->hasRole('super-admin')) {
                $query->whereHas('eventParticipants');
            }

            $query
                ->when($search, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                ->when($sportId, fn ($q, $v) => $q->whereHas('eventParticipants.event', fn ($q) => $q->where('sport_id', $v)))
                ->when($categoryId, fn ($q, $v) => $q->whereHas('eventParticipants.event', fn ($q) => $q->where('sport_category_id', $v)))
                ->when($participantId, fn ($q, $v) => $q->where('id', $v))
                ->orderBy('name');

            return $query->paginate(10)->withQueryString();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return new LengthAwarePaginator([], 0, 10, 1, [
                'path' => request()->url(),
            ]);
        });

        $events = $this->safeCollectionQuery(function () {
            return Event::query()
                ->with(['sport', 'sportCategory', 'tournament'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return collect();
        });

        $faculties = $this->safeCollectionQuery(function () {
            return Participant::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return collect();
        });

        $response = Inertia::render('EventParticipants/Index', [
            'participants' => $participants,
            'events' => $events,
            'faculties' => $faculties,
            'isFacultyRepresentative' => $isWakil,
        ]);

        if ($dataLoadFailed) {
            $response->with('error', 'Failed to load some data. Please run "php artisan migrate" on the server (database may be out of date).');
        }

        return $response;
    }

    public function store(Request $request, RegisterParticipantToEvent $action): RedirectResponse
    {
        $user = Auth::user();
        $isWakil = $user->hasRole('faculty-representative') && $user->participant_id;

        if ($isWakil) {
            $participantId = $user->participant_id;
        } else {
            Gate::authorize('create', EventParticipant::class);
            $validated = $request->validate([
                'event_id' => 'required|string|exists:events,id',
                'participant_id' => 'required|string|exists:participants,id',
            ]);
            $participantId = $validated['participant_id'];
        }

        $eventId = $request->validate(['event_id' => 'required|string|exists:events,id'])['event_id'];
        $participant = Participant::findOrFail($participantId);

        $event = Event::findOrFail($eventId);
        if ($event->registration_deadline && now()->greaterThan($event->registration_deadline)) {
            return redirect()->route('event-participants.index')
                ->with('error', 'Registration deadline for this event has passed.');
        }

        try {
            $action->handle($participant, $eventId);
        } catch (ValidationException $e) {
            return redirect()->route('event-participants.index')
                ->with('error', $e->getMessage());
        }

        $ep = EventParticipant::where('event_id', $eventId)
            ->where('participant_id', $participant->id)
            ->latest()
            ->first();
        if ($ep) {
            $deanUsers = User::where('participant_id', $participant->id)
                ->role('dean')
                ->get();
            foreach ($deanUsers as $deanUser) {
                $deanUser->notify(new NewEventRegistration($ep));
            }
        }

        return redirect()->route('event-participants.index')
            ->with('success', 'Participant registered to event successfully.');
    }

    public function destroy(EventParticipant $eventParticipant): RedirectResponse
    {
        Gate::authorize('delete', $eventParticipant);

        $eventParticipant->delete();

        return redirect()->route('event-participants.index')
            ->with('success', 'Event registration deleted.');
    }
}
