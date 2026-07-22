<?php

namespace App\Policies;

use App\Models\Participant;
use App\Models\User;

class ParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('org-admin')) {
            return true;
        }
        return $user->hasPermissionTo('view participants');
    }

    public function view(User $user, Participant $participant): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $participant->organization_id;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }
        return $user->hasPermissionTo('create participants') || $user->hasRole('org-admin');
    }

    public function update(User $user, Participant $participant): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $participant->organization_id && (
            $user->hasPermissionTo('edit participants') || $user->hasRole('org-admin')
        );
    }

    public function delete(User $user, Participant $participant): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $participant->organization_id && (
            $user->hasPermissionTo('delete participants') || $user->hasRole('org-admin')
        );
    }
}
