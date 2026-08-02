<?php

namespace App\Notifications;

use App\Models\EventParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventParticipantConfirmed extends Notification
{
    use Queueable;

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
            'message' => "Registration for '{$this->eventParticipant->event?->name}' has been approved.",
            'type' => 'confirmed',
            'severity' => 'success',
            'organization_id' => $this->eventParticipant->organization_id,
            'organization_name' => $organization?->name,
        ];
    }
}
