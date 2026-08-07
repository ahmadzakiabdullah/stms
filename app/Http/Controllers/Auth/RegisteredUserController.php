<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        abort_unless(config('app.public_registration'), 404);

        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('app.public_registration'), 404);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|lowercase|alpha_dash|min:3|max:64|unique:users,username',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $defaultOrgSlug = config('app.default_org_slug');

        if (! is_string($defaultOrgSlug) || $defaultOrgSlug === '') {
            throw ValidationException::withMessages([
                'email' => 'Registration is unavailable because no organization has been configured.',
            ]);
        }

        $organization = Organization::where('slug', $defaultOrgSlug)->first();

        if (! $organization) {
            throw ValidationException::withMessages([
                'email' => 'Registration is unavailable for the configured organization.',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'organization_id' => $organization->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
