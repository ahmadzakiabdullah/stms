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

        $cacheKey = 'public-portal:v8:'.$session->id.':'.($limit ?? 'all');

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
                Cache::forget('public-portal:v5:'.$sessionId.':'.$limit);
                Cache::forget('public-portal:v6:'.$sessionId.':'.$limit);
                Cache::forget('public-portal:v7:'.$sessionId.':'.$limit);
                Cache::forget('public-portal:v8:'.$sessionId.':'.$limit);
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
        $portalSettings = Setting::query()
            ->where('organization_id', $organizationId)
            ->whereIn('key', [
                'app_name',
                'secretariat_address',
                'secretariat_email',
                'secretariat_phone',
                'secretariat_facebook_url',
                'secretariat_instagram_url',
                'secretariat_tiktok_url',
                'secretariat_youtube_url',
            ])
            ->pluck('value', 'key')
            ->all();
        $tournamentIds = $session->tournaments()->pluck('id');
        $eventQuery = $session->events()->where('events.organization_id', $organizationId);

        $fixtureQuery = fn () => Fixture::query()->where('organization_id', $organizationId)
            ->whereHas('event', fn ($query) => $query->whereIn('tournament_id', $tournamentIds))
            ->with(['event.sport', 'event.sportCategory', 'pool:id,name', 'homeParticipant:id,name,team_name,logo_path,inverse_logo_path', 'awayParticipant:id,name,team_name,logo_path,inverse_logo_path', 'result']);

        $upcomingFixtures = $fixtureQuery()->whereIn('status', ['scheduled', 'in_progress'])
            ->orderByRaw('scheduled_at IS NULL')
            ->orderBy('scheduled_at')
            ->orderBy('match_number')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();
        $completedFixtures = $fixtureQuery()->where('status', 'completed')
            ->orderByDesc('scheduled_at')
            ->orderBy('match_number')
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
            'app_name' => filled($portalSettings['app_name'] ?? null) ? $portalSettings['app_name'] : config('app.name'),
            'competition' => ['name' => $session->name, 'description' => $session->description,
                'start_date' => $session->start_date?->toDateString(), 'end_date' => $session->end_date?->toDateString(),
                'organization' => $session->organization?->name],
            'stats' => ['sports' => (clone $eventQuery)->distinct()->count('sport_id'), 'events' => (clone $eventQuery)->count(),
                'faculties' => Participant::query()->where('organization_id', $organizationId)->where('session_id', $session->id)->active()->count(),
                'completed_matches' => $completedFixtures->count(), 'total_matches' => $playableCount],
            'sports_catalog' => (clone $eventQuery)->with(['sport:id,name', 'sportCategory:id,name'])->get()
                ->groupBy('sport_id')->map(fn ($events) => [
                    'name' => $events->first()->sport?->name,
                    'categories' => $events->map(fn ($event) => $event->sportCategory?->name)->filter()->unique()->sort()->values()->all(),
                    'events' => $events->map(fn ($event) => [
                        'name' => $event->name,
                        'category' => $event->sportCategory?->name,
                    ])->sortBy('name')->values()->all(),
                ])->filter(fn ($sport) => filled($sport['name']))->sortBy('name')->values()->all(),
            'sports' => (clone $eventQuery)->with('sport:id,name')->get()->pluck('sport.name')->filter()->unique()->sort()->values()->all(),
            'faculties' => Participant::query()->where('organization_id', $organizationId)->where('session_id', $session->id)->active()
                ->orderBy('name')->get(['id', 'name', 'logo_path', 'inverse_logo_path'])->map(fn (Participant $participant) => [
                    'name' => $participant->name, 'logo_url' => $participant->logo_url, 'inverse_logo_url' => $participant->inverse_logo_url,
                ])->values()->all(),
            'venues' => collect([
                ...(clone $eventQuery)->pluck('venues')->flatten()->filter()->all(),
                ...$fixtureQuery()->whereNotNull('venue')->where('venue', '!=', '')->get(['venue'])->pluck('venue')->all(),
            ])->unique()->sort()->values()->all(),
            'upcoming' => $upcoming->all(),
            'results' => $completed->all(),
            'medals' => ($limit === null
                ? $this->rankingService->calculateMedalTallyForSession($session)
                : $this->rankingService->calculateMedalTallyForSession($session)->take(20))->values()->all(),
            'contact' => $this->contactData($portalSettings),
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
        $eventVenues = $fixture->event?->venues ?? [];

        return ['id' => $fixture->id, 'sport' => $fixture->event?->sport?->name, 'event' => $fixture->event?->name,
            'category' => $fixture->event?->sportCategory?->name, 'stage' => $fixture->stage, 'round' => $fixture->round, 'group' => $fixture->pool?->name,
            'match_number' => $fixture->match_number, 'scheduled_at' => $fixture->scheduled_at?->toIso8601String(),
            'venue' => $fixture->venue ?: ($eventVenues[0] ?? null), 'status' => $fixture->status, 'home' => $participant($fixture->homeParticipant),
            'away' => $participant($fixture->awayParticipant), 'score_home' => $fixture->result?->score_home, 'score_away' => $fixture->result?->score_away];
    }

    private function emptyData(): array
    {
        return ['app_name' => config('app.name'), 'competition' => null, 'stats' => ['sports' => 0, 'events' => 0, 'faculties' => 0, 'completed_matches' => 0, 'total_matches' => 0],
            'sports' => [], 'sports_catalog' => [], 'faculties' => [], 'venues' => [], 'upcoming' => [], 'results' => [], 'medals' => [],
            'contact' => $this->contactData([]), 'updated_at' => now()->toIso8601String(),
            'weather' => $this->weatherService->current()];
    }

    private function contactData(array $settings): array
    {
        $value = static function (string $key) use ($settings): ?string {
            $candidate = trim((string) ($settings[$key] ?? ''));

            return $candidate !== '' ? $candidate : null;
        };

        $email = $value('secretariat_email');
        $phone = $value('secretariat_phone');

        return [
            'address' => $value('secretariat_address'),
            'email' => $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null,
            'phone' => $phone && preg_match('/^\+?[0-9\s().-]{7,50}$/', $phone) === 1 ? $phone : null,
            'social' => [
                'facebook' => $this->safeHttpUrl($value('secretariat_facebook_url')),
                'instagram' => $this->safeHttpUrl($value('secretariat_instagram_url')),
                'tiktok' => $this->safeHttpUrl($value('secretariat_tiktok_url')),
                'youtube' => $this->safeHttpUrl($value('secretariat_youtube_url')),
            ],
        ];
    }

    private function safeHttpUrl(?string $value): ?string
    {
        if (! $value || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)
            ? $value
            : null;
    }
}
