<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        // Never inherit tenant state from a previous request (or a previous
        // job on a long-running worker). This also guarantees super-admin and
        // public requests cannot leak a tenant context set by a prior pipeline.
        TenantContext::reset();

        $user = $request->user();

        if ($user && ! $user->hasRole('super-admin')) {
            TenantContext::setOrganizationId($user->organization_id);
        } else {
            // Record the absence of scoping explicitly so "no organization" can
            // never be mistaken for a silently unscoped operation.
            TenantContext::setBypass($user ? 'super-admin' : 'guest');
        }

        TenantContext::setContext('http');

        try {
            return $next($request);
        } finally {
            TenantContext::reset();
        }
    }

    /**
     * Extra safety net for work that runs after the response has been sent.
     */
    public function terminate(Request $request, Response $response): void
    {
        TenantContext::reset();
    }
}
