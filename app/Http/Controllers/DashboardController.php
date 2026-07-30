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

        $safeCount = function (string $modelClass, $query = null) {
            try {
                $builder = $modelClass::query();
                if ($query) {
                    $builder = $query($builder);
                }

                return $builder->count();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        $safeQuery = function (\Closure $query, $default = null) {
            try {
                return $query();
            } catch (\Throwable $e) {
                return $default ?? collect();
            }
        };

        $isSuper = false;
        try {
            $isSuper = $user && $user->hasRole('super-admin');
        } catch (\Throwable $e) {
            $isSuper = false;
        }

        $isFacultyRep = $user && $user->hasRole('faculty-representative') && $user->participant_id;

        $cacheKey = 'dashboard-v2-'.($user?->id ?? 'guest');
        $data = Cache::remember($cacheKey, 60, function () use ($safeCount, $safeQuery, $isSuper, $user, $isFacultyRep) {
            $stats = [
                'organizations' => $isSuper
                    ? $safeCount(Organization::class)
                    : 1,
                'activeSessions' => $safeCount(Session::class, fn ($q) => $q->where('is_active', true)),
                'tournaments' => $safeCount(Tournament::class),
                'sports' => $safeCount(Sport::class),
                'events' => $safeCount(Event::class),
                'participants' => $safeCount(Participant::class),
                'registrations' => $safeCount(Registration::class),
                'matches' => $safeCount(Fixture::class),
                'results' => $safeCount(Result::class),
            ];

            $totalEventRegistrations = $safeCount(EventParticipant::class);
            $participantsWithRegistrations = $safeCount(Participant::class, fn ($q) => $q->whereHas('eventParticipants'));

            $upcomingEvents = $safeQuery(fn () => Event::query()
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

            $registrationsBySport = $safeQuery(fn () => EventParticipant::query()
                ->selectRaw('sports.name, count(*) as total')
                ->join('events', 'event_participants.event_id', '=', 'events.id')
                ->join('sports', 'events.sport_id', '=', 'sports.id')
                ->groupBy('sports.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get());

            $myRegistrations = null;
            $facultyRegistrations = collect();
            $facultyMale = 0;
            $facultyFemale = 0;
            $facultyOfficials = 0;

            if ($isFacultyRep) {
                $myRegistrations = $safeCount(EventParticipant::class, fn ($q) => $q->where('participant_id', $user->participant_id));

                $facultyRegistrations = $safeQuery(fn () => EventParticipant::query()
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
                $facultyMale = $allSquad->where('role', 'athlete_male')->count();
                $facultyFemale = $allSquad->where('role', 'athlete_female')->count();
                $facultyOfficials = $allSquad->whereIn('role', ['manager', 'coach', 'physio'])->count();
            }

            $recentSessions = $safeQuery(fn () => Session::query()
                ->when(! $isSuper && $user, fn ($q) => $q->where('organization_id', $user->organization_id))
                ->orderBy('start_date', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'start_date', 'end_date', 'is_active']), collect());

            $recentTournaments = $safeQuery(fn () => Tournament::query()
                ->when(! $isSuper && $user, fn ($q) => $q->where('organization_id', $user->organization_id))
                ->with('session:id,name')
                ->orderBy('start_date', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'start_date', 'end_date', 'is_active', 'session_id']), collect());

            return compact(
                'stats', 'recentSessions', 'recentTournaments',
                'totalEventRegistrations', 'participantsWithRegistrations',
                'upcomingEvents', 'registrationsBySport', 'isFacultyRep', 'myRegistrations',
                'facultyRegistrations', 'facultyMale', 'facultyFemale', 'facultyOfficials'
            );
        });

        return Inertia::render('Dashboard', $data);
    }
}
