<?php

namespace App\Policies;

use App\Models\SportCategory;
use App\Models\User;

class SportCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasRole('sport-coordinator');
    }

    public function view(User $user, SportCategory $sportCategory): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $sportCategory->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin') || $user->hasRole('sport-coordinator');
    }

    public function update(User $user, SportCategory $sportCategory): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $sportCategory->organization_id
            && ($user->hasRole('org-admin') || $user->hasRole('sport-coordinator'));
    }

    public function delete(User $user, SportCategory $sportCategory): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $sportCategory->organization_id
            && ($user->hasRole('org-admin') || $user->hasRole('sport-coordinator'));
    }
}
