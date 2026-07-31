<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    /**
     * Determine whether the user can view settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin');
    }

    /**
     * Determine whether the user can update settings.
     */
    public function update(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin');
    }
}
