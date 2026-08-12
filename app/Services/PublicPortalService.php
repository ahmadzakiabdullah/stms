<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Setting;

class PublicPortalService
{
    public function __construct(private readonly RankingService $rankingService) {}

    public function data(?int $limit = 12): array
    {
        $session = $this->publicSession();
        if (! $session) {
            return $this->emptyData();
        }

        $organizationId = $session->organization_id;
        $tournamentIds = $session->tournaments()->pluck('id');
        $eventQuery = $session->events()->where('events.organization_id', $organizationId);
        $fixtures = Fixture::query()->where('organization_id', $organizationId)
            ->whereHas('event', fn ($query) => $query->whereIn('tournament_id', $tournamentIds))
            ->with(['event.sport', 'pool:id,name', 'homeParticipant:id,name,team_name,logo_path', 'awayParticipant:id,name,team_name,logo_path', 'result'])
            ->orderBy('scheduled_at')->get();
        $matches = $fixtures->map(fn (Fixture $fixture) => $this->matchData($fixture));
        $completed = $matches->where('status', 'completed')->sortByDesc('scheduled_at')->values();
        // Draw-generated fixtures may exist before the organizer assigns a date.
        // Keep them visible publicly as "to be determined" instead of hiding the
        // entire schedule; dated fixtures remain first.
        $upcoming = $matches->whereIn('status', ['scheduled', 'in_progress'])
            ->sortBy(fn (array $match) => $match['scheduled_at'] ?? '9999-12-31T23:59:59Z')
            ->values();
        $playable = $matches->whereIn('status', ['scheduled', 'in_progress', 'completed']);
        $lastUpdated = collect([$session->updated_at])
            ->concat($fixtures->map(fn (Fixture $fixture) => $fixture->updated_at))
            ->concat($fixtures->map(fn (Fixture $fixture) => $fixture->result?->updated_at))
            ->filter()
            ->max(fn ($value) => $value->getTimestamp());

        return [
            'app_name' => Setting::where('organization_id', $session->organization_id)->where('key', 'app_name')->value('value') ?? config('app.name'),
            'competition' => ['name' => $session->name, 'description' => $session->description,
                'start_date' => $session->start_date?->toDateString(), 'end_date' => $session->end_date?->toDateString(),
                'organization' => $session->organization?->name],
            'stats' => ['sports' => (clone $eventQuery)->distinct()->count('sport_id'), 'events' => (clone $eventQuery)->count(),
                'faculties' => Participant::query()->where('organization_id', $organizationId)->where('session_id', $session->id)->active()->count(),
                'completed_matches' => $completed->count(), 'total_matches' => $playable->count()],
            'sports_catalog' => (clone $eventQuery)->with(['sport:id,name', 'sportCategory:id,name'])->get()
                ->groupBy('sport_id')->map(fn ($events) => [
                    'name' => $events->first()->sport?->name,
                    'events' => $events->map(fn ($event) => $event->name)->sort()->values(),
                ])->filter(fn ($sport) => filled($sport['name']))->sortBy('name')->values(),
            'sports' => (clone $eventQuery)->with('sport:id,name')->get()->pluck('sport.name')->filter()->unique()->sort()->values(),
            'upcoming' => ($limit === null ? $upcoming : $upcoming->take($limit))->values(),
            'results' => ($limit === null ? $completed : $completed->take($limit))->values(),
            'medals' => ($limit === null
                ? $this->rankingService->calculateMedalTallyForSession($session)
                : $this->rankingService->calculateMedalTallyForSession($session)->take(20))->values(),
            'contact' => [
                'address' => Setting::where('organization_id', $session->organization_id)
                    ->where('key', 'secretariat_address')->value('value'),
            ],
            'updated_at' => ($lastUpdated ? now()->setTimestamp($lastUpdated) : now())->toIso8601String(),
        ];
    }

    private function publicSession(): ?Session
    {
        $organizationSlug = config('app.public_org_slug');
        $sessionSlug = config('app.public_session_slug');

        if (! $organizationSlug) {
            return null;
        }

        $organization = Organization::query()->active()
            ->where('slug', $organizationSlug)->first();

        if (! $organization) {
            return null;
        }

        return Session::query()->with('organization:id,name')
            ->where('organization_id', $organization->id)
            ->when($sessionSlug, fn ($query) => $query->where('slug', $sessionSlug), fn ($query) => $query->active())
            ->orderByDesc('start_date')->first();
    }

    private function matchData(Fixture $fixture): array
    {
        $participant = fn ($value) => $value ? ['name' => $value->name, 'logo_url' => $value->logo_url] : null;

        return ['id' => $fixture->id, 'sport' => $fixture->event?->sport?->name, 'event' => $fixture->event?->name,
            'stage' => $fixture->stage, 'round' => $fixture->round, 'group' => $fixture->pool?->name,
            'match_number' => $fixture->match_number, 'scheduled_at' => $fixture->scheduled_at?->toIso8601String(),
            'venue' => $fixture->venue, 'status' => $fixture->status, 'home' => $participant($fixture->homeParticipant),
            'away' => $participant($fixture->awayParticipant), 'score_home' => $fixture->result?->score_home, 'score_away' => $fixture->result?->score_away];
    }

    private function emptyData(): array
    {
        return ['app_name' => config('app.name'), 'competition' => null, 'stats' => ['sports' => 0, 'events' => 0, 'faculties' => 0, 'completed_matches' => 0, 'total_matches' => 0],
            'sports' => [], 'sports_catalog' => [], 'upcoming' => [], 'results' => [], 'medals' => [],
            'contact' => ['address' => null], 'updated_at' => now()->toIso8601String()];
    }
}
