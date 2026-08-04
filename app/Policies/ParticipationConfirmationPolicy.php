<?php

namespace App\Policies;

use App\Models\User;

class ParticipationConfirmationPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasAnyRole(['super-admin', 'org-admin'])) {
            return $user->organization_id !== null;
        }

        return $user->participant_id !== null
            && $user->hasAnyRole(['faculty-representative', 'dean']);
    }
}
