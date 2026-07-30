<?php

namespace App\Policies;

use App\Models\Sport;
use App\Models\User;

class SportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasRole('sport-coordinator')) {
            return true;
        }

        return $user->hasPermissionTo('view sports');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Sport $sport): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $sport->organization_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasPermissionTo('create sports') || $user->hasRole('org-admin') || $user->hasRole('sport-coordinator');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Sport $sport): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $sport->organization_id && ($user->hasPermissionTo('edit sports') || $user->hasRole('org-admin'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Sport $sport): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $sport->organization_id && ($user->hasPermissionTo('delete sports') || $user->hasRole('org-admin'));
    }
}
