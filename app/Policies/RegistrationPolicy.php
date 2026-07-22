<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('super-admin') || $user->hasRole('org-admin')) {
            return true;
        }
        return $user->hasPermissionTo('view registrations');
    }

    public function view(User $user, Registration $registration): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $registration->organization_id;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }
        return $user->hasPermissionTo('create registrations') || $user->hasRole('org-admin');
    }

    public function update(User $user, Registration $registration): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $registration->organization_id && (
            $user->hasPermissionTo('edit registrations') || $user->hasRole('org-admin')
        );
    }

    public function delete(User $user, Registration $registration): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->organization_id === $registration->organization_id && (
            $user->hasPermissionTo('delete registrations') || $user->hasRole('org-admin')
        );
    }
}
