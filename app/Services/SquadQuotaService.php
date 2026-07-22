<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Models\SportCategory;
use App\Models\SquadMember;

class SquadQuotaService
{
    private const ATHLETE_ROLES = ['athlete_male', 'athlete_female'];
    private const OFFICIAL_ROLES = ['assistant_manager', 'manager', 'coach', 'physio'];

    public function validateAddition(EventParticipant $eventParticipant, string $role): ?string
    {
        $category = $eventParticipant->event?->sportCategory;

        if (in_array($role, self::ATHLETE_ROLES, true)) {
            return $this->validateAthleteAddition($eventParticipant, $category, $role);
        }

        if (in_array($role, self::OFFICIAL_ROLES, true)) {
            return $this->validateOfficialAddition($eventParticipant, $category, $role);
        }

        return null;
    }

    public function athleteCounts(EventParticipant $eventParticipant): array
    {
        $male = SquadMember::where('event_participant_id', $eventParticipant->id)
            ->where('role', 'athlete_male')
            ->count();

        $female = SquadMember::where('event_participant_id', $eventParticipant->id)
            ->where('role', 'athlete_female')
            ->count();

        return [
            'male' => $male,
            'female' => $female,
            'total' => $male + $female,
        ];
    }

    private function validateAthleteAddition(
        EventParticipant $eventParticipant,
        ?SportCategory $category,
        string $role
    ): ?string {
        $allowed = $category?->allowedAthleteRoles() ?? self::ATHLETE_ROLES;
        if (!in_array($role, $allowed, true)) {
            return 'This event does not allow ' . ($role === 'athlete_male' ? 'male' : 'female') . ' athletes.';
        }

        $maxField = $role === 'athlete_male' ? 'max_male_athletes' : 'max_female_athletes';
        $max = $category?->$maxField;
        if ($max !== null) {
            $current = SquadMember::where('event_participant_id', $eventParticipant->id)
                ->where('role', $role)
                ->count();

            if ($current >= $max) {
                return ucfirst(str_replace('_', ' ', $role)) . " quota full ({$current}/{$max}).";
            }
        }

        if (!$category?->usesTotalAthleteQuota()) {
            return null;
        }

        $counts = $this->athleteCounts($eventParticipant);
        if ($counts['total'] >= $category->max_athletes_total) {
            return "Athlete quota full ({$counts['total']}/{$category->max_athletes_total}).";
        }

        $nextMale = $counts['male'] + ($role === 'athlete_male' ? 1 : 0);
        $nextFemale = $counts['female'] + ($role === 'athlete_female' ? 1 : 0);
        $nextTotal = $nextMale + $nextFemale;

        if ($nextTotal === $category->max_athletes_total) {
            $minMale = $category->min_male_athletes ?? 0;
            $minFemale = $category->min_female_athletes ?? 0;

            if ($nextMale < $minMale || $nextFemale < $minFemale) {
                return "Athlete mix does not meet the required minimum gender quota (male {$nextMale}/{$minMale}, female {$nextFemale}/{$minFemale}).";
            }
        }

        return null;
    }

    private function validateOfficialAddition(
        EventParticipant $eventParticipant,
        ?SportCategory $category,
        string $role
    ): ?string {
        $current = SquadMember::where('event_participant_id', $eventParticipant->id)
            ->where('role', $role)
            ->count();

        if ($current >= 1) {
            return 'Only 1 ' . $role . ' allowed per team.';
        }

        $maxOfficials = $category?->max_officials;
        if ($maxOfficials !== null) {
            $totalOfficials = SquadMember::where('event_participant_id', $eventParticipant->id)
                ->whereIn('role', self::OFFICIAL_ROLES)
                ->count();

            if ($totalOfficials >= $maxOfficials) {
                return "Total officials quota full ({$totalOfficials}/{$maxOfficials}).";
            }
        }

        return null;
    }
}
