<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Services\UserService;

class CreateUser
{
    public function handle(array $data, ?UserService $service = null): User
    {
        $service = $service ?? app(UserService::class);
        return $service->createUser($data);
    }
}
