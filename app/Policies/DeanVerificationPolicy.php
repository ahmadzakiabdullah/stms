<?php

namespace App\Policies;

use App\Models\EventParticipant;
use App\Models\User;

class DeanVerificationPolicy
{
    /**
     * Determine whether the user can view the dean verification dashboard.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('dean') && $user->participant_id !== null;
    }

    /**
     * Determine whether the user can verify (approve/reject) the given registration.
     */
    public function verify(User $user, EventParticipant $eventParticipant): bool
    {
        return $user->hasRole('dean')
            && $eventParticipant->participant_id === $user->participant_id;
    }
}
