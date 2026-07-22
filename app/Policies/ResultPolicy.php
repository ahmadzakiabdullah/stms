<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\User;

class ResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_results')
            || $user->hasRole(['super-admin', 'org-admin']);
    }

    public function view(User $user, Result $result): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $result->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_results')
            || $user->hasRole(['super-admin', 'org-admin']);
    }

    public function update(User $user, Result $result): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $result->organization_id
            && ($user->hasPermissionTo('manage_results') || $user->hasRole('org-admin'));
    }

    public function delete(User $user, Result $result): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $result->organization_id
            && ($user->hasPermissionTo('manage_results') || $user->hasRole('org-admin'));
    }
}
