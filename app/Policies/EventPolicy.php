<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasRole('tournament-manager')) {
            return true;
        }

        return $user->hasPermissionTo('view events');
    }

    public function view(User $user, Event $event): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $event->organization_id;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasPermissionTo('create events') || $user->hasRole('tournament-manager') || $user->hasRole('org-admin');
    }

    public function update(User $user, Event $event): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $event->organization_id && ($user->hasPermissionTo('edit events') || $user->hasRole('org-admin'));
    }

    public function delete(User $user, Event $event): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $event->organization_id && ($user->hasPermissionTo('delete events') || $user->hasRole('org-admin'));
    }
}
