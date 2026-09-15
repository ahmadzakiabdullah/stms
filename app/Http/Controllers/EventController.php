<?php

namespace App\Http\Controllers;

use App\Actions\Events\CreateEvent;
use App\Actions\Events\DeleteEvent;
use App\Actions\Events\UpdateEvent;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        // Defensive queries to prevent 500 errors on production when the database
        // has not been fully migrated (common cause of /events 500).
        // The global BelongsToOrganization scope is still respected for non-super users.
        // See similar pattern in routes/web.php dashboard closure.
        $dataLoadFailed = false;

        $events = $this->safePaginatedQuery(function () {
            $query = Event::with(['tournament', 'sport', 'sportCategory', 'organization'])->withCount('pools');

            if ($tournamentId = request('tournament_id')) {
                $query->where('tournament_id', $tournamentId);
            }

            if (request()->has('is_active')) {
                $query->where('is_active', request('is_active'));
            }

            return $query->orderBy('start_date', 'desc')
                ->paginate(15)
                ->withQueryString();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;
            return new LengthAwarePaginator([], 0, 15, 1, [
                'path' => request()->url(),
            ]);
        });

        $tournaments = $this->safeCollectionQuery(function () {
            return Tournament::query()
                ->with('sports:id,name')
                ->orderBy('start_date', 'desc')
                ->get(['id', 'name', 'slug', 'start_date', 'end_date']);
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;
            return collect();
        });

        $sports = $this->safeCollectionQuery(function () {
            return Sport::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;
            return collect();
        });

        $categories = $this->safeCollectionQuery(function () {
            return SportCategory::query()
                ->with('sport')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'sport_id']);
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;
            return collect();
        });

        $usedCategoryIds = Event::query()
            ->select('tournament_id', 'sport_id', 'sport_category_id')
            ->get()
            ->groupBy(fn($e) => $e->tournament_id . ':' . $e->sport_id)
            ->map(fn($group) => $group->pluck('sport_category_id')->values()->toArray())
            ->toArray();

        $response = Inertia::render('Events/Index', [
            'events' => $events,
            'tournaments' => $tournaments,
            'sports' => $sports,
            'categories' => $categories,
            'usedCategoryIds' => $usedCategoryIds,
        ]);

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

        foreach ($events as $event) {
            try {
                $event->delete();
                $deleted++;
            } catch (\Throwable $e) {
                Log::error('Batch delete event failed', ['id' => $event->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('events.index')
            ->with('success', "{$deleted} events deleted successfully.");
    }
}
