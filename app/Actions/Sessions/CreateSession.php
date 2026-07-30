<?php

namespace App\Actions\Sessions;

use App\Models\Session;
use App\Services\SessionService;

class CreateSession
{
    public function handle(array $data, ?SessionService $service = null): Session
    {
        $service = $service ?? app(SessionService::class);

        return $service->createSession($data);
    }
}
