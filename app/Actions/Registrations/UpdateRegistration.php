<?php

namespace App\Actions\Registrations;

use App\Models\Registration;
use App\Services\RegistrationService;

class UpdateRegistration
{
    public function handle(Registration $registration, array $data, ?RegistrationService $service = null): Registration
    {
        $service = $service ?? app(RegistrationService::class);

        return $service->updateRegistration($registration, $data);
    }
}
