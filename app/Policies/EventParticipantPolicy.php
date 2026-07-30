<?php

namespace App\Policies;

use App\Models\EventParticipant;
use App\Models\User;

class EventParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasPermissionTo('view event participants');
    }

    public function view(User $user, EventParticipant $eventParticipant): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $eventParticipant->event->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasPermissionTo('create event participants');
    }

    public function update(User $user, EventParticipant $eventParticipant): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $eventParticipant->event->organization_id && (
            $user->hasRole('org-admin') || $user->hasPermissionTo('edit event participants')
        );
    }

    public function delete(User $user, EventParticipant $eventParticipant): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $eventParticipant->event->organization_id && (
            $user->hasRole('org-admin') || $user->hasPermissionTo('delete event participants')
        );
    }
}
