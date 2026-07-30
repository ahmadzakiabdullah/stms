<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Services\OrganizationService;

class UpdateOrganization
{
    public function handle(Organization $organization, array $data, ?OrganizationService $service = null): Organization
    {
        $service = $service ?? app(OrganizationService::class);

        return $service->updateOrganization($organization, $data);
    }
}
