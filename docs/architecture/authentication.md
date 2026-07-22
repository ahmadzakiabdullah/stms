# Authentication Architecture

Authentication is provided by Laravel Breeze with Inertia.js scaffolding, which gives a complete, secure authentication system out of the box. The frontend handles UI rendering while all auth logic runs server-side via Laravel's built-in authentication controllers.

## Scaffolded Flows

- **Login** — Email/password authentication via `AuthenticatedSessionController`. Rate-limited to 5 attempts per minute. Successful login regenerates the session and redirects to the dashboard.
- **Registration** — `RegisteredUserController` creates a new User and a default Organization simultaneously. The user is automatically assigned the `Org Admin` role for their organization.
- **Password Reset** — `PasswordResetLinkController` sends a reset link via email. `NewPasswordController` handles the token verification and password update.
- **Email Verification** — `EmailVerificationPromptController` and `VerifyEmailController` enforce verified emails before accessing sensitive routes. Verification is optional but recommended; configurable via `fortify.php`.

## Session Management

All authentication state is managed server-side. Inertia receives the authenticated user via the `HandleInertiaRequests` middleware's `share()` method, which exposes `auth.user` globally to the frontend. Logout destroys the session and clears all tenant-scoped cache keys.

## Post-Authentication Setup

After login, the system resolves the user's active organization from session data (`session('active_organization_id')`). If the user belongs to multiple organizations, an organization switcher is shown on the dashboard. The active organization drives all tenant-scoped queries during the session.

## Route Protection

Authentication middleware is applied to all route groups except the auth pages themselves. Route grouping:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard and all protected routes
});
Route::middleware('guest')->group(function () {
    // Login, Register, Forgot Password
});
```
