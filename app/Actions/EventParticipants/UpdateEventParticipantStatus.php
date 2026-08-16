<?php

namespace App\Actions\EventParticipants;

use App\Models\EventParticipant;
use App\Notifications\EventParticipantConfirmed;
use App\Notifications\EventParticipantRejected;

class UpdateEventParticipantStatus
{
    public function handle(EventParticipant $eventParticipant, string $status): void
    {
        $eventParticipant->update(['status' => $status]);

        foreach ($eventParticipant->participant?->users ?? [] as $user) {
            $user->notify($status === 'confirmed'
                ? new EventParticipantConfirmed($eventParticipant)
                : new EventParticipantRejected($eventParticipant));
        }
    }
}
