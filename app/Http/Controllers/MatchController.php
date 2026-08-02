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
use App\Services\KnockoutStageService;
use App\Services\LeagueTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MatchController extends Controller
{
    public function __construct(
        private readonly LeagueTableService $leagueTableService,
        private readonly KnockoutStageService $knockoutStageService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        $scopeToAdminSports = $user->hasRole('admin-sport') && ! $user->hasRole(['super-admin', 'org-admin']);
        $sportIds = $scopeToAdminSports ? $user->sports()->pluck('sports.id') : null;

        $events = Event::query()
            ->with(['tournament:id,name', 'sport:id,name', 'sportCategory:id,name'])
            ->withCount('pools')
            ->when($sportIds !== null, fn ($q) => $q->whereIn('sport_id', $sportIds))
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
                        $q->with(['homeParticipant', 'awayParticipant', 'result'])
                            ->orderBy('round')
                            ->orderBy('match_number');
                    },
                ])
                ->orderBy('sort_order')
                ->get()
                ->map(function ($pool) {
                    $standings = $this->leagueTableService->standings($pool);

                    $participants = $pool->eventParticipants->pluck('participant');

                    return [
                        ...$pool->toArray(),
                        'standings' => $standings->map(function ($row) use ($participants) {
                            $participant = $participants->firstWhere('id', $row['participant_id']);

                            return [
                                ...$row,
                                'participant' => $participant
                                    ? ['id' => $participant->id, 'name' => $participant->name, 'team_name' => $participant->team_name, 'logo_url' => $participant->logo_url]
                                    : null,
                            ];
                        })->values(),
                        'has_standings' => $standings->isNotEmpty(),
                    ];
                });
        }

        $allFixtures = Fixture::query()
            ->join('events', fn ($join) => $join->on('events.id', '=', 'matches.event_id')
                ->whereNull('events.deleted_at'))
            ->with([
                'event:id,name,slug',
                'pool:id,name',
                'homeParticipant:id,name,team_name,logo_path',
                'awayParticipant:id,name,team_name,logo_path',
                'result',
            ])
            ->when($sportIds !== null, fn ($q) => $q->whereIn('events.sport_id', $sportIds))
            ->select('matches.*')
            ->orderBy('events.name')
            ->orderBy('matches.match_number')
            ->get();

        $knockout = [
            'has_stage' => $selectedEvent ? $this->knockoutStageService->hasKnockoutStage($selectedEvent) : false,
            'league_complete' => $selectedEvent ? $this->knockoutStageService->leagueComplete($selectedEvent) : false,
            'event_slug' => $selectedEvent?->slug,
            'fixtures' => $selectedEvent ? $this->knockoutStageService->fixtures($selectedEvent)->values() : [],
        ];

        $response = Inertia::render('Matches/Index', [
            'events' => $events,
            'drawnEventIds' => $drawnEventIds,
            'selectedEventId' => $selectedEvent?->id,
            'pools' => $pools,
            'allFixtures' => $allFixtures,
            'knockout' => $knockout,
            'participants' => Participant::query()->active()->orderBy('name')->get(['id', 'name', 'slug']),
            'canManage' => $user->hasAnyRole(['super-admin', 'org-admin', 'admin-sport']),
        ]);

        return $response;
    }

    public function store(StoreMatchRequest $request, StoreMatch $action): RedirectResponse
    {
        $event = Event::query()->findOrFail($request->validated('event_id'));

        Gate::authorize('create', [Fixture::class, $event->sport_id]);

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

    public function generateKnockout(Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        try {
            $count = $this->knockoutStageService->generate($event);

            activity()
                ->performedOn($event)
                ->causedBy(auth()->user())
                ->event('generate_knockout')
                ->withProperties(['fixtures' => $count])
                ->log("Knockout stage generated for '{$event->name}': {$count} fixtures");

            return redirect()->back()->with('success', "Knockout stage generated: {$count} fixtures created.");
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
