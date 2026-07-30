<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Services\OrganizationService;

class CreateOrganization
{
    public function handle(array $data, ?OrganizationService $service = null): Organization
    {
        $service = $service ?? app(OrganizationService::class);

        return $service->createOrganization($data);
    }
}
