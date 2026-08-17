# ADR-009: Request-Scoped Tenant Context

**Status:** Accepted  
**Date:** 2026-08-07  
**Deciders:** STMS Architecture Team  
**Supersedes/extends:** ADR-002 (implementation of the runtime tenant context)

## Context

Tenant scoping was held in process-global static properties on
`App\Services\TenantContext`. Under PHP-FPM that never surfaced because each
request gets a fresh process; but queue workers and long-running servers (e.g.
Octane) reuse one process, so a tenant context set by one request/job could leak
into the next and scope — or un-scope — queries it should not.

Guests and super-admins also "bypassed" scoping implicitly by resolving to a
null `organization_id` (so the global scope adds `WHERE` nothing). Nothing
signalled that the missing `organization_id` was intentional, so an accidental
unscoped query was indistinguishable from a deliberate cross-tenant read.

## Decision

Move the tenant state from static properties to a **container-scoped singleton**
instance of `App\Services\TenantContext`, bound in `AppServiceProvider::register`.
The static methods are retained as a thin facade over that instance
(`app(TenantContext::class)`) for backward compatibility.

The `SetTenantContext` middleware now:

- resets the context at the **start** of every request, so no tenant from a
  previous request is ever inherited;
- resolves the authenticated user first, and only then binds their
  `organization_id` — super-admin and guest are recorded as an explicit,
  auditable **bypass** (with a reason) rather than an implicit null;
- records the context kind (`http`);
- always cleans up in a `finally` block and additionally via a `terminate`
  hook, so even exceptions or post-response work cannot leave state behind.

New explicit controls were added:

- `setBypass(reason)`, `isBypassing()`, `bypassReason()` — an audited opt-out;
- `isInitialized()` — whether this request/pipeline has bound a tenant or
  recorded a bypass;
- `requireOrganization()` — **fail-closed**: throws `LogicException` when a
  tenant-required operation has no tenant available or is running under an
  explicit bypass.

`isConsole()` / `isQueue()` now consult the recorded context only instead of
guessing from `runningInConsole()`, so the console (and later, queue jobs) must
explicitly receive their organization.

## Consequences

Positive:
- No cross-request cross-tenant leakage under Octane or queue workers.
- Bypass (super-admin / guest / console bootstrap) is explicit, auditable, and
  greppable (`setBypass`).
- Fail-closed helper makes it impossible for tenant-required code to run
  silently unscoped; audit points exactly where a bypass is intentional.

Negative:
- The static facade hides the container access; callers must keep using the
  facade (all existing callers do — global scope + tests).
- Every tenant-aware HTTP/job/command path must continue to bind or explicitly
  bypass the context; missing integration remains a fail-closed operational risk.

## Implementation Status

Implemented 2026-08-07 and extended with a tenant-aware job contract/middleware.
Lifecycle and queue behavior have tests, and the static tenant-bypass allowlist
check passes as of 2026-08-17. The full PHPUnit suite is currently red for
unrelated draw-contract failures, so do not describe the repository as fully
green. Read-authorization gaps identified in the current audit also remain.

## References

- ADR-002: Multi-Tenant Design
- ADR-009 supersedes the static implementation described in ADR-002's status
- `STMS_HARDENING_PLAN.md` — P0 section 1.1
- `app/Services/TenantContext.php`
- `app/Http/Middleware/SetTenantContext.php`
- `app/Models/Concerns/BelongsToOrganization.php`
- `tests/Feature/TenantContextLifecycleTest.php`
- `tests/Feature/TenantIsolationTest.php`
