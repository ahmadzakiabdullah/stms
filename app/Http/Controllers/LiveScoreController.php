<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Sport;
use App\Services\LeagueTableService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiveScoreController extends Controller
{
    public function __construct(private readonly LeagueTableService $leagueTableService) {}

    /**
     * Public live scores / results board.
     *
     * No authentication is required. Data is scoped to the first active
     * organization so spectators never see data from other tenants.
     */
    public function index(Request $request): Response
    {
        $organization = Organization::query()->where('is_active', true)->orderBy('created_at')->first();
        $orgId = $organization?->id;

        $sportSlug = $request->string('sport')->toString();
        $eventSlug = $request->string('event')->toString();

        $events = Event::query()
            ->forOrganization($orgId)
            ->with('sport:id,name,slug')
            ->orderBy('name')
            ->get();

        $sports = Sport::query()
            ->forOrganization($orgId)
            ->whereIn('id', $events->pluck('sport_id')->unique()->values())
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        if ($sportSlug !== '') {
            $events = $events->filter(fn (Event $event) => $event->sport?->slug === $sportSlug)->values();
        }

        if ($eventSlug !== '') {
            $eventSlug = $events->firstWhere('slug', $eventSlug)?->slug ?? $eventSlug;
        }

        $matches = Fixture::query()
            ->forOrganization($orgId)
            ->where('status', 'completed')
            ->whereNotNull('home_participant_id')
            ->whereNotNull('away_participant_id')
            ->whereHas('result')
            ->with([
                'event:id,name,slug,sport_id',
                'event.sport:id,name,slug',
                'pool:id,name',
                'homeParticipant:id,name,team_name,logo_path',
                'awayParticipant:id,name,team_name,logo_path',
                'result',
            ])
            ->when($eventSlug !== '', fn ($q) => $q->whereHas('event', fn ($e) => $e->where('slug', $eventSlug)))
            ->when($eventSlug === '' && $sportSlug !== '', fn ($q) => $q->whereHas('event.sport', fn ($s) => $s->where('slug', $sportSlug)))
            ->orderByDesc('scheduled_at')
            ->orderBy('match_number')
            ->get();

        $standings = $events
            ->filter(fn (Event $event) => $eventSlug === '' || $event->slug === $eventSlug)
            ->values()
            ->map(function (Event $event) {
                $pools = $event->pools()
                    ->with(['eventParticipants' => fn ($q) => $q->with('participant:id,name,team_name,logo_path')])
                    ->orderBy('sort_order')
                    ->get();

                if ($pools->isEmpty()) {
                    return null;
                }

                return [
                    'event' => [
                        'id' => $event->id,
                        'name' => $event->name,
                        'slug' => $event->slug,
                        'sport' => $event->sport ? ['id' => $event->sport->id, 'name' => $event->sport->name, 'slug' => $event->sport->slug] : null,
                    ],
                    'pools' => $pools->values()->map(function ($pool) {
                        return [
                            'id' => $pool->id,
                            'name' => $pool->name,
                            'rows' => $this->leagueTableService->standings($pool)
                                ->map(function ($row) use ($pool) {
                                    $participant = $pool->eventParticipants->firstWhere('participant_id', $row['participant_id'])?->participant;

                                    return [
                                        ...$row,
                                        'participant' => $participant
                                            ? ['id' => $participant->id, 'name' => $participant->name, 'team_name' => $participant->team_name, 'logo_url' => $participant->logo_url]
                                            : null,
                                    ];
                                })->values(),
                        ];
                    }),
                ];
            })
            ->filter()
            ->values();

        return Inertia::render('Live/Index', [
            'organization' => $organization ? ['id' => $organization->id, 'name' => $organization->name] : null,
            'sports' => $sports,
            'events' => $events->map(fn (Event $event) => [
                'id' => $event->id,
                'name' => $event->name,
                'slug' => $event->slug,
                'sport_slug' => $event->sport?->slug,
            ])->values(),
            'matches' => $matches,
            'standings' => $standings,
            'filters' => [
                'sport' => $sportSlug,
                'event' => $eventSlug,
            ],
        ]);
    }
}
