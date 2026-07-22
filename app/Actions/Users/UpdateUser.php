<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\UserService;

class UpdateUser
{
    public function handle(User $user, array $data, ?UserService $service = null): User
    {
        $service = $service ?? app(UserService::class);
        return $service->updateUser($user, $data);
    }
}
