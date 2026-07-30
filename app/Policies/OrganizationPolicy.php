<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('org-admin')) {
            return true;
        }

        return $user->hasPermissionTo('view organizations');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $organization->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasPermissionTo('create organizations') || $user->hasRole('org-admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $organization->id && ($user->hasPermissionTo('edit organizations') || $user->hasRole('org-admin'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $organization->id && ($user->hasPermissionTo('delete organizations') || $user->hasRole('org-admin'));
    }
}
