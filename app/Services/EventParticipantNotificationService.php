<?php

namespace App\Services;

use App\Models\EventParticipant;
use App\Models\User;
use App\Notifications\NewEventRegistration;

final class EventParticipantNotificationService
{
    public function notifyRegistration(EventParticipant $eventParticipant): void
    {
        $deanUsers = User::query()
            ->where('participant_id', $eventParticipant->participant_id)
            ->whereHas('roles', fn ($query) => $query->where('name', 'dean'))
            ->get();

        $adminUsers = User::query()
            ->where('organization_id', $eventParticipant->organization_id)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['super-admin', 'org-admin']))
            ->get();

        foreach ($deanUsers->concat($adminUsers)->unique('uuid') as $recipient) {
            $recipient->notify(new NewEventRegistration($eventParticipant));
        }
    }
}
