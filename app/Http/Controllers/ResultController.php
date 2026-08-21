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
use App\Models\SquadMember;
use App\Notifications\MatchResultNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', Result::class);

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
                    'match.event.sport:id,name,scoring_mode',
                    'match.pool:id,name',
                    'match.homeParticipant:id,name,team_name,logo_path,inverse_logo_path',
                    'match.awayParticipant:id,name,team_name,logo_path,inverse_logo_path',
                    'winner:id,name,team_name',
                    'scoringEvents.squadMember:id,name',
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
                'homeParticipant:id,name,team_name,logo_path,inverse_logo_path',
                'awayParticipant:id,name,team_name,logo_path,inverse_logo_path',
                'event.sport:id,name,scoring_mode',
            ])
            ->whereDoesntHave('result')
            ->whereNotNull('home_participant_id')
            ->whereNotNull('away_participant_id')
            ->whereHas('event', function ($q) use ($sportIds) {
                $q->whereNull('deleted_at');

                if ($sportIds !== null) {
                    $q->whereIn('sport_id', $sportIds);
                }
            })
            ->orderBy(Event::select('name')->whereColumn('id', 'matches.event_id'))
            ->orderBy('match_number')
            ->get(['id', 'match_number', 'event_id', 'pool_id', 'round', 'stage', 'home_participant_id', 'away_participant_id', 'status', 'scheduled_at']);

        $scoringMembers = SquadMember::query()
            ->with('eventParticipant:id,event_id,participant_id')
            ->where('is_active', true)
            ->whereIn('role', SquadMember::ATHLETE_ROLES)
            ->whereHas('eventParticipant', fn ($query) => $query->where('status', 'confirmed'))
            ->get(['id', 'name', 'event_participant_id'])
            ->groupBy(fn ($member) => $member->eventParticipant?->event_id.'|'.$member->eventParticipant?->participant_id);

        $attachScoringMembers = function ($match) use ($scoringMembers) {
            if (($match->event?->sport?->scoring_mode ?? 'none') !== 'individual') {
                return $match;
            }

            $match->setAttribute('scoring_members', collect([$match->home_participant_id, $match->away_participant_id])
                ->filter()
                ->flatMap(fn ($participantId) => $scoringMembers->get($match->event_id.'|'.$participantId, collect()))
                ->map(fn ($member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'participant_id' => $member->eventParticipant?->participant_id,
                ])
                ->values());

            return $match;
        };

        $matches = $matches->map($attachScoringMembers)->values();
        $results->each(fn ($result) => $result->match && $attachScoringMembers($result->match));

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
            'canManage' => $user->hasAnyRole(['super-admin', 'org-admin', 'admin-sport']),
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

        $result = $action->handle(
            auth()->user()->organization,
            $request->validated()
        );

        $this->notifyMatchParticipants($result, 'recorded');

        return redirect()->route('results.index')
            ->with('success', 'Result recorded successfully.');
    }

    public function update(UpdateResultRequest $request, Result $result, UpdateResult $action): RedirectResponse
    {
        Gate::authorize('update', $result);

        $result = $action->handle(
            auth()->user()->organization,
            $result->id,
            $request->validated()
        );

        $this->notifyMatchParticipants($result, 'updated');

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

        $this->notifyMatchParticipants($result, 'removed');

        return redirect()->route('results.index')
            ->with('success', 'Result deleted successfully.');
    }

    private function notifyMatchParticipants(Result $result, string $action): void
    {
        $match = $result->match()->with(['event', 'homeParticipant', 'awayParticipant'])->first();
        $result->loadMissing('winner');

        if (! $match) {
            return;
        }

        $users = collect([$match->home_participant_id, $match->away_participant_id])
            ->filter()
            ->unique()
            ->flatMap(fn ($participantId) => Participant::find($participantId)?->users ?? collect())
            ->unique(fn ($user) => $user->getKey());

        foreach ($users as $user) {
            $user->notify(new MatchResultNotification($result, $action));
        }
    }
}
