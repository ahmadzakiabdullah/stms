<?php

namespace App\Queue\Middleware;

use App\Contracts\TenantAwareJob;
use App\Services\TenantContext;
use Closure;
use LogicException;

class SetTenantContext
{
    public function handle(object $job, Closure $next): mixed
    {
        TenantContext::reset();
        TenantContext::setContext('queue');

        if (! $job instanceof TenantAwareJob) {
            TenantContext::reset();

            throw new LogicException('Tenant queue middleware requires a TenantAwareJob.');
        }

        TenantContext::setOrganizationId($job->tenantOrganizationId());

        try {
            return $next($job);
        } finally {
            TenantContext::reset();
        }
    }
}
