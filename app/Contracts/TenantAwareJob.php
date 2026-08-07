<?php

namespace App\Contracts;

interface TenantAwareJob
{
    public function tenantOrganizationId(): string;
}
