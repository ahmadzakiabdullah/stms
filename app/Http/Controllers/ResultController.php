<?php

namespace App\Http\Controllers;

use App\Actions\Results\DeleteResult;
use App\Actions\Results\StoreResult;
use App\Actions\Results\UpdateResult;
use App\Http\Requests\Result\StoreResultRequest;
use App\Http\Requests\Result\UpdateResultRequest;
use App\Models\Event;
use App\Models\Fixture;
use App\Models\Participant;
use App\Models\Result;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    public function index(): Response
    {
        $dataLoadFailed = false;

        $user = auth()->user();
        $scopeToAdminSports = $user->hasRole('admin-sport') && ! $user->hasRole(['super-admin', 'org-admin']);
        $sportIds = $scopeToAdminSports ? $user->sports()->pluck('sports.id') : null;

        $results = $this->safeCollectionQuery(function () use ($sportIds) {
            $query = Result::query()
                ->join('matches', fn ($join) => $join->on('matches.id', '=', 'results.match_id')
                    ->whereNull('matches.deleted_at'))
                ->join('events', fn ($join) => $join->on('events.id', '=', 'matches.event_id')
                    ->whereNull('events.deleted_at'))
                ->with([
                    'match.event',
                    'match.pool:id,name',
                    'match.homeParticipant:id,name,team_name,logo_path',
                    'match.awayParticipant:id,name,team_name,logo_path',
                    'winner:id,name,team_name',
                ])
                ->select('results.*')
                ->orderBy('events.name')
                ->orderBy('matches.match_number');

            if ($sportIds !== null) {
                $query->whereIn('events.sport_id', $sportIds);
            }

            return $query->get();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;

            return collect();
        });

        $matches = Fixture::query()
            ->with([
                'event:id,name,sport_id',
                'pool:id,name',
                'homeParticipant:id,name,team_name,logo_path',
                'awayParticipant:id,name,team_name,logo_path',
            ])
            ->whereDoesntHave('result')
            ->whereNotNull('home_participant_id')
            ->whereNotNull('away_participant_id')
            ->when($sportIds !== null, fn ($q) => $q->whereHas('event', fn ($e) => $e->whereIn('sport_id', $sportIds)))
            ->orderBy(Event::select('name')->whereColumn('id', 'matches.event_id'))
            ->orderBy('match_number')
            ->get(['id', 'match_number', 'event_id', 'pool_id', 'round', 'stage', 'home_participant_id', 'away_participant_id', 'status', 'scheduled_at']);

        $events = Event::query()
            ->with('sport:id,name')
            ->when($sportIds !== null, fn ($q) => $q->whereIn('sport_id', $sportIds))
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'sport_id']);

        $response = Inertia::render('Results/Index', [
            'results' => $results,
            'matches' => $matches,
            'participants' => Participant::query()->active()->orderBy('name')->get(['id', 'name', 'slug']),
            'events' => $events,
        ]);

        if ($dataLoadFailed) {
            $response->with('error', 'Failed to load some data. Please run "php artisan migrate" on the server (database may be out of date).');
        }

        return $response;
    }

    public function store(StoreResultRequest $request, StoreResult $action): RedirectResponse
    {
        $sportId = Fixture::query()->with('event')
            ->findOrFail($request->validated('match_id'))
            ->event
            ->sport_id;

        Gate::authorize('create', [Result::class, $sportId]);

        $action->handle(
            auth()->user()->organization,
            $request->validated()
        );

        return redirect()->route('results.index')
            ->with('success', 'Result recorded successfully.');
    }

    public function update(UpdateResultRequest $request, Result $result, UpdateResult $action): RedirectResponse
    {
        Gate::authorize('update', $result);

        $action->handle(
            auth()->user()->organization,
            $result->id,
            $request->validated()
        );

        return redirect()->route('results.index')
            ->with('success', 'Result updated successfully.');
    }

    public function destroy(Result $result, DeleteResult $action): RedirectResponse
    {
        Gate::authorize('delete', $result);

        $action->handle(
            auth()->user()->organization,
            $result->id
        );

        return redirect()->route('results.index')
            ->with('success', 'Result deleted successfully.');
    }
}
