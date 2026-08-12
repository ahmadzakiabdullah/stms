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

        // Keep locale switching working when an existing deployment has a
        // stale SESSION_PATH (for example /portal instead of /saf/portal).
        // The locale cookie is written by the locale endpoint and takes
        // precedence until the deployment session configuration is corrected.
        if ($request->cookie('app_locale')) {
            $sessionLocale = (string) $request->cookie('app_locale');
        }

        if (in_array($sessionLocale, $supportedLocales, true)) {
            App::setLocale($sessionLocale);
        }

        return $next($request);
    }
}
