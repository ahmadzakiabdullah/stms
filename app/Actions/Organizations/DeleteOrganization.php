<?php

namespace App\Actions\Organizations;

use App\Models\Organization;
use App\Services\OrganizationService;

class DeleteOrganization
{
    public function handle(Organization $organization, ?OrganizationService $service = null): void
    {
        $service = $service ?? app(OrganizationService::class);
        $service->deleteOrganization($organization);
    }
}
