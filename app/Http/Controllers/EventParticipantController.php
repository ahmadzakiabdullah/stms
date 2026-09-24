<?php

namespace App\Http\Controllers;

use App\Actions\EventParticipants\UpdateEventParticipantStatus;
use App\Actions\Participants\BatchRegisterParticipantToEvents;
use App\Actions\Participants\RegisterParticipantToEvent;
use App\Http\Requests\EventParticipant\BatchRegisterEventParticipantsRequest;
use App\Http\Requests\EventParticipant\BatchUpdateEventParticipantStatusRequest;
use App\Http\Requests\EventParticipant\EventParticipantImportRequest;
use App\Http\Requests\EventParticipant\RegisterEventParticipantRequest;
use App\Http\Requests\EventParticipant\UpdateEventParticipantStatusRequest;
use App\Http\Requests\EventParticipant\WithdrawEventParticipantRequest;
use App\Imports\EventParticipantImport;
use App\Models\DataTransfer;
use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\SquadMember;
use App\Services\DataTransferService;
use App\Services\EventParticipantIndexService;
use App\Services\SquadManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventParticipantController extends Controller
{
    public function registerEvents(Request $request, EventParticipantIndexService $indexService): Response
    {
        return $this->index($request, $indexService);
    }

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

    public function store(RegisterEventParticipantRequest $request, RegisterParticipantToEvent $action): RedirectResponse
    {
        $user = Auth::user();
        $isWakil = $user->hasRole('faculty-representative') && $user->participant_id;

        $eventId = $request->input('event_id');
        $participant = Participant::findOrFail($request->participantId());

        try {
            $ep = $action->handle($participant, $eventId);
        } catch (ValidationException $e) {
            return redirect()->route($isWakil ? 'dashboard' : 'event-participants.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route($isWakil ? 'dashboard' : 'event-participants.index')
            ->with('success', 'Participant registered to event successfully.');
    }

    public function storeBatch(BatchRegisterEventParticipantsRequest $request, BatchRegisterParticipantToEvents $action): RedirectResponse
    {
        $user = Auth::user();
        $isWakil = $user->hasRole('faculty-representative') && $user->participant_id;

        $participant = Participant::findOrFail($request->participantId());

        $validated = $request->validated();

        $result = $action->handle($participant, $validated['event_ids']);
        ['registered' => $registered, 'failures' => $failures, 'created' => $created] = $result;
        $bulkActionId = (string) str()->uuid();
        activity()
            ->performedOn($participant)
            ->causedBy($user)
            ->event('bulk_registered')
            ->withProperties([
                'bulk_action_id' => $bulkActionId,
                'bulk_action' => 'event_participants.batch_register',
                'selected_count' => $result['selected_count'],
                'registered_count' => $registered,
                'skipped_count' => $result['skipped_count'],
                'event_ids' => $result['event_ids'],
                'created_ids' => $created->pluck('id')->values()->all(),
                'failures' => $failures,
            ])
            ->log('Participant registered to events by bulk action');

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
        $validated = $request->validated();

        try {
            $action->handle($eventParticipant, $validated['status'], $validated['notes'] ?? null);
        } catch (ValidationException $e) {
            return redirect()->route('event-participants.index')
                ->with('error', $e->getMessage());
        }

        return redirect()->route('event-participants.index', collect($request->query())->only([
            'search', 'sport_id', 'category_id', 'participant_id', 'status',
        ])->filter(fn ($v) => $v !== null && $v !== '')->all())->with('success', $validated['status'] === 'confirmed'
            ? 'Registration approved.'
            : 'Registration rejected.');
    }

    public function batchUpdateStatus(BatchUpdateEventParticipantStatusRequest $request, UpdateEventParticipantStatus $action): RedirectResponse
    {
        $validated = $request->validated();
        $status = $validated['status'];
        $notes = $validated['notes'] ?? null;
        $ids = array_values(array_unique($validated['ids']));
        $bulkActionId = (string) str()->uuid();
        $visibleRegistrations = EventParticipant::whereIn('id', $ids)->get();

        if ($visibleRegistrations->pluck('organization_id')->unique()->count() > 1) {
            return redirect()->route('event-participants.index', collect($request->query())->only([
                'search', 'sport_id', 'category_id', 'participant_id', 'status',
            ])->filter(fn ($v) => $v !== null && $v !== '')->all())
                ->with('error', 'Bulk status updates can only be applied to registrations from one organization at a time.');
        }

        $updated = 0;
        $failures = [];
        $updatedIds = [];

        foreach ($ids as $id) {
            $eventParticipant = EventParticipant::find($id);

            if (! $eventParticipant || ! Gate::allows('update', $eventParticipant)) {
                continue;
            }

            try {
                $action->handle($eventParticipant, $status, $notes);
                $updated++;
                $updatedIds[] = $eventParticipant->id;
                activity()
                    ->performedOn($eventParticipant)
                    ->causedBy($request->user())
                    ->event('bulk_status_updated')
                    ->withProperties([
                        'bulk_action_id' => $bulkActionId,
                        'bulk_action' => 'event_participants.batch_status',
                        'target_status' => $status,
                        'notes' => $notes,
                    ])
                    ->log('Event registration status updated by bulk action');
            } catch (ValidationException $e) {
                $failures[] = $e->getMessage();
            }
        }

        $organizationId = $visibleRegistrations->first()?->organization_id ?? $request->user()?->organization_id;
        $organization = $organizationId ? Organization::withoutGlobalScopes()->find($organizationId) : null;
        if ($organization) {
            activity()
                ->performedOn($organization)
                ->causedBy($request->user())
                ->event('bulk_status_updated')
                ->withProperties([
                    'bulk_action_id' => $bulkActionId,
                    'bulk_action' => 'event_participants.batch_status',
                    'target_status' => $status,
                    'notes' => $notes,
                    'selected_count' => count($ids),
                    'updated_count' => $updated,
                    'skipped_count' => max(0, count($ids) - $updated),
                    'updated_ids' => $updatedIds,
                    'failures' => $failures,
                ])
                ->log('Event registration bulk status update');
        }

        $verdict = $status === 'confirmed' ? 'approved' : 'rejected';
        $successMessage = "{$updated} registration(s) {$verdict}.";
        $errorMessage = count($failures) > 0 ? implode(' ', $failures) : null;

        $response = redirect()->route('event-participants.index', collect($request->query())->only([
            'search', 'sport_id', 'category_id', 'participant_id', 'status',
        ])->filter(fn ($v) => $v !== null && $v !== '')->all())
            ->with('success', $successMessage);

        if ($errorMessage) {
            $response->with('error', $errorMessage);
        }

        return $response;
    }

    public function withdraw(WithdrawEventParticipantRequest $request, EventParticipant $eventParticipant, UpdateEventParticipantStatus $action): RedirectResponse
    {
        try {
            $action->handle($eventParticipant, EventParticipant::STATUS_WITHDRAWN);
        } catch (ValidationException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Registration withdrawn.');
    }

    public function import(EventParticipantImportRequest $request): RedirectResponse
    {
        $participant = Participant::findOrFail($request->participantId());
        $import = new EventParticipantImport($participant, $participant->organization_id);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            Log::error('Event participant import failed', ['participant_id' => $participant->id, 'error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Failed to import the file.');
        }

        $created = $import->createdCount();
        $errors = $import->errors();
        activity()
            ->performedOn($participant)
            ->causedBy($request->user())
            ->event('bulk_imported')
            ->withProperties([
                'bulk_action_id' => (string) str()->uuid(),
                'bulk_action' => 'event_participants.import',
                'selected_count' => $created + count($errors),
                'created_count' => $created,
                'error_count' => count($errors),
                'failures' => $errors,
            ])
            ->log('Event registrations imported by bulk action');

        $response = redirect()->back();

        if ($created > 0) {
            $response->with('success', "{$created} registration(s) imported successfully.");
        }

        if (count($errors) > 0) {
            $summary = $created > 0
                ? "{$created} registration(s) created with ".count($errors).' error(s):'
                : 'No registrations were created:';
            $response->with('error', $summary.' '.implode(' ', $errors));
        } elseif ($created === 0) {
            $response->with('error', 'No registrations were created.');
        }

        return $response;
    }

    public function queueImport(EventParticipantImportRequest $request, DataTransferService $transfers): JsonResponse|RedirectResponse
    {
        $participant = Participant::where('organization_id', $request->user()->organization_id)
            ->findOrFail($request->participantId());
        $contents = file_get_contents($request->file('file')->getRealPath());
        abort_unless(is_string($contents), 422, 'The import file could not be read.');
        $idempotencyKey = 'event-participant-import:'.sha1($participant->id.'|'.$contents);
        $sourcePath = $request->file('file')->store('transfers/'.$request->user()->organization_id.'/input', 'local');
        $transfer = $transfers->queue(
            $request->user(),
            DataTransfer::TYPE_IMPORT_EVENT_PARTICIPANTS,
            ['participant_id' => $participant->id],
            $sourcePath,
            $idempotencyKey,
        );

        if ($request->expectsJson()) {
            return response()->json(['data' => $transfers->status($transfer)], $transfer->wasRecentlyCreated ? 202 : 200);
        }

        if ($transfer->isSuccessful()) {
            $response = redirect()->back();
            if ($transfer->processed > 0) {
                $response->with('success', "{$transfer->processed} registration(s) imported successfully.");
            }
            if (count($transfer->failure_report ?? []) > 0) {
                $response->with('error', 'Import completed with errors: '.implode(' ', $transfer->failure_report));
            }

            return $response;
        }

        return redirect()->back()->with('success', 'Event registration import queued. Track progress using transfer '.$transfer->id.'.');
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        Gate::authorize('create', EventParticipant::class);

        return response()->streamDownload(function () {
            echo "event_name\n";
            echo "Badminton - Singles\n";
            echo "Football - Team\n";
        }, 'event-registrations-template.csv', ['Content-Type' => 'text/csv']);
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
