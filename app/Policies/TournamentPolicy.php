<?php

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;

class TournamentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin');
    }

    public function view(User $user, Tournament $tournament): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $tournament->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin');
    }

    public function update(User $user, Tournament $tournament): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $tournament->organization_id && $user->hasRole('org-admin');
    }

    public function delete(User $user, Tournament $tournament): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $tournament->organization_id && $user->hasRole('org-admin');
    }
}
