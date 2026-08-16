<?php

namespace App\Notifications;

use App\Models\EventParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEventRegistration extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public int $timeout = 60;

    public function __construct(public EventParticipant $eventParticipant) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $organization = $this->eventParticipant->participant?->organization;

        return [
            'event_participant_id' => $this->eventParticipant->id,
            'event_name' => $this->eventParticipant->event?->name ?? 'Unknown Event',
            'faculty_name' => $this->eventParticipant->participant?->name ?? 'Unknown Faculty',
            'message' => "{$this->eventParticipant->participant?->name} registered for '{$this->eventParticipant->event?->name}'.",
            'type' => 'new_registration',
            'severity' => 'warning',
            'organization_id' => $this->eventParticipant->organization_id,
            'organization_name' => $organization?->name,
            'action_url' => route('event-participants.index', [
                'status' => 'pending',
                'participant_id' => $this->eventParticipant->participant_id,
            ]),
        ];
    }
}
