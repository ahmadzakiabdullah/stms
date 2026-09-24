<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $request->attributes->set('csp_nonce', base64_encode(random_bytes(16)));
        }

        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
            $isReportOnly = (bool) config('app.csp_report_only', true);

            $header = $isReportOnly
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $nonce = (string) $request->attributes->get('csp_nonce', '');
            $scriptSrc = "script-src 'self'".($nonce !== '' ? " 'nonce-{$nonce}'" : '').'; ';

            // React uses DOM inline styles for dynamic widths/theme previews.
            // Keep scripts nonce-protected, while allowing those style attributes
            // and CSSOM updates to render correctly under the enforced CSP.
            // Do not combine unsafe-inline with a nonce: browsers ignore
            // unsafe-inline whenever a nonce/hash is present in style-src.
            $styleSrc = "style-src 'self' 'unsafe-inline'; ";

            $fontSrc = $isReportOnly
                ? "font-src 'self' data:; "
                : "font-src 'self' data:; ";

            $tailDirectives = $isReportOnly
                ? ''
                : 'upgrade-insecure-requests; block-all-mixed-content';

            $response->headers->set(
                $header,
                "default-src 'self'; ".
                $scriptSrc.
                $styleSrc.
                "style-src-attr 'unsafe-inline'; ".
                "img-src 'self' data: blob:; ".
                $fontSrc.
                "connect-src 'self'; ".
                "object-src 'none'; ".
                "frame-ancestors 'self'; ".
                "form-action 'self'; ".
                "base-uri 'self'; ".
                $tailDirectives
            );
        }

        return $response;
    }
}
