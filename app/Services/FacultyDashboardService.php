<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\SportCategory;
use App\Models\User;

class FacultyDashboardService
{
    /**
     * Assemble the data required by the faculty dashboard page.
     *
     * The faculty dashboard lives on the main `/dashboard` route: faculty
     * representatives use it to register their faculty for events and to
     * manage squad members.
     *
     * @return array<string, mixed>
     */
    public function dataFor(User $user): array
    {
        $participant = $user->participant;

        $registrations = collect();
        $totalMale = 0;
        $totalFemale = 0;
        $totalOfficials = 0;

        if ($participant) {
            $registrations = EventParticipant::with([
                'event.sport',
                'event.sportCategory',
                'event.tournament.session',
                'squadMembers' => fn ($q) => $q->ordered(),
            ])
                ->where('participant_id', $participant->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // ⚡ Optimized: Replaced repeated ->where()->count() loops with a single ->countBy() pass on flattened members.
            // Expected Impact: Reduces CPU cycles and nested iteration overhead when calculating squad totals.
            $rolesCount = $registrations->flatMap->squadMembers->countBy('role');
            $totalMale += $rolesCount->get('athlete_male', 0);
            $totalFemale += $rolesCount->get('athlete_female', 0);
            $totalOfficials += $rolesCount->only(['assistant_manager', 'manager', 'coach', 'physio'])->sum();
        }

        $availableEvents = Event::with(['sport', 'sportCategory', 'tournament'])
            ->where('is_active', true)
            ->orderBy('start_date')
            ->get(['id', 'name', 'sport_id', 'sport_category_id', 'tournament_id', 'start_date']);

        return [
            'participant' => $participant,
            'registrations' => $registrations,
            'totals' => [
                'male' => $totalMale,
                'female' => $totalFemale,
                'officials' => $totalOfficials,
            ],
            'availableEvents' => $availableEvents,
            'sportCategories' => SportCategory::with('sport')
                ->orderBy('name')
                ->get()
                ->map(fn ($sc) => array_merge(
                    $sc->only([
                        'id',
                        'name',
                        'sport_id',
                        'quota_mode',
                        'max_athletes_total',
                        'max_male_athletes',
                        'max_female_athletes',
                        'min_male_athletes',
                        'min_female_athletes',
                        'max_officials',
                    ]),
                    ['allowed_roles' => $sc->allowedAthleteRoles()]
                )),
        ];
    }
}
