<?php

namespace App\Policies;

use App\Models\EventParticipant;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class EventParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin') || $this->hasPermission($user, 'view event participants');
    }

    public function view(User $user, EventParticipant $eventParticipant): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $eventParticipant->event?->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin') || $this->hasPermission($user, 'create event participants');
    }

    public function update(User $user, EventParticipant $eventParticipant): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $eventParticipant->event?->organization_id && (
            $user->hasRole('org-admin') || $this->hasPermission($user, 'edit event participants')
        );
    }

    public function delete(User $user, EventParticipant $eventParticipant): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $eventParticipant->event?->organization_id && (
            $user->hasRole('org-admin') || $this->hasPermission($user, 'delete event participants')
        );
    }

    private function hasPermission(User $user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }
}
