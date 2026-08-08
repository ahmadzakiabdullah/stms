<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Apply locale from session when it is supported.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', []);
        if (! is_array($supportedLocales) || $supportedLocales === []) {
            $supportedLocales = ['en'];
        }

        $sessionLocale = (string) config('app.locale');

        if ($request->hasSession()) {
            $sessionLocale = (string) $request->session()->get('locale', $sessionLocale);
        }

        if (in_array($sessionLocale, $supportedLocales, true)) {
            App::setLocale($sessionLocale);
        }

        return $next($request);
    }
}
