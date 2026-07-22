<?php

namespace App\Policies;

use App\Models\Fixture;
use App\Models\User;

class MatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_matches')
            || $user->hasRole(['super-admin', 'org-admin']);
    }

    public function view(User $user, Fixture $match): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $match->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_matches')
            || $user->hasRole(['super-admin', 'org-admin']);
    }

    public function update(User $user, Fixture $match): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $match->organization_id
            && ($user->hasPermissionTo('manage_matches') || $user->hasRole('org-admin'));
    }

    public function delete(User $user, Fixture $match): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $match->organization_id
            && ($user->hasPermissionTo('manage_matches') || $user->hasRole('org-admin'));
    }
}
