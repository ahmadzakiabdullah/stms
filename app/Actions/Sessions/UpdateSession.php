<?php

namespace App\Actions\Sessions;

use App\Models\Session;
use App\Services\SessionService;

class UpdateSession
{
    public function handle(Session $session, array $data, ?SessionService $service = null): Session
    {
        $service = $service ?? app(SessionService::class);

        return $service->updateSession($session, $data);
    }
}
