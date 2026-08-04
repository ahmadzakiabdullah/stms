<?php

namespace App\Services;

use App\Models\Participant;
use App\Models\Session;
use App\Models\Setting;
use App\Models\Tournament;
use App\Models\User;

class ParticipationConfirmationService
{
    /**
     * @param  array{participant_id?: string, session_id?: string}  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters): array
    {
        $organizationId = (string) $user->organization_id;
        $canSelectParticipant = $user->hasAnyRole(['super-admin', 'org-admin']);
        $settings = Setting::query()
            ->where('organization_id', $organizationId)
            ->pluck('value', 'key');

        $participants = Participant::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'session_id']);

        $selectedParticipantId = $canSelectParticipant
            ? (string) ($filters['participant_id'] ?? $participants->first()?->id)
            : (string) $user->participant_id;

        $participant = $participants->firstWhere('id', $selectedParticipantId);
        if (! $participant && $canSelectParticipant) {
            $participant = $participants->first();
        }

        $sessions = Session::query()
            ->where('organization_id', $organizationId)
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'start_date', 'end_date', 'is_active']);

        $selectedSessionId = (string) ($filters['session_id'] ?? $participant?->session_id ?? $sessions->firstWhere('is_active', true)?->id ?? $sessions->first()?->id);
        if (! $sessions->contains('id', $selectedSessionId)) {
            $selectedSessionId = (string) ($sessions->first()?->id ?? '');
        }

        $tournaments = Tournament::query()
            ->where('organization_id', $organizationId)
            ->when($selectedSessionId, fn ($query) => $query->where('session_id', $selectedSessionId))
            ->orderBy('start_date')
            ->with(['events.sport', 'events.sportCategory'])
            ->get(['id', 'name', 'start_date', 'end_date']);

        $registrations = collect();
        if ($participant) {
            $registrations = $participant->eventParticipants()
                ->where('organization_id', $organizationId)
                ->with(['event.sport', 'event.sportCategory', 'event.tournament'])
                ->whereHas('event.tournament', fn ($query) => $query->when(
                    $selectedSessionId,
                    fn ($q) => $q->where('session_id', $selectedSessionId),
                ))
                ->get();
        }

        $registrationsByEvent = $registrations->keyBy('event_id');
        $phases = $tournaments->map(function (Tournament $tournament) use ($registrationsByEvent): array {
            $rows = $tournament->events
                ->sortBy(fn ($event) => implode('|', [
                    $event->sport?->name ?? '',
                    $event->sportCategory?->name ?? '',
                ]))
                ->values()
                ->map(function ($event) use ($registrationsByEvent): array {
                    $registration = $registrationsByEvent->get($event->id);

                    return [
                        'id' => $registration?->id ?? $event->id,
                        'sport' => $event->sport?->name ?? '-',
                        'category' => $event->sportCategory?->name ?? $event->name ?? '-',
                        'status' => $registration?->status ?? 'not_participating',
                        'yes' => $registration?->status === 'confirmed',
                        'no' => $registration === null || $registration->status === 'rejected',
                    ];
                });

            return [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'period' => $this->period($tournament->start_date, $tournament->end_date),
                'rows' => $rows,
            ];
        })->values();

        $dean = $participant
            ? User::query()
                ->where('organization_id', $organizationId)
                ->where('participant_id', $participant->id)
                ->whereHas('roles', fn ($query) => $query->where('name', 'dean'))
                ->orderBy('name')
                ->first(['uuid', 'name'])
            : null;

        return [
            'canSelectParticipant' => $canSelectParticipant,
            'organization' => [
                'name' => $user->organization?->name ?? '',
                'logo_url' => asset('images/utem-logo.svg'),
            ],
            'branding' => [
                'tournament_logo_url' => $settings->get('tournament_logo_url'),
                'secretariat_address' => $settings->get('secretariat_address', 'Sports Secretariat'),
            ],
            'participant' => $participant ? [
                'id' => $participant->id,
                'name' => $participant->name,
                'slug' => $participant->slug,
            ] : null,
            'dean' => $dean ? ['name' => $dean->name] : null,
            'participants' => $participants->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values(),
            'sessions' => $sessions->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'period' => $this->period($item->start_date, $item->end_date),
            ])->values(),
            'filters' => [
                'participant_id' => $participant?->id ?? '',
                'session_id' => $selectedSessionId,
            ],
            'phases' => $phases,
            'generatedDate' => now()->format('d F Y'),
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
