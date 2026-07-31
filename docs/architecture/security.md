# Security Architecture

STMS uses a layered security model combining authentication, role-based access control, and tenant data isolation. Every request passes through authentication (Laravel Breeze), authorization (Spatie roles/permissions), and tenancy scoping before reaching business logic.

## Authentication

Laravel Breeze provides Inertia-based authentication scaffolding with login, registration, password reset, and email verification. Sessions are server-managed via Laravel's built-in session driver. Email verification is enforced for sensitive operations.

## Role-Based Access Control

Roles and permissions are managed via Spatie Laravel Permission v6. Seeded roles include `super-admin`, `org-admin`, `staff`, `faculty-representative`, and `dean`, with granular permissions per domain entity. Authorization is primarily enforced through controller `Gate::authorize()` calls and policies.

1. **Authentication middleware** — protects the application route group.
2. **Policy gates** — controller methods perform resource/action authorization before mutations and sensitive reads.

## Multi-Tenancy Scoping

Beyond authentication and RBAC, tenant-aware model queries are filtered by `organization_id` via a global scope when an authenticated non-super-admin user exists. Super-admin users bypass that constraint. The trait also exposes explicit scope-removal helpers, so cross-tenant callers must authorize and scope their use carefully.

## Authorization Flow

```
Request → Auth (Breeze) → Controller → Policy/Gate → Service/Action
```

Policies handle resource-specific rules (e.g., "can update tournament only if tournament.organization_id matches user's organization"). Form Requests handle input validation separately from authorization.

## Production Hardening Controls

- Public registration defaults off in production and requires an explicit tenant slug when enabled.
- Reverse-proxy trust is configured through a comma-separated `TRUSTED_PROXIES` allowlist; an empty value trusts no additional proxies.
- Routine production seeding skips demo users and SAF operational data. Direct demo seeding requires an explicit override.
- Docker Compose requires externally supplied application/database/Redis secrets and keeps MySQL/Redis on the internal Compose network.
