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

            // Optimization: Use a single pass countBy to avoid O(N*3) multiple where()->count() passes on the collection
            foreach ($registrations as $reg) {
                $roleCounts = $reg->squadMembers->countBy('role');
                $totalMale += $roleCounts->get('athlete_male', 0);
                $totalFemale += $roleCounts->get('athlete_female', 0);
                $totalOfficials += $roleCounts->get('assistant_manager', 0)
                                 + $roleCounts->get('manager', 0)
                                 + $roleCounts->get('coach', 0)
                                 + $roleCounts->get('physio', 0);
            }
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
