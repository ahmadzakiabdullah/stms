<?php

namespace App\Actions\Registrations;

use App\Models\Registration;
use App\Services\RegistrationService;

class CreateRegistration
{
    public function handle(array $data, ?RegistrationService $service = null): Registration
    {
        $service = $service ?? app(RegistrationService::class);
        return $service->createRegistration($data);
    }
}
