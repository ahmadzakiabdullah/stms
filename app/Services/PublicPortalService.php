<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Session;
use App\Models\Setting;
use App\Models\SquadMember;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PublicPortalService
{
    private const ATHLETES_PER_PAGE = 24;

    private const TEAMS_PER_PAGE = 12;

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

        $cacheKey = 'public-portal:v11:'.$session->id.':'.($limit ?? 'all');

        return Cache::flexible($cacheKey, [120, 600], function () use ($session, $limit): array {
            return $this->buildData($session, $limit);
        });
    }

    /**
     * Lightweight shared context for public sub-pages that do not need the
     * homepage dashboard payload (fixtures, medals, weather, contact data).
     */
    public function publicContext(): array
    {
        $session = $this->publicSession();
        if (! $session) {
            return ['app_name' => config('app.name'), 'session_branding' => ['logo_url' => null, 'inverse_logo_url' => null], 'competition' => null];
        }

        $appName = Setting::query()
            ->where('organization_id', $session->organization_id)
            ->where('key', 'app_name')
            ->value('value');

        return [
            'app_name' => filled($appName) ? $appName : config('app.name'),
            'session_branding' => [
                'logo_url' => $session->logo_url,
                'inverse_logo_url' => $session->inverse_logo_url,
            ],
            'competition' => [
                'name' => $session->name,
                'organization' => $session->organization?->name,
                'start_date' => $session->start_date?->toDateString(),
                'end_date' => $session->end_date?->toDateString(),
            ],
        ];
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
                Cache::forget('public-portal:v9:'.$sessionId.':'.$limit);
                Cache::forget('public-portal:v10:'.$sessionId.':'.$limit);
                Cache::forget('public-portal:v11:'.$sessionId.':'.$limit);
            }
            Cache::forget('public-athletes:v1:'.$sessionId);
            Cache::forget('public-athletes:v2:'.$sessionId);

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

    public function athleteDirectory(array $filters = []): array
    {
        $view = ($filters['view'] ?? 'teams') === 'athletes' ? 'athletes' : 'teams';
        $q = trim((string) ($filters['q'] ?? ''));
        $sport = trim((string) ($filters['sport'] ?? ''));
        $category = trim((string) ($filters['category'] ?? ''));
        $faculty = trim((string) ($filters['faculty'] ?? ''));
        $letter = mb_strtoupper(trim((string) ($filters['letter'] ?? '')));
        $sort = (string) ($filters['sort'] ?? 'name');
        $page = max(1, (int) ($filters['page'] ?? 1));

        $session = $this->publicSession();
        if (! $session) {
            return $this->emptyAthleteDirectory($view, $q, $sport, $category, $faculty, $letter, $sort);
        }

        $base = Cache::flexible('public-athletes:v2:'.$session->id, [120, 600], fn (): array => $this->buildAthleteDirectory($session));

        $athletes = $this->sortRows($this->filterAthleteRows($base['athletes'], $q, $sport, $category, $faculty, $letter), $sort);
        $rosters = $this->sortRows($this->filterRosterRows($base['rosters'], $q, $sport, $category, $faculty, $letter), $sort);

        $perPage = $view === 'athletes' ? self::ATHLETES_PER_PAGE : self::TEAMS_PER_PAGE;
        $total = $view === 'athletes' ? count($athletes) : count($rosters);

        $paginator = new LengthAwarePaginator(
            array_slice($view === 'athletes' ? $athletes : $rosters, ($page - 1) * $perPage, $perPage),
            $total,
            $perPage,
            $page,
            [
                'path' => route('public.athletes'),
                'query' => array_filter([
                    'view' => $view === 'athletes' ? 'athletes' : null,
                    'q' => $q !== '' ? $q : null,
                    'sport' => $sport !== '' ? $sport : null,
                    'category' => $category !== '' ? $category : null,
                    'faculty' => $faculty !== '' ? $faculty : null,
                    'letter' => $letter !== '' ? $letter : null,
                    'sort' => $sort !== 'name' ? $sort : null,
                ], fn ($value) => $value !== null),
            ],
        );

        return [
            'view' => $view,
            'filters' => ['q' => $q, 'sport' => $sport, 'category' => $category, 'faculty' => $faculty, 'letter' => $letter, 'sort' => $sort],
            'letters' => $this->availableLetters($base['athletes'], $q, $sport, $category, $faculty),
            'athletes' => $view === 'athletes' ? $paginator : null,
            'rosters' => $view === 'teams' ? $paginator : null,
            'counts' => ['teams' => count($rosters), 'athletes' => count($athletes)],
            'faculties' => $base['faculties'],
            'sports' => $base['sports'],
            'categories' => $base['categories'],
            'stats' => $base['stats'],
            'updated_at' => $base['updated_at'],
        ];
    }

    private function buildAthleteDirectory(Session $session): array
    {
        $tournamentIds = $session->tournaments()->pluck('id');
        $registrations = EventParticipant::query()
            ->where('organization_id', $session->organization_id)
            ->where('status', 'confirmed')
            ->whereHas('participant', fn ($query) => $query->where('session_id', $session->id)->where('is_active', true))
            ->whereHas('event', fn ($query) => $query->whereIn('tournament_id', $tournamentIds)->where('organization_id', $session->organization_id))
            ->with([
                'participant:id,name,logo_path,inverse_logo_path',
                'event:id,name,sport_id,sport_category_id',
                'event.sport:id,name',
                'event.sportCategory:id,name',
                'squadMembers' => fn ($query) => $query->where('is_active', true)->ordered(),
            ])->get();

        $rosters = $registrations->groupBy('participant_id')->map(function ($entries) {
            $participant = $entries->first()->participant;
            $members = $entries->flatMap->squadMembers
                ->unique(fn (SquadMember $member) => $member->name.'|'.$member->role)
                ->sortBy(fn (SquadMember $member) => $member->role === 'athlete_male' || $member->role === 'athlete_female' ? '1'.$member->name : '0'.$member->name)
                ->values();

            return [
                'id' => $participant?->id,
                'name' => $participant?->name,
                'logo_url' => $participant?->logo_url,
                'inverse_logo_url' => $participant?->inverse_logo_url,
                'events' => $entries->map(fn (EventParticipant $entry) => [
                    'name' => $entry->event?->name,
                    'sport' => $entry->event?->sport?->name,
                    'category' => $entry->event?->sportCategory?->name,
                ])->unique(fn (array $event) => implode('|', [$event['name'], $event['sport'], $event['category']]))->sortBy('name')->values()->all(),
                'members' => $members->map(fn (SquadMember $member) => [
                    'name' => $member->name,
                    'role' => $member->role,
                ])->values()->all(),
            ];
        })->filter(fn (array $roster) => filled($roster['name']))->sortBy('name')->values();

        $members = $rosters->flatMap(fn (array $roster) => $roster['members']);
        $athletes = $registrations->flatMap(function (EventParticipant $entry) {
            $participant = $entry->participant;

            return $entry->squadMembers->whereIn('role', SquadMember::ATHLETE_ROLES)->map(fn (SquadMember $member) => [
                'id' => $member->id,
                'key' => $participant?->id.'|'.$member->name,
                'name' => $member->name,
                'faculty' => $participant?->name,
                'faculty_logo_url' => $participant?->logo_url,
                'faculty_inverse_logo_url' => $participant?->inverse_logo_url,
                'events' => [[
                    'name' => $entry->event?->name,
                    'sport' => $entry->event?->sport?->name,
                    'category' => $entry->event?->sportCategory?->name,
                ]],
            ]);
        })->groupBy('key')->map(function ($entries) {
            $athlete = $entries->first();

            return [
                'id' => $athlete['id'],
                'name' => $athlete['name'],
                'faculty' => $athlete['faculty'],
                'faculty_logo_url' => $athlete['faculty_logo_url'],
                'faculty_inverse_logo_url' => $athlete['faculty_inverse_logo_url'],
                'events' => $entries->flatMap(fn (array $entry) => $entry['events'])->unique(fn (array $event) => implode('|', [$event['name'], $event['sport'], $event['category']]))->sortBy('name')->values()->all(),
            ];
        })->sortBy('name')->values();

        return [
            'rosters' => $rosters->all(),
            'athletes' => $athletes->all(),
            'faculties' => $athletes->pluck('faculty')->filter()->unique()->sort()->values()->all(),
            'sports' => $registrations->map(fn (EventParticipant $entry) => $entry->event?->sport?->name)->filter()->unique()->sort()->values()->all(),
            'categories' => $registrations->map(fn (EventParticipant $entry) => $entry->event?->sportCategory?->name)->filter()->unique()->sort()->values()->all(),
            'stats' => [
                'teams' => $rosters->count(),
                'athletes' => $athletes->count(),
                'officials' => $members->whereIn('role', SquadMember::OFFICIAL_ROLES)->unique('name')->count(),
            ],
            'updated_at' => ($registrations->max('updated_at') ?: $session->updated_at)->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $athletes
     * @return array<int, array<string, mixed>>
     */
    private function filterAthleteRows(array $athletes, string $q, string $sport, string $category, string $faculty, string $letter): array
    {
        $needle = mb_strtolower($q);

        return array_values(array_filter($athletes, function (array $athlete) use ($needle, $sport, $category, $faculty, $letter): bool {
            if ($needle !== '') {
                $haystack = array_merge(
                    [$athlete['name'] ?? null, $athlete['faculty'] ?? null],
                    array_map(fn (array $event) => $event['name'] ?? null, $athlete['events'] ?? []),
                );

                if (! $this->containsNeedle($haystack, $needle)) {
                    return false;
                }
            }

            if ($faculty !== '' && (string) ($athlete['faculty'] ?? '') !== $faculty) {
                return false;
            }

            return $this->matchesEventFilters($athlete['events'] ?? [], $sport, $category)
                && $this->matchesLetter((string) ($athlete['name'] ?? ''), $letter);
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rosters
     * @return array<int, array<string, mixed>>
     */
    private function filterRosterRows(array $rosters, string $q, string $sport, string $category, string $faculty, string $letter): array
    {
        $needle = mb_strtolower($q);

        return array_values(array_filter($rosters, function (array $roster) use ($needle, $sport, $category, $faculty, $letter): bool {
            if ($needle !== '') {
                $haystack = array_merge(
                    [$roster['name'] ?? null],
                    array_map(fn (array $member) => $member['name'] ?? null, $roster['members'] ?? []),
                    array_map(fn (array $event) => $event['name'] ?? null, $roster['events'] ?? []),
                );

                if (! $this->containsNeedle($haystack, $needle)) {
                    return false;
                }
            }

            if ($faculty !== '' && (string) ($roster['name'] ?? '') !== $faculty) {
                return false;
            }

            return $this->matchesEventFilters($roster['events'] ?? [], $sport, $category)
                && $this->matchesLetter((string) ($roster['name'] ?? ''), $letter);
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function sortRows(array $rows, string $sort): array
    {
        usort($rows, function (array $a, array $b) use ($sort): int {
            if ($sort === 'faculty') {
                $facultyCompare = strcasecmp((string) ($a['faculty'] ?? ''), (string) ($b['faculty'] ?? ''));
                if ($facultyCompare !== 0) {
                    return $facultyCompare;
                }
            }

            $compare = strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));

            return $sort === 'name_desc' ? -$compare : $compare;
        });

        return $rows;
    }

    private function containsNeedle(array $values, string $needle): bool
    {
        foreach ($values as $value) {
            if (filled($value) && mb_strpos(mb_strtolower((string) $value), $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function matchesEventFilters(array $events, string $sport, string $category): bool
    {
        if ($sport !== '' && ! collect($events)->contains(fn (array $event) => ($event['sport'] ?? null) === $sport)) {
            return false;
        }

        if ($category !== '' && ! collect($events)->contains(fn (array $event) => ($event['category'] ?? null) === $category)) {
            return false;
        }

        return true;
    }

    private function matchesLetter(string $name, string $letter): bool
    {
        if ($letter === '') {
            return true;
        }

        $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));

        if ($letter === '#') {
            return preg_match('/[A-Z]/', $initial) !== 1;
        }

        return $initial === $letter;
    }

    /**
     * @param  array<int, array<string, mixed>>  $athletes
     * @return array<int, string>
     */
    private function availableLetters(array $athletes, string $q, string $sport, string $category, string $faculty): array
    {
        $letters = [];

        foreach ($this->filterAthleteRows($athletes, $q, $sport, $category, $faculty, '') as $athlete) {
            $initial = mb_strtoupper(mb_substr(trim((string) ($athlete['name'] ?? '')), 0, 1));
            $letters[preg_match('/[A-Z]/', $initial) === 1 ? $initial : '#'] = true;
        }

        $keys = array_keys($letters);
        sort($keys);

        return $keys;
    }

    private function emptyAthleteDirectory(string $view, string $q, string $sport, string $category, string $faculty, string $letter, string $sort): array
    {
        return [
            'view' => $view,
            'filters' => ['q' => $q, 'sport' => $sport, 'category' => $category, 'faculty' => $faculty, 'letter' => $letter, 'sort' => $sort],
            'letters' => [],
            'athletes' => null,
            'rosters' => null,
            'counts' => ['teams' => 0, 'athletes' => 0],
            'faculties' => [],
            'sports' => [],
            'categories' => [],
            'stats' => ['teams' => 0, 'athletes' => 0, 'officials' => 0],
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function athleteProfile(string $squadMemberId): ?array
    {
        $session = $this->publicSession();
        if (! $session) {
            return null;
        }

        $member = SquadMember::query()
            ->whereKey($squadMemberId)
            ->where('is_active', true)
            ->whereHas('eventParticipant', function ($query) use ($session) {
                $query->where('organization_id', $session->organization_id)
                    ->where('status', 'confirmed')
                    ->whereHas('participant', fn ($participant) => $participant->where('session_id', $session->id)->where('is_active', true))
                    ->whereHas('event', fn ($event) => $event->whereIn('tournament_id', $session->tournaments()->pluck('id'))->where('organization_id', $session->organization_id));
            })
            ->with([
                'eventParticipant.participant:id,name,logo_path,inverse_logo_path',
                'eventParticipant.event:id,name,sport_id,sport_category_id',
                'eventParticipant.event.sport:id,name',
                'eventParticipant.event.sportCategory:id,name',
            ])->first();

        if (! $member) {
            return null;
        }

        $entry = $member->eventParticipant;
        $participant = $entry->participant;
        $fixtures = Fixture::query()
            ->where('organization_id', $session->organization_id)
            ->where('event_id', $entry->event_id)
            ->where(function ($query) use ($participant) {
                $query->where('home_participant_id', $participant->id)->orWhere('away_participant_id', $participant->id);
            })
            ->with(['event:id,name,venues', 'result', 'homeParticipant:id,name', 'awayParticipant:id,name'])
            ->orderByDesc('scheduled_at')->orderByDesc('match_number')->get();

        $matches = $fixtures->map(function (Fixture $fixture) use ($participant) {
            $isHome = $fixture->home_participant_id === $participant->id;
            $result = $fixture->result;
            $outcome = null;
            if ($fixture->status === 'completed' && $result) {
                $outcome = $result->winner_participant_id === null
                    ? 'draw'
                    : ($result->winner_participant_id === $participant->id ? 'win' : 'loss');
            }

            return [
                'id' => $fixture->id,
                'event' => $fixture->event?->name,
                'opponent' => $isHome ? $fixture->awayParticipant?->name : $fixture->homeParticipant?->name,
                'score_for' => $isHome ? $result?->score_home : $result?->score_away,
                'score_against' => $isHome ? $result?->score_away : $result?->score_home,
                'scheduled_at' => $fixture->scheduled_at?->toIso8601String(),
                'venue' => $fixture->venue ?: ($fixture->event?->venues[0] ?? null),
                'status' => $fixture->status,
                'outcome' => $outcome,
            ];
        })->values();

        return [
            'athlete' => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->role,
                'faculty' => $participant->name,
                'logo_url' => $participant->logo_url,
                'inverse_logo_url' => $participant->inverse_logo_url,
                'sport' => $entry->event?->sport?->name,
                'category' => $entry->event?->sportCategory?->name,
                'event' => $entry->event?->name,
            ],
            'stats' => [
                'matches' => $matches->where('status', 'completed')->count(),
                'wins' => $matches->where('outcome', 'win')->count(),
                'draws' => $matches->where('outcome', 'draw')->count(),
                'losses' => $matches->where('outcome', 'loss')->count(),
            ],
            'matches' => $matches->all(),
            'updated_at' => (($fixtures->max('updated_at') ?: $session->updated_at))->toIso8601String(),
        ];
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
            ->with(['event.sport', 'event.sportCategory', 'pool:id,name', 'homeParticipant:id,name,team_name,logo_path,inverse_logo_path', 'awayParticipant:id,name,team_name,logo_path,inverse_logo_path', 'result.scoringEvents.squadMember']);

        $upcomingFixtures = $fixtureQuery()->whereIn('status', ['scheduled', 'in_progress'])
            ->orderByRaw('scheduled_at IS NULL')
            ->orderBy('scheduled_at')
            ->orderBy('match_number')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();
        $completedFixtures = $fixtureQuery()->where('status', 'completed')
            ->where(function ($query): void {
                $query->whereDoesntHave('result')
                    ->orWhereHas('result', fn ($result) => $result->publiclyVisible());
            })
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

        // Reuse the catalog for counts, names and venues instead of querying events repeatedly.
        $catalogEvents = (clone $eventQuery)->with([
            'sport:id,name',
            'sportCategory:id,name,quota_mode,max_athletes_total,max_male_athletes,max_female_athletes,min_male_athletes,min_female_athletes,max_officials',
            'sport.documents' => fn ($query) => $query
                ->where('organization_id', $organizationId)
                ->where('session_id', $session->id)
                ->where('is_published', true),
        ])->get();

        return [
            'app_name' => filled($portalSettings['app_name'] ?? null) ? $portalSettings['app_name'] : config('app.name'),
            'session_branding' => [
                'logo_url' => $session->logo_url,
                'inverse_logo_url' => $session->inverse_logo_url,
            ],
            'competition' => ['name' => $session->name, 'description' => $session->description,
                'start_date' => $session->start_date?->toDateString(), 'end_date' => $session->end_date?->toDateString(),
                'organization' => $session->organization?->name],
            'stats' => ['sports' => $catalogEvents->pluck('sport_id')->filter()->unique()->count(), 'events' => $catalogEvents->count(),
                'faculties' => Participant::query()->where('organization_id', $organizationId)->where('session_id', $session->id)->active()->count(),
                'completed_matches' => $completedFixtures->count(), 'total_matches' => $playableCount],
            'sports_catalog' => $catalogEvents
                ->groupBy('sport_id')->map(fn ($events) => [
                    'name' => $events->first()->sport?->name,
                    'categories' => $events->map(fn ($event) => $event->sportCategory?->name)->filter()->unique()->sort()->values()->all(),
                    'events' => $events->map(fn ($event) => [
                        'name' => $event->name,
                        'category' => $event->sportCategory?->name,
                        'venues' => collect($event->venues ?? [])->filter()->values()->all(),
                        'quota' => [
                            'mode' => $event->sportCategory?->quota_mode,
                            'total' => $event->sportCategory?->max_athletes_total,
                            'male' => $event->sportCategory?->max_male_athletes,
                            'female' => $event->sportCategory?->max_female_athletes,
                            'officials' => $event->sportCategory?->max_officials,
                            'min_male' => $event->sportCategory?->min_male_athletes,
                            'min_female' => $event->sportCategory?->min_female_athletes,
                        ],
                    ])->sortBy('name')->values()->all(),
                    'documents' => ($events->first()->sport?->documents ?? collect())->map(fn ($document) => [
                        'title' => $document->title,
                        'url' => $document->url,
                        'file_name' => $document->file_name,
                        'mime_type' => $document->mime_type,
                        'file_size' => $document->file_size,
                    ])->values()->all(),
                ])->filter(fn ($sport) => filled($sport['name']))->sortBy('name')->values()->all(),
            'sports' => $catalogEvents->pluck('sport.name')->filter()->unique()->sort()->values()->all(),
            'faculties' => Participant::query()->where('organization_id', $organizationId)->where('session_id', $session->id)->active()
                ->orderBy('name')->get(['id', 'name', 'logo_path', 'inverse_logo_path'])->map(fn (Participant $participant) => [
                    'name' => $participant->name, 'logo_url' => $participant->logo_url, 'inverse_logo_url' => $participant->inverse_logo_url,
                ])->values()->all(),
            'venues' => collect([
                ...$catalogEvents->pluck('venues')->flatten()->filter()->all(),
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

        if (! $organizationSlug) {
            return null;
        }

        $organization = Organization::query()->active()
            ->where('slug', $organizationSlug)->first();

        if (! $organization) {
            return null;
        }

        return Session::query()->with('organization:id,name')
            ->forOrganization($organization->id)
            ->active()
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
    }

    private function matchData(Fixture $fixture): array
    {
        $participant = fn ($value) => $value ? [
            'id' => $value->id,
            'name' => $value->name,
            'logo_url' => $value->logo_url,
            'inverse_logo_url' => $value->inverse_logo_url,
        ] : null;
        $eventVenues = $fixture->event?->venues ?? [];

        $scorers = $fixture->result?->scoringEvents?->map(fn ($event) => [
            'participant_id' => $event->participant_id,
            'name' => $event->squadMember?->name,
            'event_type' => $event->event_type,
            'period' => $event->period,
            'minute' => $event->minute,
            'second' => $event->second,
        ])->values()->all() ?? [];

        return ['id' => $fixture->id, 'sport' => $fixture->event?->sport?->name, 'event' => $fixture->event?->name,
            'category' => $fixture->event?->sportCategory?->name, 'stage' => $fixture->stage, 'round' => $fixture->round, 'group' => $fixture->pool?->name,
            'match_number' => $fixture->match_number, 'scheduled_at' => $fixture->scheduled_at?->toIso8601String(),
            'venue' => $fixture->venue ?: ($eventVenues[0] ?? null), 'status' => $fixture->status, 'home' => $participant($fixture->homeParticipant),
            'away' => $participant($fixture->awayParticipant), 'score_home' => $fixture->result?->score_home, 'score_away' => $fixture->result?->score_away,
            'scoring_events' => $scorers];
    }

    private function emptyData(): array
    {
        return ['app_name' => config('app.name'), 'session_branding' => ['logo_url' => null, 'inverse_logo_url' => null], 'competition' => null, 'stats' => ['sports' => 0, 'events' => 0, 'faculties' => 0, 'completed_matches' => 0, 'total_matches' => 0],
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
