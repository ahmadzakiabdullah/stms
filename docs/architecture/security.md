# Security Architecture

STMS uses a layered security model combining authentication, role-based access control, and tenant data isolation. Every request passes through authentication (Laravel Breeze), authorization (Spatie roles/permissions), and tenancy scoping before reaching business logic.

## Authentication

Laravel Breeze provides Inertia-based authentication scaffolding with login, registration, password reset, and email verification. Sessions are server-managed via Laravel's built-in session driver. Email verification is enforced for sensitive operations.

## Role-Based Access Control

Roles and permissions are managed via Spatie Laravel Permission v6. The system defines a base set of roles (Super Admin, Org Admin, Manager, Operator, Viewer) with granular permissions per domain entity. Permissions are cached for performance and invalidated on change. Authorization checks happen at two levels:

1. **Controller middleware** — `middleware('permission:...')` gates the route.
2. **Policy gates** — fine-grained checks within the controller method before Action execution.

## Multi-Tenancy Scoping

Beyond authentication and RBAC, every tenant-scoped query is automatically filtered by `organization_id` via a global scope. This ensures that even authenticated users with valid permissions cannot access data outside their organization. Super Admin users bypass organization scoping entirely via a `skipTenancy()` flag.

## Authorization Flow

```
Request → Auth (Breeze) → Route Middleware (Spatie) → Controller → Policy → Service/Action
```

Policies handle resource-specific rules (e.g., "can update tournament only if tournament.organization_id matches user's organization"). Form Requests handle input validation separately from authorization.
