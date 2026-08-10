<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): Response
    {
        $request->session()->forget('url.intended');

        if (! config('app.email_verification_required')) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/VerifyEmail');
    }
}
