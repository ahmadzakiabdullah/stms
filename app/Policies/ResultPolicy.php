<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\User;

class ResultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_results')
            || $user->hasRole(['super-admin', 'org-admin', 'admin-sport']);
    }

    public function view(User $user, Result $result): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $result->organization_id;
    }

    public function create(User $user, ?string $sportId = null): bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('org-admin')) {
            return true;
        }

        if ($user->hasRole('admin-sport')) {
            return $sportId !== null && $user->canManageSport($sportId);
        }

        return $user->hasPermissionTo('manage_results');
    }

    public function update(User $user, Result $result): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id !== $result->organization_id) {
            return false;
        }

        if ($user->hasRole('org-admin')) {
            return true;
        }

        return $user->canManageSport($result->match->event->sport_id);
    }

    public function delete(User $user, Result $result): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($user->organization_id !== $result->organization_id) {
            return false;
        }

        if ($user->hasRole('org-admin')) {
            return true;
        }

        return $user->canManageSport($result->match->event->sport_id);
    }
}
