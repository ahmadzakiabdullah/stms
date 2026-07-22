<?php

namespace App\Notifications;

use App\Models\EventParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEventRegistration extends Notification
{
    use Queueable;

    public function __construct(public EventParticipant $eventParticipant) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'event_participant_id' => $this->eventParticipant->id,
            'event_name' => $this->eventParticipant->event?->name ?? 'Unknown Event',
            'faculty_name' => $this->eventParticipant->participant?->name ?? 'Unknown Faculty',
            'message' => "{$this->eventParticipant->participant?->name} registered for '{$this->eventParticipant->event?->name}'.",
            'type' => 'new_registration',
        ];
    }
}
