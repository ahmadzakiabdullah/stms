# Authentication Architecture

Authentication is provided by Laravel Breeze with Inertia.js scaffolding, which gives a complete, secure authentication system out of the box. The frontend handles UI rendering while all auth logic runs server-side via Laravel's built-in authentication controllers.

## Scaffolded Flows

- **Login** — Email/password authentication via `AuthenticatedSessionController`. Rate-limited to 5 attempts per minute. Successful login regenerates the session and redirects to the dashboard.
- **Registration** — Public registration is controlled by `PUBLIC_REGISTRATION_ENABLED` and defaults to disabled in production. When enabled, it requires a valid `DEFAULT_ORG_SLUG`; missing or invalid tenant configuration fails closed. It does not create an organization or assign an admin role. Invitation-based onboarding remains recommended for multi-tenant production use.
- **Password Reset** — `PasswordResetLinkController` sends a reset link via email. `NewPasswordController` handles the token verification and password update.
- **Email Verification** — `EmailVerificationPromptController` and `VerifyEmailController` enforce verified emails before accessing sensitive routes. Verification is optional but recommended; configurable via `fortify.php`.

## Session Management

All authentication state is managed server-side. Inertia receives the authenticated user via the `HandleInertiaRequests` middleware's `share()` method, which exposes `auth.user` globally to the frontend. Logout destroys the session and clears all tenant-scoped cache keys.

## Post-Authentication Setup

Each user currently has one `organization_id`. Tenant-aware models use the authenticated user's organization through `BelongsToOrganization`; there is no organization switcher or multi-membership session context.

## Route Protection

Authentication middleware is applied to all route groups except the auth pages themselves. Route grouping:

```php
Route::middleware('auth')->group(function () {
    // Protected application routes
});
Route::middleware('guest')->group(function () {
    // Login, Register, Forgot Password
});
```

Only `/dashboard` currently includes the `verified` middleware; the broader authenticated route group does not require verified email.
