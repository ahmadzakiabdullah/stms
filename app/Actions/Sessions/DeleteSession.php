<?php

namespace App\Actions\Sessions;

use App\Models\Session;
use App\Services\SessionService;

class DeleteSession
{
    public function handle(Session $session, ?SessionService $service = null): void
    {
        $service = $service ?? app(SessionService::class);
        $service->deleteSession($session);
    }
}
