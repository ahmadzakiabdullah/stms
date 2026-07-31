<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    /**
     * Determine whether the user can view the dashboard.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }
}
