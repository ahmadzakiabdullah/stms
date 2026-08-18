<?php

namespace App\Http\Controllers;

use App\Actions\EventParticipants\UpdateEventParticipantStatus;
use App\Actions\Participants\BatchRegisterParticipantToEvents;
use App\Actions\Participants\RegisterParticipantToEvent;
use App\Http\Requests\EventParticipant\BatchRegisterEventParticipantsRequest;
use App\Http\Requests\EventParticipant\RegisterEventParticipantRequest;
use App\Http\Requests\EventParticipant\UpdateEventParticipantStatusRequest;
use App\Models\EventParticipant;
use App\Models\Participant;
use App\Models\SquadMember;
use App\Services\EventParticipantIndexService;
use App\Services\EventParticipantNotificationService;
use App\Services\SquadManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventParticipantController extends Controller
{
    public function index(Request $request, EventParticipantIndexService $indexService): Response
    {
        Gate::authorize('viewAny', EventParticipant::class);

        $user = Auth::user();
        abort_unless($user, 401);

        $data = $indexService->dataFor($user, $request->only([
            'search', 'sport_id', 'category_id', 'participant_id', 'status',
        ]));
        $dataLoadFailed = $data['dataLoadFailed'];
        unset($data['dataLoadFailed']);

        $response = Inertia::render('EventParticipants/Index', $data);

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
