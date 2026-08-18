<?php

namespace App\Http\Controllers;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\DeleteEvent;
use App\Actions\Events\UpdateEvent;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
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

    public function batchDestroy(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('events.index')
                ->with('error', 'No events selected.');
        }

        $events = Event::whereIn('id', $ids)->get();

        foreach ($events as $event) {
            Gate::authorize('delete', $event);
        }

        $deleted = 0;

        try {
            DB::transaction(function () use ($events, &$deleted) {
                $idsToDelete = $events->pluck('id')->toArray();

                // Perform a single query to soft delete all events, avoiding N soft-delete queries.
                Event::whereIn('id', $idsToDelete)->delete();
                $deleted = count($idsToDelete);

                // Spatie Activitylog automatically logs on 'deleted' model events.
                // Since we bypassed individual delete() calls, we use the facade to log manually.
                // This eliminates the N `SELECT` queries that Spatie otherwise executes.
                foreach ($events as $event) {
                    activity()
                        ->performedOn($event)
                        ->event('deleted')
                        ->log('deleted');
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
