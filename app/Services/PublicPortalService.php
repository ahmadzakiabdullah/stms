<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class PublicPortalService
{
    public function __construct(
        private readonly RankingService $rankingService,
        private readonly PublicWeatherService $weatherService,
    ) {}

    public function data(?int $limit = 12): array
    {
        $session = $this->publicSession();
        if (! $session) {
            return $this->emptyData();
        }

        $cacheKey = 'public-portal:v4:'.$session->id.':'.($limit ?? 'all');

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($session, $limit): array {
            return $this->buildData($session, $limit);
        });
    }

    public function forget(?string $sessionId = null): void
    {
        if ($sessionId) {
            foreach ([12, 'all'] as $limit) {
                Cache::forget('public-portal:v2:'.$sessionId.':'.$limit);
                Cache::forget('public-portal:v3:'.$sessionId.':'.$limit);
                Cache::forget('public-portal:v4:'.$sessionId.':'.$limit);
            }

            return;
        }

        $session = $this->publicSession();
        if ($session) {
            $this->forget($session->id);
        }
    }

    public function forgetForOrganization(string $organizationId): void
    {
        $session = $this->publicSession();

        if ($session && $session->organization_id === $organizationId) {
            $this->forget($session->id);
        }
    }

    private function buildData(Session $session, ?int $limit): array
    {
        $organizationId = $session->organization_id;
        $tournamentIds = $session->tournaments()->pluck('id');
        $eventQuery = $session->events()->where('events.organization_id', $organizationId);

        $fixtureQuery = fn () => Fixture::query()->where('organization_id', $organizationId)
            ->whereHas('event', fn ($query) => $query->whereIn('tournament_id', $tournamentIds))
            ->with(['event.sport', 'pool:id,name', 'homeParticipant:id,name,team_name,logo_path,inverse_logo_path', 'awayParticipant:id,name,team_name,logo_path,inverse_logo_path', 'result']);

        $upcomingFixtures = $fixtureQuery()->whereIn('status', ['scheduled', 'in_progress'])
            ->orderByRaw('scheduled_at IS NULL, scheduled_at')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();
        $completedFixtures = $fixtureQuery()->where('status', 'completed')
            ->orderByDesc('scheduled_at')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();
        $upcoming = $upcomingFixtures->map(fn (Fixture $fixture) => $this->matchData($fixture))->values();
        $completed = $completedFixtures->map(fn (Fixture $fixture) => $this->matchData($fixture))->values();
        // Draw-generated fixtures may exist before the organizer assigns a date.
        // Keep them visible publicly as "to be determined" instead of hiding the
        // entire schedule; dated fixtures remain first.
        $playableCount = $fixtureQuery()->whereIn('status', ['scheduled', 'in_progress', 'completed'])->count();
        $lastUpdated = collect([$session->updated_at, $fixtureQuery()->max('updated_at')])->filter()->max();

        return [
            'app_name' => Setting::where('organization_id', $session->organization_id)->where('key', 'app_name')->value('value') ?? config('app.name'),
            'competition' => ['name' => $session->name, 'description' => $session->description,
                'start_date' => $session->start_date?->toDateString(), 'end_date' => $session->end_date?->toDateString(),
                'organization' => $session->organization?->name],
            'stats' => ['sports' => (clone $eventQuery)->distinct()->count('sport_id'), 'events' => (clone $eventQuery)->count(),
                'faculties' => Participant::query()->where('organization_id', $organizationId)->where('session_id', $session->id)->active()->count(),
                'completed_matches' => $completedFixtures->count(), 'total_matches' => $playableCount],
            'sports_catalog' => (clone $eventQuery)->with(['sport:id,name', 'sportCategory:id,name'])->get()
                ->groupBy('sport_id')->map(fn ($events) => [
                    'name' => $events->first()->sport?->name,
                    'events' => $events->map(fn ($event) => $event->name)->sort()->values()->all(),
                ])->filter(fn ($sport) => filled($sport['name']))->sortBy('name')->values()->all(),
            'sports' => (clone $eventQuery)->with('sport:id,name')->get()->pluck('sport.name')->filter()->unique()->sort()->values()->all(),
            'upcoming' => $upcoming->all(),
            'results' => $completed->all(),
            'medals' => ($limit === null
                ? $this->rankingService->calculateMedalTallyForSession($session)
                : $this->rankingService->calculateMedalTallyForSession($session)->take(20))->values()->all(),
            'contact' => [
                'address' => Setting::where('organization_id', $session->organization_id)
                    ->where('key', 'secretariat_address')->value('value'),
            ],
            'updated_at' => ($lastUpdated instanceof \DateTimeInterface ? $lastUpdated : now())->toIso8601String(),
            'weather' => $this->weatherService->current(),
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
        $participant = fn ($value) => $value ? [
            'name' => $value->name,
            'logo_url' => $value->logo_url,
            'inverse_logo_url' => $value->inverse_logo_url,
        ] : null;

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
            'contact' => ['address' => null], 'updated_at' => now()->toIso8601String(),
            'weather' => $this->weatherService->current()];
    }
}
