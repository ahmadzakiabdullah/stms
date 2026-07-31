<?php

namespace App\Policies;

use App\Models\User;

class ActivityLogPolicy
{
    /**
     * Determine whether the user can view any activity logs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin');
    }
}
