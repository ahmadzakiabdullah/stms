<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    /**
     * Switch the current locale for the session.
     * Works for guests and authenticated users alike (no auth middleware).
     */
    public function update(Request $request, string $locale)
    {
        $available = array_keys(config('locales.available_locales', []));

        if (! in_array($locale, $available, true)) {
            abort(404);
        }

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return back();
    }
}
