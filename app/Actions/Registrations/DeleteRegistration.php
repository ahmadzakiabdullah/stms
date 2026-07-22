<?php

namespace App\Actions\Registrations;

use App\Models\Registration;
use App\Services\RegistrationService;

class DeleteRegistration
{
    public function handle(Registration $registration, ?RegistrationService $service = null): void
    {
        $service = $service ?? app(RegistrationService::class);
        $service->deleteRegistration($registration);
    }
}
