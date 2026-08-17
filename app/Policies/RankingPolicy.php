<?php

namespace App\Policies;

use App\Models\User;

class RankingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super-admin', 'org-admin', 'admin-sport']);
    }
}
