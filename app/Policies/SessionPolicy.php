<?php

namespace App\Policies;

use App\Models\Session;
use App\Models\User;

class SessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin');
    }

    public function view(User $user, Session $session): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $session->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('org-admin');
    }

    public function update(User $user, Session $session): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $session->organization_id && $user->hasRole('org-admin');
    }

    public function delete(User $user, Session $session): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $session->organization_id && $user->hasRole('org-admin');
    }
}
