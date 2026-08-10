<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

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
