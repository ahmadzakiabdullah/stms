<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\DashboardMetricSnapshot;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Session;
use App\Models\Sport;
use App\Models\SquadMember;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class DashboardDataService
{
    private bool $queryFailed = false;

    private ?User $user = null;

    /**
     * @param  array{sport_id?: ?string, faculty_id?: ?string, status?: ?string, session_id?: ?string, tournament_id?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function dataFor(User $user, array $filters, bool $isSuper): array
    {
        $this->user = $user;
        $this->queryFailed = false;

        $sportId = $filters['sport_id'] ?? null;
        $facultyId = $filters['faculty_id'] ?? null;
        $status = $filters['status'] ?? null;
        $sessionId = $filters['session_id'] ?? null;
        $tournamentId = $filters['tournament_id'] ?? null;
        $scopeFilters = [$sportId, $facultyId, $status, $sessionId, $tournamentId];
        $cacheKey = 'dashboard-v7-'.($user->organization_id ?? 'all').'-'.$user->getKey().'-'.md5(implode('|', $scopeFilters));

        $data = Cache::remember($cacheKey, 60, function () use ($isSuper, $user, $sportId, $facultyId, $status, $sessionId, $tournamentId) {
            $eventScope = fn ($query) => $query
                ->when($sessionId, fn ($query) => $query->whereHas('tournament', fn ($query) => $query->where('session_id', $sessionId)))
                ->when($tournamentId, fn ($query) => $query->where('tournament_id', $tournamentId));
            $fixtureScope = fn ($query) => $query->whereHas('event', $eventScope);
            $stats = [
                'organizations' => $isSuper ? $this->safeCount(Organization::class) : 1,
                'activeSessions' => $this->safeCount(Session::class, fn ($query) => $query->where('is_active', true)),
                'tournaments' => $this->safeCount(Tournament::class),
                'sports' => $this->safeCount(Sport::class),
                'events' => $this->safeCount(Event::class, $eventScope),
                'participants' => $this->safeCount(Participant::class),
                'registrations' => $this->safeCount(Registration::class),
                'matches' => $this->safeCount(Fixture::class, $fixtureScope),
                'results' => $this->safeCount(Result::class, fn ($query) => $query->whereHas('match', fn ($query) => $fixtureScope($query))),
            ];

            $totalEventRegistrations = $this->safeCount(EventParticipant::class, fn ($query) => $query->whereHas('event', $eventScope));
            $participantsWithRegistrations = $this->safeCount(Participant::class, fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('eventParticipants', fn ($query) => $query->whereHas('event', $eventScope)));

            $registrationPipeline = $this->safeQuery(fn () => EventParticipant::query()
                ->whereHas('event', $eventScope)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(), []);

            $upcomingEvents = $this->safeQuery(fn () => Event::query()
                ->with(['sport:id,name', 'sportCategory:id,name', 'tournament:id,name'])
                ->withCount('eventParticipants')
                ->where('is_active', true)
                ->when($sessionId, fn ($query) => $query->whereHas('tournament', fn ($query) => $query->where('session_id', $sessionId)))
                ->when($tournamentId, fn ($query) => $query->where('tournament_id', $tournamentId))
                ->where('start_date', '>=', now()->subDay())
                ->orderBy('start_date')
                ->limit(5)
                ->get()
                ->map(fn ($event) => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'slug' => $event->slug,
                    'start_date' => $event->start_date?->format('Y-m-d'),
                    'sport' => $event->sport ? ['id' => $event->sport->id, 'name' => $event->sport->name] : null,
                    'sport_category' => $event->sportCategory ? ['id' => $event->sportCategory->id, 'name' => $event->sportCategory->name] : null,
                    'tournament' => $event->tournament ? ['id' => $event->tournament->id, 'name' => $event->tournament->name] : null,
                    'registration_count' => $event->event_participants_count ?? 0,
                ]));

            $registrationsBySport = $this->safeQuery(fn () => EventParticipant::query()
                ->selectRaw('sports.name, count(*) as total')
                ->join('events', 'event_participants.event_id', '=', 'events.id')
                ->join('sports', 'events.sport_id', '=', 'sports.id')
                ->when($sessionId, fn ($query) => $query->whereIn('events.tournament_id', Tournament::query()->select('id')->where('session_id', $sessionId)))
                ->when($tournamentId, fn ($query) => $query->where('events.tournament_id', $tournamentId))
                ->groupBy('sports.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get());

            $registrationStats = [
                'totalRegistrations' => $totalEventRegistrations,
                'pending' => $registrationPipeline['pending'] ?? 0,
                'confirmed' => $registrationPipeline['confirmed'] ?? 0,
                'totalFaculties' => $this->safeCount(Participant::class, fn ($query) => $query->where('is_active', true)),
                'totalEvents' => $this->safeCount(Event::class, fn ($query) => $query->where('is_active', true)),
            ];

            $systemOverview = $isSuper ? $this->safeQuery(function () use ($eventScope) {
                $fixturesByStatus = Fixture::query()->whereHas('event', $eventScope)
                    ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
                $activeEvents = $eventScope(Event::query()->where('is_active', true));
                $inactiveEvents = $eventScope(Event::query()->where('is_active', false));
                $eventsWithoutFixtures = clone $activeEvents;
                $eventsWithoutFixtures->whereHas('eventParticipants')->whereDoesntHave('matches');

                return [
                    'users' => User::query()->count(),
                    'activeOrganizations' => Organization::query()->where('is_active', true)->count(),
                    'inactiveOrganizations' => Organization::query()->where('is_active', false)->count(),
                    'activeEvents' => $activeEvents->count(),
                    'inactiveEvents' => $inactiveEvents->count(),
                    'eventsWithoutFixtures' => $eventsWithoutFixtures->count(),
                    'unscheduledFixtures' => Fixture::query()->whereHas('event', $eventScope)
                        ->whereNull('scheduled_at')->whereIn('status', ['scheduled', 'in_progress'])->count(),
                    'fixturesByStatus' => [
                        'scheduled' => (int) ($fixturesByStatus['scheduled'] ?? 0),
                        'in_progress' => (int) ($fixturesByStatus['in_progress'] ?? 0),
                        'completed' => (int) ($fixturesByStatus['completed'] ?? 0),
                        'cancelled' => (int) ($fixturesByStatus['cancelled'] ?? 0),
                    ],
                ];
            }, []) : [];

            $trendSnapshots = $isSuper ? $this->safeQuery(fn () => DashboardMetricSnapshot::query()
                ->where('scope_key', $tournamentId ? 'tournament:'.$tournamentId : ($sessionId ? 'session:'.$sessionId : 'global'))
                ->where('snapshot_date', '>=', now()->subDays(89)->toDateString())
                ->orderBy('snapshot_date')
                ->get(['snapshot_date', 'organizations', 'users', 'events', 'matches'])
                ->map(fn ($snapshot) => [
                    'date' => $snapshot->snapshot_date->toDateString(),
                    'organizations' => $snapshot->organizations,
                    'users' => $snapshot->users,
                    'events' => $snapshot->events,
                    'matches' => $snapshot->matches,
                ])->values(), collect()) : collect();

            $facultyStats = $this->safeQuery(fn () => Participant::query()
                ->where('is_active', true)
                ->withCount(['eventParticipants as total' => function ($query) use ($sportId, $status) {
                    if ($sportId) {
                        $query->whereHas('event', fn ($query) => $query->where('sport_id', $sportId));
                    }
                    if ($status) {
                        $query->where('status', $status);
                    }
                }])
                ->withCount(['eventParticipants as pending' => function ($query) use ($sportId) {
                    $query->where('status', 'pending')->when($sportId, fn ($query) => $query->whereHas('event', fn ($query) => $query->where('sport_id', $sportId)));
                }])
                ->withCount(['eventParticipants as confirmed' => function ($query) use ($sportId) {
                    $query->where('status', 'confirmed')->when($sportId, fn ($query) => $query->whereHas('event', fn ($query) => $query->where('sport_id', $sportId)));
                }])
                ->withCount(['eventParticipants as rejected' => function ($query) use ($sportId) {
                    $query->where('status', 'rejected')->when($sportId, fn ($query) => $query->whereHas('event', fn ($query) => $query->where('sport_id', $sportId)));
                }])
                ->orderBy('name')
                ->get(['id', 'name']), collect());

            $eventStats = $this->safeQuery(fn () => Event::query()
                ->where('is_active', true)
                ->with(['sport', 'sportCategory', 'tournament'])
                ->withCount(['eventParticipants as total' => function ($query) use ($facultyId, $status) {
                    if ($facultyId) {
                        $query->where('participant_id', $facultyId);
                    }
                    if ($status) {
                        $query->where('status', $status);
                    }
                }])
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(), collect());

            $sports = $this->safeQuery(fn () => Sport::query()->orderBy('name')->get(['id', 'name']), collect());
            $faculties = $this->safeQuery(fn () => Participant::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']), collect());

            $squadStats = $this->safeQuery(function () use ($sportId, $facultyId, $status) {
                return SquadMember::query()
                    ->join('event_participants', 'squad_members.event_participant_id', '=', 'event_participants.id')
                    ->join('events', 'event_participants.event_id', '=', 'events.id')
                    ->where('squad_members.is_active', true)
                    ->when($sportId, fn ($query) => $query->where('events.sport_id', $sportId))
                    ->when($facultyId, fn ($query) => $query->where('event_participants.participant_id', $facultyId))
                    ->when($status, fn ($query) => $query->where('event_participants.status', $status))
                    ->selectRaw('squad_members.role, count(*) as total')
                    ->groupBy('squad_members.role')
                    ->pluck('total', 'role')
                    ->all();
            }, []);

            $recentSessions = $this->safeQuery(fn () => Session::query()
                ->when(! $isSuper, fn ($query) => $query->where('organization_id', $user->organization_id))
                ->orderBy('start_date', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'start_date', 'end_date', 'is_active']), collect());

            $recentTournaments = $this->safeQuery(fn () => Tournament::query()
                ->when(! $isSuper, fn ($query) => $query->where('organization_id', $user->organization_id))
                ->with('session:id,name')
                ->orderBy('start_date', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'start_date', 'end_date', 'is_active', 'session_id']), collect());

            $filterSessions = $this->safeQuery(fn () => Session::query()
                ->when(! $isSuper, fn ($query) => $query->where('organization_id', $user->organization_id))
                ->orderByDesc('is_active')->orderByDesc('start_date')->get(['id', 'name']), collect());
            $filterTournaments = $this->safeQuery(fn () => Tournament::query()
                ->when(! $isSuper, fn ($query) => $query->where('organization_id', $user->organization_id))
                ->when($sessionId, fn ($query) => $query->where('session_id', $sessionId))
                ->orderBy('name')->get(['id', 'name', 'session_id']), collect());

            $currentTrendMetrics = [
                'organizations' => (int) ($systemOverview['activeOrganizations'] ?? 0),
                'users' => (int) ($systemOverview['users'] ?? 0),
                'events' => $this->safeCount(Event::class, $eventScope),
                'matches' => $this->safeCount(Fixture::class, $fixtureScope),
            ];
            $selectedFilters = ['session_id' => $sessionId, 'tournament_id' => $tournamentId];

            return compact(
                'stats', 'recentSessions', 'recentTournaments',
                'totalEventRegistrations', 'participantsWithRegistrations',
                'upcomingEvents', 'registrationsBySport',
                'registrationPipeline', 'registrationStats', 'systemOverview', 'facultyStats', 'eventStats', 'sports', 'faculties', 'squadStats',
                'trendSnapshots', 'filterSessions', 'filterTournaments', 'currentTrendMetrics', 'selectedFilters'
            );
        });

        if ($this->queryFailed) {
            Cache::forget($cacheKey);
        }

        return $data;
    }

    private function safeCount(string $modelClass, ?\Closure $query = null): int
    {
        try {
            $builder = $modelClass::query();

            return ($query ? $query($builder) : $builder)->count();
        } catch (\Throwable $exception) {
            $this->queryFailed = true;
            $this->logFallback('count', $exception, ['model' => $modelClass]);

            return 0;
        }
    }

    private function safeQuery(\Closure $query, mixed $default = null): mixed
    {
        try {
            return $query();
        } catch (\Throwable $exception) {
            $this->queryFailed = true;
            $this->logFallback('query', $exception);

            return $default ?? collect();
        }
    }

    private function logFallback(string $operation, \Throwable $exception, array $context = []): void
    {
        Log::warning('Dashboard query failed; using fallback payload.', array_merge([
            'operation' => $operation,
            'exception' => $exception,
            'correlation_id' => request()->attributes->get('correlation_id'),
            'user_id' => $this->user?->uuid,
            'organization_id' => $this->user?->organization_id,
            'route' => request()->path(),
        ], $context));
    }
}
