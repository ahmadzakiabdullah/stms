<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Setting;

class TeamRegistrationFormService
{
    /** @return array<string, mixed> */
    public function build(EventParticipant $registration): array
    {
        $registration->load([
            'participant',
            'event.sport',
            'event.sportCategory',
            'event.tournament.session',
            'squadMembers' => fn ($query) => $query->where('is_active', true)->orderBy('name'),
        ]);

        $settings = Setting::query()
            ->where('organization_id', $registration->organization_id)
            ->pluck('value', 'key');
        $organization = Organization::query()->find($registration->organization_id);

        $officialRoles = ['manager', 'assistant_manager', 'coach', 'physio'];
        $roleOrder = array_flip($officialRoles);
        $officials = $registration->squadMembers
            ->whereIn('role', $officialRoles)
            ->sortBy(fn ($member) => $roleOrder[$member->role] ?? 99)
            ->values();
        $athletes = $registration->squadMembers
            ->whereIn('role', ['athlete_male', 'athlete_female'])
            ->sortBy(fn ($member) => implode('|', [$member->role, $member->name]))
            ->values();

        $category = $registration->event?->sportCategory;
        $athleteQuota = $category?->quota_mode === 'gender_based'
            ? (int) $category->max_male_athletes + (int) $category->max_female_athletes
            : (int) $category?->max_athletes_total;

        return [
            'organization' => [
                'name' => $organization?->name ?? '',
                'logo_url' => asset('images/utem-logo.svg'),
            ],
            'branding' => [
                'tournament_logo_url' => $settings->get('tournament_logo_url'),
                'secretariat_address' => $settings->get('secretariat_address', 'Sports Secretariat'),
                'form_reference' => $settings->get('team_registration_form_reference', 'SAF 03/04'),
            ],
            'registration' => [
                'id' => $registration->id,
                'status' => $registration->status,
                'registration_date' => $registration->registration_date?->format('d/m/Y'),
            ],
            'participant' => [
                'name' => $registration->participant?->name ?? '-',
                'team_name' => $registration->participant?->team_name,
                'logo_url' => $registration->participant?->logo_url,
            ],
            'event' => [
                'name' => $registration->event?->name ?? '-',
                'sport' => $registration->event?->sport?->name ?? '-',
                'category' => $category?->name ?? '-',
                'tournament' => $registration->event?->tournament?->name ?? '-',
                'session' => $registration->event?->tournament?->session?->name ?? '-',
                'period' => $this->period($registration->event?->start_date, $registration->event?->end_date),
            ],
            'officials' => $officials->map(fn ($member) => $this->member($member))->all(),
            'athletes' => $athletes->map(fn ($member) => $this->member($member))->all(),
            'quotaRows' => [
                'officials' => (int) $category?->max_officials,
                'athletes' => $athleteQuota,
            ],
            'generatedDate' => now()->format('d/m/Y'),
        ];
    }

    /** @return array<string, mixed> */
    private function member($member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'role' => $member->role,
            'matrix_no' => $member->matrix_no,
            'identification_no' => $member->identification_no,
            'phone' => $member->phone,
        ];
    }

    private function period($startDate, $endDate): string
    {
        if (! $startDate || ! $endDate) {
            return '-';
        }

        return $startDate->format('d/m/Y').' - '.$endDate->format('d/m/Y');
    }
}
