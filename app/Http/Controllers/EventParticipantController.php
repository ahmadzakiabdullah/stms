<?php

namespace App\Http\Controllers;

use App\Actions\EventParticipants\UpdateEventParticipantStatus;
use App\Actions\Participants\BatchRegisterParticipantToEvents;
use App\Actions\Participants\RegisterParticipantToEvent;
use App\Http\Requests\EventParticipant\BatchRegisterEventParticipantsRequest;
use App\Http\Requests\EventParticipant\RegisterEventParticipantRequest;
use App\Http\Requests\EventParticipant\UpdateEventParticipantStatusRequest;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\SquadMember;
use App\Services\EventParticipantNotificationService;
use App\Services\SquadManagementService;
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
        $status = $request->input('status');

        $participants = $this->safePaginatedQuery(function () use ($hasParticipant, $user, $search, $sportId, $categoryId, $participantId, $status) {
            $query = Participant::query()
                ->with(['eventParticipants' => function ($q) use ($search, $sportId, $categoryId, $status) {
                    $q->with(['event.sport', 'event.sportCategory', 'event.tournament', 'squadMembers' => fn ($q) => $q->ordered()])
                        ->when($search, fn ($q, $v) => $q->where(fn ($q) => $q->whereHas('event', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                            ->orWhereHas('event.sport', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                            ->orWhereHas('event.sportCategory', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                        ))
                        ->when($sportId, fn ($q) => $q->whereHas('event', fn ($q) => $q->where('sport_id', $sportId)))
                        ->when($categoryId, fn ($q) => $q->whereHas('event', fn ($q) => $q->where('sport_category_id', $categoryId)))
                        ->when($status, fn ($q, $v) => $q->where('status', $v));
                }])
                ->where('is_active', true);

            if ($hasParticipant) {
                $query->where('id', $user->participant_id);
            } elseif (! $user->hasRole('super-admin')) {
                $query->whereHas('eventParticipants');
            }

            $query
                ->when($search, function ($q, $v) {
                    $q->where(function ($q) use ($v) {
                        $q->where('name', 'like', "%{$v}%")
                            ->orWhereHas('eventParticipants.event', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                            ->orWhereHas('eventParticipants.event.sport', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                            ->orWhereHas('eventParticipants.event.sportCategory', fn ($q) => $q->where('name', 'like', "%{$v}%"));
                    });
                })
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

        $events = $this->safeCollectionQuery(function () use ($search, $sportId, $categoryId) {
            return Event::query()
                ->with(['sport', 'sportCategory', 'tournament'])
                ->where('is_active', true)
                ->when($search, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%{$v}%")
                    ->orWhereHas('sport', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                    ->orWhereHas('sportCategory', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                ))
                ->when($sportId, fn ($q, $v) => $q->where('sport_id', $v))
                ->when($categoryId, fn ($q, $v) => $q->where('sport_category_id', $v))
                ->orderBy('name')
                ->get();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return collect();
        });

        $faculties = $this->safeCollectionQuery(function () {
            return Participant::query()
                ->with(['eventParticipants:id,participant_id,event_id'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return collect();
        });

        $statusCounts = $this->safeCollectionQuery(function () use ($hasParticipant, $user, $search, $sportId, $categoryId, $participantId) {
            $query = EventParticipant::query()
                ->when($search, fn ($q, $v) => $q->where(fn ($q) => $q->whereHas('participant', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                    ->orWhereHas('event', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                    ->orWhereHas('event.sport', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                    ->orWhereHas('event.sportCategory', fn ($q) => $q->where('name', 'like', "%{$v}%"))
                ))
                ->when($sportId, fn ($q, $v) => $q->whereHas('event', fn ($q) => $q->where('sport_id', $v)))
                ->when($categoryId, fn ($q, $v) => $q->whereHas('event', fn ($q) => $q->where('sport_category_id', $v)))
                ->when($participantId, fn ($q, $v) => $q->where('participant_id', $v));

            if ($hasParticipant) {
                $query->where('participant_id', $user->participant_id);
            }

            return $query->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        }, fn () => collect(['pending' => 0, 'confirmed' => 0, 'rejected' => 0]));

        $response = Inertia::render('EventParticipants/Index', [
            'participants' => $participants,
            'events' => $events,
            'faculties' => $faculties,
            'isFacultyRepresentative' => $isWakil,
            'statusCounts' => $statusCounts,
        ]);

        if ($dataLoadFailed) {
            $response->with('error', 'Failed to load some data. Please run "php artisan migrate" on the server (database may be out of date).');
        }

        return $response;
    }

    public function store(RegisterEventParticipantRequest $request, RegisterParticipantToEvent $action, EventParticipantNotificationService $notificationService): RedirectResponse
    {
        $user = Auth::user();
        $isWakil = $user->hasRole('faculty-representative') && $user->participant_id;

        if ($isWakil) {
            $participantId = $user->participant_id;
        } else {
            Gate::authorize('create', EventParticipant::class);
            $validated = $request->validated();
            $participantId = $validated['participant_id'];
        }

        $eventId = $request->input('event_id');
        $participant = Participant::findOrFail($participantId);

        try {
            $ep = $action->handle($participant, $eventId);
        } catch (ValidationException $e) {
            return redirect()->route($isWakil ? 'dashboard' : 'event-participants.index')
                ->with('error', $e->getMessage());
        }

        $notificationService->notifyRegistration($ep);

        return redirect()->route($isWakil ? 'dashboard' : 'event-participants.index')
            ->with('success', 'Participant registered to event successfully.');
    }

    public function storeBatch(BatchRegisterEventParticipantsRequest $request, BatchRegisterParticipantToEvents $action, EventParticipantNotificationService $notificationService): RedirectResponse
    {
        $user = Auth::user();
        $isWakil = $user->hasRole('faculty-representative') && $user->participant_id;

        if ($isWakil) {
            $participant = Participant::findOrFail($user->participant_id);
        } else {
            Gate::authorize('create', EventParticipant::class);
            $participant = Participant::findOrFail($request->input('participant_id'));
        }

        $validated = $request->validated();

        ['registered' => $registered, 'failures' => $failures, 'created' => $created] = $action->handle($participant, $validated['event_ids']);

        foreach ($created as $ep) {
            $notificationService->notifyRegistration($ep);
        }

        $redirect = $isWakil ? 'dashboard' : 'event-participants.index';

        if ($registered === 0 && count($failures) > 0) {
            return redirect()->route($redirect)
                ->with('error', implode(' ', $failures));
        }

        return redirect()->route($redirect)
            ->with('success', "Registered for {$registered} event(s).")
            ->with('error', count($failures) > 0 ? implode(' ', $failures) : null);
    }

    public function destroy(EventParticipant $eventParticipant): RedirectResponse
    {
        Gate::authorize('delete', $eventParticipant);

        $eventParticipant->delete();

        return redirect()->route('event-participants.index')
            ->with('success', 'Event registration deleted.');
    }

    public function updateStatus(UpdateEventParticipantStatusRequest $request, EventParticipant $eventParticipant, UpdateEventParticipantStatus $action): RedirectResponse
    {
        Gate::authorize('update', $eventParticipant);

        $validated = $request->validated();
        $action->handle($eventParticipant, $validated['status']);

        return redirect()->route('event-participants.index', collect($request->query())->only([
            'search', 'sport_id', 'category_id', 'participant_id', 'status',
        ])->filter(fn ($v) => $v !== null && $v !== '')->all())->with('success', $validated['status'] === 'confirmed'
            ? 'Registration approved.'
            : 'Registration rejected.');
    }

    public function storeSquad(Request $request, EventParticipant $eventParticipant, SquadManagementService $squadService): RedirectResponse
    {
        $this->authorizeSquadManagement($eventParticipant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:athlete_male,athlete_female,assistant_manager,manager,coach,physio'],
            'matrix_no' => ['required', 'string', 'max:20'],
            'identification_no' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $squadService->add($eventParticipant, $validated);
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()
            ->with('success', 'Squad member added.');
    }

    public function updateSquad(Request $request, EventParticipant $eventParticipant, SquadMember $squadMember, SquadManagementService $squadService): RedirectResponse
    {
        $this->authorizeSquadManagement($eventParticipant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:athlete_male,athlete_female,assistant_manager,manager,coach,physio'],
            'matrix_no' => ['required', 'string', 'max:20'],
            'identification_no' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $squadService->update($eventParticipant, $squadMember, $validated);
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()
            ->with('success', 'Squad member updated.');
    }

    public function destroySquad(EventParticipant $eventParticipant, SquadMember $squadMember, SquadManagementService $squadService): RedirectResponse
    {
        $this->authorizeSquadManagement($eventParticipant);

        $squadService->remove($eventParticipant, $squadMember);

        return redirect()->back()
            ->with('success', 'Squad member removed.');
    }

    private function authorizeSquadManagement(EventParticipant $eventParticipant): void
    {
        $user = Auth::user();
        abort_unless($user->hasRole('super-admin') || $user->hasRole('org-admin'), 403);

        abort_unless(
            $user->hasRole('super-admin') || $user->organization_id === $eventParticipant->organization_id,
            404,
        );
    }
}
