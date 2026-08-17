# Authentication Architecture

## Flows

- Case-insensitive username-or-email login through `AuthenticatedSessionController`; session is regenerated after success and login is throttled.
- Password reset and confirmation use Laravel's standard broker/controllers.
- Public registration routes remain registered, but `PUBLIC_REGISTRATION_ENABLED=false` makes `/register` return 404. When enabled, `DEFAULT_ORG_SLUG` must resolve or registration fails closed.
- Users belong to one organization; no tenant switcher or multi-membership exists.

## Email Verification

Operational route groups use:

```php
config('app.email_verification_required') ? ['auth', 'verified'] : ['auth']
```

The choice is made when routes boot. Profile routes remain auth-only so a user can correct an email. Therefore verification is **conditional**, not universally enforced.

Audit state:

- `.env.production.example` sets `EMAIL_VERIFICATION_REQUIRED=true`.
- Runtime workspace marked production reports it as `false`.
- Production external behavior cannot prove a user's verification requirement without an authorized test account.

## Session Management

Authentication state is server-side and shared to Inertia through `HandleInertiaRequests`. Logout invalidates the session.

Target production driver is Redis. The audited production workspace uses file sessions, which is not multi-instance safe and is tracked as P0.

## Authorization Boundary

Authentication only proves identity. Controllers/middleware must still call Policies/Gates. Several sensitive index actions currently omit `viewAny`; see the 17 August audit and `TODOS.md`.
