<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $model->organization_id && $user->hasRole('org-admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $model->organization_id
            && $user->hasRole('org-admin')
            && ! $model->hasRole('super-admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $model->organization_id
            && $user->hasRole('org-admin')
            && ! $model->hasRole('super-admin');
    }
}
