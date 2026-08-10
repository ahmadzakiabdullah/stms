<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HealthEndpointToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $token = (string) config('app.health.token');

            if ($token === '' || ! hash_equals($token, (string) $request->header('X-Health-Token'))) {
                abort(404);
            }
        }

        return $next($request);
    }
}
