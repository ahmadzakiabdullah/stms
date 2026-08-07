<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            $header = config('app.csp_report_only', true)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set(
                $header,
                "default-src 'self'; ".
                "script-src 'self'; ".
                "style-src 'self' 'unsafe-inline'; ".
                "img-src 'self' data: blob:; ".
                "font-src 'self' data:; ".
                "connect-src 'self'; ".
                "object-src 'none'; ".
                "frame-ancestors 'self'; ".
                "form-action 'self'; ".
                "base-uri 'self'; ".
                'upgrade-insecure-requests; '.
                'block-all-mixed-content'
            );
        }

        return $response;
    }
}
