<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipant;
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
     * @param  array{sport_id?: ?string, faculty_id?: ?string, status?: ?string}  $filters
     * @return array<string, mixed>
     */
    public function dataFor(User $user, array $filters, bool $isSuper): array
    {
        $this->user = $user;
        $this->queryFailed = false;

        $sportId = $filters['sport_id'] ?? null;
        $facultyId = $filters['faculty_id'] ?? null;
        $status = $filters['status'] ?? null;
        $cacheKey = 'dashboard-v5-'.($user->organization_id ?? 'all').'-'.$user->getKey().'-'.md5(implode('|', [$sportId, $facultyId, $status]));

        $data = Cache::remember($cacheKey, 60, function () use ($isSuper, $user, $sportId, $facultyId, $status) {
            $stats = [
                'organizations' => $isSuper ? $this->safeCount(Organization::class) : 1,
                'activeSessions' => $this->safeCount(Session::class, fn ($query) => $query->where('is_active', true)),
                'tournaments' => $this->safeCount(Tournament::class),
                'sports' => $this->safeCount(Sport::class),
                'events' => $this->safeCount(Event::class),
                'participants' => $this->safeCount(Participant::class),
                'registrations' => $this->safeCount(Registration::class),
                'matches' => $this->safeCount(Fixture::class),
                'results' => $this->safeCount(Result::class),
            ];

            $totalEventRegistrations = $this->safeCount(EventParticipant::class);
            $participantsWithRegistrations = $this->safeCount(Participant::class, fn ($query) => $query
                ->where('is_active', true)
                ->whereHas('eventParticipants'));

            $registrationPipeline = $this->safeQuery(fn () => EventParticipant::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(), []);

            $upcomingEvents = $this->safeQuery(fn () => Event::query()
                ->with(['sport:id,name', 'sportCategory:id,name', 'tournament:id,name'])
                ->withCount('eventParticipants')
                ->where('is_active', true)
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

            $systemOverview = $isSuper ? $this->safeQuery(function () {
                $row = (array) DB::query()->selectRaw("(SELECT COUNT(*) FROM users) AS users,
                    (SELECT COUNT(*) FROM organizations WHERE is_active = 1) AS active_organizations,
                    (SELECT COUNT(*) FROM organizations WHERE is_active = 0) AS inactive_organizations,
                    (SELECT COUNT(*) FROM events WHERE is_active = 1) AS active_events,
                    (SELECT COUNT(*) FROM events WHERE is_active = 0) AS inactive_events,
                    (SELECT COUNT(*) FROM events e WHERE e.is_active = 1 AND NOT EXISTS (SELECT 1 FROM matches m WHERE m.event_id = e.id)) AS events_without_fixtures,
                    (SELECT COUNT(*) FROM matches WHERE scheduled_at IS NULL AND status IN ('scheduled', 'in_progress')) AS unscheduled_fixtures,
                    (SELECT COUNT(*) FROM matches WHERE status = 'scheduled') AS fixtures_scheduled,
                    (SELECT COUNT(*) FROM matches WHERE status = 'in_progress') AS fixtures_in_progress,
                    (SELECT COUNT(*) FROM matches WHERE status = 'completed') AS fixtures_completed,
                    (SELECT COUNT(*) FROM matches WHERE status = 'cancelled') AS fixtures_cancelled")->first();

                return [
                    'users' => (int) ($row['users'] ?? 0),
                    'activeOrganizations' => (int) ($row['active_organizations'] ?? 0),
                    'inactiveOrganizations' => (int) ($row['inactive_organizations'] ?? 0),
                    'activeEvents' => (int) ($row['active_events'] ?? 0),
                    'inactiveEvents' => (int) ($row['inactive_events'] ?? 0),
                    'eventsWithoutFixtures' => (int) ($row['events_without_fixtures'] ?? 0),
                    'unscheduledFixtures' => (int) ($row['unscheduled_fixtures'] ?? 0),
                    'fixturesByStatus' => [
                        'scheduled' => (int) ($row['fixtures_scheduled'] ?? 0),
                        'in_progress' => (int) ($row['fixtures_in_progress'] ?? 0),
                        'completed' => (int) ($row['fixtures_completed'] ?? 0),
                        'cancelled' => (int) ($row['fixtures_cancelled'] ?? 0),
                    ],
                ];
            }, []) : [];

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

            return compact(
                'stats', 'recentSessions', 'recentTournaments',
                'totalEventRegistrations', 'participantsWithRegistrations',
                'upcomingEvents', 'registrationsBySport',
                'registrationPipeline', 'registrationStats', 'systemOverview', 'facultyStats', 'eventStats', 'sports', 'faculties', 'squadStats'
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
