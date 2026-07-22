<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\UserService;

class DeleteUser
{
    public function handle(User $user, ?UserService $service = null): void
    {
        $service = $service ?? app(UserService::class);
        $service->deleteUser($user);
    }
}
