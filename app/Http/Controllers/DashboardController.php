<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Fixture;
use App\Models\Organization;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Result;
use App\Models\Session;
use App\Models\Sport;
use App\Models\Tournament;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $isSuper = $this->isSuperAdmin($user);
        $isFacultyRep = $this->isFacultyRepresentative($user);

        $cacheKey = 'dashboard-v2-'.($user?->id ?? 'guest');

        $data = Cache::remember($cacheKey, 60, function () use ($isSuper, $user, $isFacultyRep) {
            return array_merge([
                'stats' => $this->getStats($isSuper),
                'totalEventRegistrations' => $this->safeCount(EventParticipant::class),
                'participantsWithRegistrations' => $this->safeCount(Participant::class, fn ($q) => $q->whereHas('eventParticipants')),
                'upcomingEvents' => $this->getUpcomingEvents(),
                'registrationsBySport' => $this->getRegistrationsBySport(),
                'recentSessions' => $this->getRecentSessions($user, $isSuper),
                'recentTournaments' => $this->getRecentTournaments($user, $isSuper),
                'isFacultyRep' => $isFacultyRep,
            ], $this->getFacultyData($user, $isFacultyRep));
        });

        return Inertia::render('Dashboard', $data);
    }

    private function isSuperAdmin($user): bool
    {
        try {
            return $user && $user->hasRole('super-admin');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isFacultyRepresentative($user): bool
    {
        try {
            return $user && $user->hasRole('faculty-representative') && $user->participant_id;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function safeCount(string $modelClass, $query = null): int
    {
        try {
            $builder = $modelClass::query();
            if ($query) {
                $builder = $query($builder);
            }

            return $builder->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeQuery(\Closure $query, $default = null)
    {
        try {
            return $query();
        } catch (\Throwable $e) {
            return $default ?? collect();
        }
    }

    private function getStats(bool $isSuper): array
    {
        return [
            'organizations' => $isSuper
                ? $this->safeCount(Organization::class)
                : 1,
            'activeSessions' => $this->safeCount(Session::class, fn ($q) => $q->where('is_active', true)),
            'tournaments' => $this->safeCount(Tournament::class),
            'sports' => $this->safeCount(Sport::class),
            'events' => $this->safeCount(Event::class),
            'participants' => $this->safeCount(Participant::class),
            'registrations' => $this->safeCount(Registration::class),
            'matches' => $this->safeCount(Fixture::class),
            'results' => $this->safeCount(Result::class),
        ];
    }

    private function getUpcomingEvents()
    {
        return $this->safeQuery(fn () => Event::query()
            ->with(['sport:id,name', 'sportCategory:id,name', 'tournament:id,name'])
            ->withCount('eventParticipants')
            ->where('is_active', true)
            ->where('start_date', '>=', now()->subDay())
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => $e->name,
                'slug' => $e->slug,
                'start_date' => $e->start_date?->format('Y-m-d'),
                'sport' => $e->sport ? ['id' => $e->sport->id, 'name' => $e->sport->name] : null,
                'sport_category' => $e->sportCategory ? ['id' => $e->sportCategory->id, 'name' => $e->sportCategory->name] : null,
                'tournament' => $e->tournament ? ['id' => $e->tournament->id, 'name' => $e->tournament->name] : null,
                'registration_count' => $e->event_participants_count ?? 0,
            ]));
    }

    private function getRegistrationsBySport()
    {
        return $this->safeQuery(fn () => EventParticipant::query()
            ->selectRaw('sports.name, count(*) as total')
            ->join('events', 'event_participants.event_id', '=', 'events.id')
            ->join('sports', 'events.sport_id', '=', 'sports.id')
            ->groupBy('sports.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get());
    }

    private function getFacultyData($user, bool $isFacultyRep): array
    {
        if (! $isFacultyRep) {
            return [
                'myRegistrations' => null,
                'facultyRegistrations' => collect(),
                'facultyMale' => 0,
                'facultyFemale' => 0,
                'facultyOfficials' => 0,
            ];
        }

        $myRegistrations = $this->safeCount(EventParticipant::class, fn ($q) => $q->where('participant_id', $user->participant_id));

        $facultyRegistrations = $this->safeQuery(fn () => EventParticipant::query()
            ->with([
                'event.sport:id,name',
                'event.sportCategory:id,name',
                'squadMembers',
            ])
            ->where('participant_id', $user->participant_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($ep) => [
                'id' => $ep->id,
                'event' => $ep->event ? [
                    'id' => $ep->event->id,
                    'name' => $ep->event->name,
                    'sport' => $ep->event->sport ? ['id' => $ep->event->sport->id, 'name' => $ep->event->sport->name] : null,
                    'sport_category' => $ep->event->sportCategory ? ['id' => $ep->event->sportCategory->id, 'name' => $ep->event->sportCategory->name] : null,
                    'start_date' => $ep->event->start_date?->format('Y-m-d'),
                ] : null,
                'squad_members' => $ep->squadMembers->map(fn ($sm) => ['role' => $sm->role]),
            ]));

        $allSquad = $facultyRegistrations->flatMap(fn ($r) => $r['squad_members'] ?? []);

        return [
            'myRegistrations' => $myRegistrations,
            'facultyRegistrations' => $facultyRegistrations,
            'facultyMale' => $allSquad->where('role', 'athlete_male')->count(),
            'facultyFemale' => $allSquad->where('role', 'athlete_female')->count(),
            'facultyOfficials' => $allSquad->whereIn('role', ['manager', 'coach', 'physio'])->count(),
        ];
    }

    private function getRecentSessions($user, bool $isSuper)
    {
        return $this->safeQuery(fn () => Session::query()
            ->when(! $isSuper && $user, fn ($q) => $q->where('organization_id', $user->organization_id))
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'start_date', 'end_date', 'is_active']), collect());
    }

    private function getRecentTournaments($user, bool $isSuper)
    {
        return $this->safeQuery(fn () => Tournament::query()
            ->when(! $isSuper && $user, fn ($q) => $q->where('organization_id', $user->organization_id))
            ->with('session:id,name')
            ->orderBy('start_date', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'start_date', 'end_date', 'is_active', 'session_id']), collect());
    }
}
