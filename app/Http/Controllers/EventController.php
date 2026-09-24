<?php

namespace App\Http\Controllers;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\DeleteEvent;
use App\Actions\Events\UpdateEvent;
use App\Http\Requests\Event\BatchDestroyEventsRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Services\EventIndexService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request, EventIndexService $indexService): Response
    {
        Gate::authorize('viewAny', Event::class);

        $user = Auth::user();
        abort_unless($user, 401);

        $data = $indexService->dataFor($user, [
            'search' => $request->input('search'),
            'tournament_id' => $request->input('tournament_id'),
            'has_is_active' => $request->has('is_active'),
            'is_active' => $request->input('is_active'),
        ]);
        $dataLoadFailed = $data['dataLoadFailed'];
        unset($data['dataLoadFailed']);

        $response = Inertia::render('Events/Index', $data);

        if ($dataLoadFailed) {
            // Surface the real problem to the user/admin.
            // On prod this almost always means "the latest migrations have not been run".
            $response->with('error', 'Failed to load some data. Please run "php artisan migrate" on the server (database may be out of date).');
        }

        return $response;
    }

    public function store(StoreEventRequest $request, CreateEvent $action): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $action->handle($request->validated());

        return redirect()->route('events.index')
            ->with('success', 'Event created successfully.');
    }

    public function update(UpdateEventRequest $request, Event $event, UpdateEvent $action): RedirectResponse
    {
        Gate::authorize('update', $event);

        $action->handle($event, $request->validated());

        return redirect()->route('events.index')
            ->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event, DeleteEvent $action): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $action->handle($event);

        return redirect()->route('events.index')
            ->with('success', 'Event deleted successfully.');
    }

    public function batchDestroy(BatchDestroyEventsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $ids = array_values(array_unique($validated['ids']));
        $reason = trim($validated['reason']);
        $events = Event::whereIn('id', $ids)->get();

        if ($events->count() !== count($ids)) {
            return redirect()->route('events.index')
                ->with('error', 'Some selected events could not be found in your organization.');
        }

        if ($events->pluck('organization_id')->unique()->count() !== 1) {
            return redirect()->route('events.index')
                ->with('error', 'Bulk delete can only be applied to events from one organization at a time.');
        }

        foreach ($events as $event) {
            Gate::authorize('delete', $event);
        }

        $deleted = 0;

        try {
            DB::transaction(function () use ($events, $reason, $request, &$deleted) {
                $idsToDelete = $events->pluck('id')->toArray();
                $organizationId = (string) $events->first()->organization_id;
                $bulkActionId = (string) str()->uuid();

                // Perform a single query to soft delete all events, avoiding N soft-delete queries.
                Event::whereIn('id', $idsToDelete)->delete();
                $deleted = count($idsToDelete);

                // Spatie Activitylog automatically logs on 'deleted' model events.
                // Since we bypassed individual delete() calls, we use the facade to log manually.
                // This eliminates the N `SELECT` queries that Spatie otherwise executes.
                foreach ($events as $event) {
                    activity()
                        ->performedOn($event)
                        ->causedBy($request->user())
                        ->withProperties([
                            'bulk_action_id' => $bulkActionId,
                            'bulk_action' => 'events.batch_destroy',
                            'reason' => $reason,
                            'selected_count' => $deleted,
                        ])
                        ->event('deleted')
                        ->log('Event deleted by bulk action');
                }

                $organization = Organization::withoutGlobalScopes()->find($organizationId);
                if ($organization) {
                    activity()
                        ->performedOn($organization)
                        ->causedBy($request->user())
                        ->withProperties([
                            'bulk_action_id' => $bulkActionId,
                            'bulk_action' => 'events.batch_destroy',
                            'reason' => $reason,
                            'event_ids' => $idsToDelete,
                            'deleted_count' => $deleted,
                        ])
                        ->event('bulk_deleted')
                        ->log('Events bulk deleted');
                }
            });
        } catch (\Throwable $e) {
            Log::error('Batch delete events failed', ['error' => $e->getMessage()]);

            return redirect()->route('events.index')
                ->with('error', 'Failed to delete events due to a server error.');
        }

        if ($deleted === 0) {
            return redirect()->route('events.index')
                ->with('error', 'No events were deleted.');
        }

        return redirect()->route('events.index')
            ->with('success', "{$deleted} events deleted successfully.");
    }
}
