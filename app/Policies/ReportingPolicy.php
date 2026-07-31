<?php

namespace App\Policies;

use App\Models\User;

class ReportingPolicy
{
    /**
     * Determine whether the user can view reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin')
            || $user->hasRole('org-admin')
            || $user->hasRole('sport-coordinator')
            || $user->hasRole('tournament-manager')
            || $user->hasRole('staff');
    }
}
