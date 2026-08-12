<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetTenantContext;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $webAppend = [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SecurityHeaders::class,
            SetTenantContext::class,
        ];

        if (class_exists('App\\Http\\Middleware\\SetLocale')) {
            array_unshift($webAppend, 'App\\Http\\Middleware\\SetLocale');
        }

        $middleware->web(append: $webAppend);

        $middleware->replace(
            Illuminate\Http\Middleware\TrustProxies::class,
            TrustProxies::class,
        );

        // Locale is a non-sensitive preference endpoint. Exclude it from
        // CSRF validation so subfolder deployments with stale cookie paths
        // can still switch language and persist the locale cookie.
        $middleware->validateCsrfTokens(except: ['locale']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
