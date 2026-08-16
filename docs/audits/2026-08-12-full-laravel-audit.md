# Full Laravel Security, Architecture, and Performance Audit

**Audit date:** 12 August 2026
**Scope:** Laravel/PHP application, React/Inertia integration, routes, models, policies, requests, services/actions, migrations, seeders, tests, configuration, and locked dependencies.
**Method:** Static review, targeted source inspection, Composer/npm audit, tenant guard scripts, PHPUnit, TypeScript, Vite build, and Pint validation. This is a source-code audit; it is not a penetration test or an infrastructure audit.

> **Remediation update — 12 August 2026:** SEC-01, DATA-01, and TEST-01 have been remediated in the working tree. Tenant/role/relation regression coverage was added, both dummy seeders are production-guarded, and local validation now passes 407 tests / 1,635 assertions, Pint, TypeScript, tenant checks, inventory checks, and the mapped-drive Vite production build. Production role/activity-log review and connected CI evidence remain open before release.

## A. Executive Summary

**System health: MODERATE (Sederhana), with one critical authorization defect requiring immediate remediation.**

The system has a stronger-than-average Laravel baseline: Eloquent is used safely, tenant-aware domain models have organization scoping, UUIDs and database constraints are widespread, SVG content is sanitized, policies and Form Requests exist, security headers are implemented, secrets are not committed, and both Composer and production npm dependency audits are clean.

Release approval should nevertheless be blocked until **SEC-01** is fixed. An `org-admin` can submit arbitrary global role IDs and a foreign `organization_id` through user management. `UserService` trusts those values and calls `syncRoles()` without excluding `super-admin`. This creates a direct privilege-escalation and cross-tenant account-creation path.

Validation also exposed five failing email-verification security tests, six Pint violations, stale inventory documentation, and a build failure under the UNC execution environment. The latter is environment-specific and does not by itself prove that the frontend source cannot build on the deployment host.

### Positive controls confirmed

- No user-controlled SQL interpolation, `DB::unprepared()`, raw Blade output, `dangerouslySetInnerHTML`, or debug terminators were found.
- CSRF middleware is active; only the non-sensitive `locale` preference endpoint is exempted.
- Tenant bypasses are explicit and the repository allowlist check passes.
- Public SVG uploads pass through `enshrined/svg-sanitize`; generated filenames are UUID-based.
- Models use explicit `$fillable`; `.env` is not tracked.
- Composite indexes cover the main tenant/event/status query paths.
- `composer audit --locked` and `npm audit --omit=dev` report no vulnerable locked production packages.

## B. Vulnerabilities and Main Issues

### CRITICAL

#### SEC-01 — Org admin can assign `super-admin` and create/update users across tenants

**Evidence:** `app/Http/Requests/User/StoreUserRequest.php:24-29`, `app/Http/Requests/User/UpdateUserRequest.php`, `app/Http/Controllers/UserController.php:75-104`, `app/Services/UserService.php:17-76`.

The requests accept any existing organization, participant, and global role IDs. The controller only supplies the current organization when `organization_id` is empty; it does not overwrite a foreign submitted value. The service then persists the foreign tenant ID and synchronizes every submitted role, including `super-admin`.

**Impact:** vertical privilege escalation to system-wide super-admin, cross-organization account creation, and possible cross-tenant data access. This is an OWASP A01 Broken Access Control issue.

**Immediate action:** disable or restrict user-management mutation routes for non-super-admins until the patch is deployed; review users and role/activity logs for unexpected super-admin assignments.

### HIGH

#### SEC-02 — Other tenant-owned foreign keys rely on generic `exists` rules

**Evidence:** participant, event, registration, session, tournament, match, and result Form Requests use rules such as `exists:organizations,id`, `exists:events,id`, or `exists:participants,id` without tenant predicates. `StoreParticipantRequest` accepts a supplied `organization_id`, while `ParticipantService` only defaults it when empty.

Global Eloquent scopes do not automatically constrain Laravel's database-backed `exists` validator. Some downstream scoped model lookups fail safely, but creation flows that trust validated arrays may persist foreign tenant IDs.

**Impact:** inconsistent tenant isolation and possible cross-tenant relationships/data creation. Audit every tenant-owned input, not only users.

#### DATA-01 — Dummy seeders can mutate production tournament data

**Evidence:** `database/seeders/DummyFutsalMenSeeder.php` and `database/seeders/DummyFootballMensResultsSeeder.php` contain no production guard. The football seeder overwrites results and marks every selected fixture completed; the futsal seeder confirms registrations and inserts synthetic squad records. `SAF2026DataSeeder` already demonstrates the required guarded pattern.

**Impact:** accidental corruption of live registrations, squads, fixtures, results, rankings, and notifications/reporting assumptions.

### MEDIUM

#### TEST-01 — Email-verification security suite is failing

**Evidence:** five failures in `tests/Feature/VerifiedMiddlewareTest.php`; protected routes returned HTTP 200 instead of redirect when the test set verification as required.

The route middleware is selected while routes are loaded, whereas the tests change configuration later. Production may still enforce verification when configuration is correct at boot, but the test currently does not prove that control. Treat this as a release-gate failure until corrected and tested with both enabled/disabled configurations.

#### PERF-01 — Notifications run synchronously despite queue infrastructure

**Evidence:** all four classes in `app/Notifications` extend `Notification` but do not implement `ShouldQueue`. Controllers notify users inside loops (`EventParticipantController`, `DeanVerificationController`, and `ResultController`). `.env.example` selects Redis queueing, but these notifications do not use it.

**Impact:** registration/result requests slow down with recipient count; mail-channel additions could make writes time out or partially fail.

#### PERF-02 — Public portal loads and maps the full fixture set on every request

**Evidence:** `app/Services/PublicPortalService.php:20-66` calls `get()` for every fixture in the public session, maps all matches, then applies the display limit in memory. It also executes repeated event and settings queries and recalculates medal tallies.

**Impact:** response time and memory grow linearly with tournament history. Cache the public payload and query upcoming/recent result subsets independently; calculate aggregate counts in SQL.

#### ARCH-01 — EventParticipantController remains a 424-line mixed-responsibility controller

It performs validation, authorization, registration orchestration, deadline rules, batching, notification recipient discovery, squad CRUD, and quota workflow. Similar direct validation remains in several controllers.

**Impact:** policy drift, duplicated rules, difficult tests, and higher regression risk. Extract Form Requests plus action/service classes and transaction boundaries.

#### OPS-01 — Security-safe production defaults are not represented by `.env.example`

`.env.example` uses `APP_DEBUG=true` and `CSP_REPORT_ONLY=true`. These are reasonable local-development values but risky if copied to production without an explicit deployment overlay. Secure production guidance should require debug off, CSP enforcement, secure cookies under HTTPS, and verified mail/queue workers.

#### OBS-01 — Several broad exception catches hide degraded data

`HandleInertiaRequests` and `ReportingController` catch `Throwable` and return empty/default payloads without consistently logging the exception. The dashboard does log its fallbacks, which is the preferred pattern.

**Impact:** operational failures can look like valid empty data and delay incident detection.

### LOW

#### QA-01 — Quality gates are not fully green

- PHPUnit: **401 tests, 396 passed, 5 failed, 1,615 assertions**.
- Pint: six files require formatting, including `SetLocale.php`, `routes/web.php`, and `DummyFutsalMenSeeder.php`.
- Inventory check: the remediated working tree contains **125 routes, 59 migrations, 39 controllers, 38 pages, and 92 test files**.
- TypeScript check passed when invoked directly.
- Vite build failed while resolving Vite from a UNC path. Re-run from a mapped/local drive or the actual deployment working directory before release.

#### ARCH-02 — Form Request authorization is often only `user() !== null`

Controllers generally call policies afterward, so this is mostly defense-in-depth rather than a current bypass. Moving the policy decision into each Form Request makes the request independently safe and avoids future controller omissions.

#### PERF-03 — Public/report exports materialize collections in memory

Export and ranking/report paths use `get()` and collection maps. Acceptable for the present SAF dataset, but use query-backed/chunked exports and queued generation before supporting large multi-tenant tournaments.

## C. Recommended Refactoring — Before vs After

### 1. Enforce tenant and role boundaries in user creation

**Before**

```php
if (! $currentUser->hasRole('super-admin') && empty($data['organization_id'])) {
    $data['organization_id'] = $currentUser->organization_id;
}

$roles = Role::whereIn('id', $data['roles'])->get();
$user->syncRoles($roles);
```

**After**

```php
if (! $currentUser->hasRole('super-admin')) {
    $data['organization_id'] = TenantContext::requireOrganization();
    $data['participant_id'] = Participant::query()
        ->whereKey($data['participant_id'] ?? null)
        ->value('id');

    $data['roles'] = Role::query()
        ->whereIn('id', $data['roles'] ?? [])
        ->where('name', '!=', 'super-admin')
        ->pluck('id')
        ->all();
}

DB::transaction(fn () => $userService->createUser($data));
```

Also reject, rather than silently rewrite, unauthorized tenant/role values where possible; add explicit tests proving org admins cannot assign super-admin or reference another tenant.

### 2. Use tenant-aware validation

**Before**

```php
'event_id' => ['required', 'uuid', 'exists:events,id'],
'participant_id' => ['required', 'uuid', 'exists:participants,id'],
```

**After**

```php
$organizationId = TenantContext::requireOrganization();

'event_id' => [
    'required', 'uuid',
    Rule::exists('events', 'id')->where('organization_id', $organizationId),
],
'participant_id' => [
    'required', 'uuid',
    Rule::exists('participants', 'id')->where('organization_id', $organizationId),
],
```

For super-admin workflows, require an explicit selected tenant context instead of an implicit unscoped query.

### 3. Guard destructive demo seeders

**Before**

```php
public function run(): void
{
    $event = Event::query()->whereHas(/* ... */)->firstOrFail();
    // Mutate registrations/results...
}
```

**After**

```php
public function run(): void
{
    if (app()->isProduction() && ! filter_var(env('ALLOW_DEMO_SEEDING', false), FILTER_VALIDATE_BOOL)) {
        throw new RuntimeException('Dummy seeding is disabled in production.');
    }

    DB::transaction(fn () => $this->seedApprovedEvent());
}
```

Prefer factories for test data. If an operator-facing fixture is required, demand an explicit organization/event UUID and `--force`, show a mutation count, and write an activity/audit entry.

### 4. Queue notifications after a successful transaction

**Before**

```php
class NewEventRegistration extends Notification
{
}

foreach ($recipients as $recipient) {
    $recipient->notify(new NewEventRegistration($registration));
}
```

**After**

```php
class NewEventRegistration extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $afterCommit = true;
}

Notification::send($recipients, new NewEventRegistration($registration));
```

Add queue retry/backoff rules, failed-job alerting, idempotency expectations, and a worker health check.

### 5. Split public portal queries and cache the payload

**Before**

```php
$fixtures = Fixture::query()->where(/* session */)->with(/* ... */)->get();
$matches = $fixtures->map(fn (Fixture $fixture) => $this->matchData($fixture));
$upcoming = $matches->whereIn('status', ['scheduled', 'in_progress'])->take($limit);
```

**After**

```php
return Cache::remember("public-portal:{$session->id}:v1", now()->addMinutes(2), function () use ($session, $limit) {
    $upcoming = $this->fixtureQuery($session)
        ->whereIn('status', ['scheduled', 'in_progress'])
        ->orderByRaw('scheduled_at IS NULL, scheduled_at')
        ->limit($limit ?? 100)
        ->get();

    $results = $this->fixtureQuery($session)
        ->where('status', 'completed')
        ->latest('scheduled_at')
        ->limit($limit ?? 100)
        ->get();

    return $this->presentPortal($session, $upcoming, $results);
});
```

Invalidate the versioned key after fixture/result/event/setting mutations.

## D. Prioritized Action Plan

### P0 — Before the next deployment

1. Temporarily restrict user create/update to super-admin, or remove role and tenant fields from org-admin requests.
2. Patch user creation/update so org admins are forcibly scoped to their tenant and can never assign `super-admin`.
3. Add regression tests for cross-tenant organization/participant/sport IDs and super-admin role assignment.
4. Review the database and activity logs for unexpected super-admin roles, cross-tenant users, and recent role changes; rotate affected credentials if found.
5. Add production guards to both dummy seeders and remove them from normal `DatabaseSeeder` paths.
6. Repair the five email-verification tests and require the full security suite to pass.

### P1 — Within one sprint

1. Apply tenant-aware `Rule::exists(...)->where('organization_id', ...)` across all tenant-owned Form Requests.
2. Refactor `EventParticipantController` into dedicated registration, batch registration, status, and squad actions with Form Requests and transactions.
3. Queue notifications with `ShouldQueue`/`afterCommit`; monitor failed jobs.
4. Cache and paginate/split public portal fixture and ranking queries.
5. Log broad fallback exceptions with route, tenant, correlation ID, and exception context.
6. Make CI run Composer audit, npm audit, tenant bypass check, inventory check, PHPUnit, Pint, typecheck, and production build from a non-UNC build workspace.

### P2 — Within 30–60 days

1. Add query-count tests or Laravel Telescope/Debugbar in non-production to detect N+1 regressions.
2. Convert large exports to queued, chunked generation with authorization re-checks at execution time.
3. Establish minimum coverage for policies, tenant isolation, uploads, seeders, and destructive tournament workflows.
4. Publish separate `.env.production.example` or deployment validation that rejects `APP_DEBUG=true`, report-only CSP, insecure cookies, sync queue, and non-Redis cache in production.
5. Add periodic production restore drills, multi-worker load evidence, external alert delivery tests, and dependency audit evidence to the release checklist.

## Validation Record

| Check | Result |
|---|---|
| Composer locked dependency audit | Pass — no advisories |
| npm production dependency audit | Pass — no vulnerabilities |
| Tenant bypass allowlist | Pass |
| TypeScript (`tsc --noEmit`) | Pass |
| PHPUnit | Pass — 407/407 tests, 1,635 assertions |
| Pint | Pass |
| Inventory documentation check | Pass — 125 routes, 59 migrations, 39 controllers, 38 pages, 92 test files |
| Vite production build | Pass — executed from a temporary mapped drive for UNC compatibility |

## Release Decision

**SEC-01, DATA-01, and TEST-01 are remediated locally. Do not release until the production role/activity-log review is complete and the same validation suite passes in connected CI on a clean tree.** No evidence of SQL injection, stored XSS, committed secrets, or vulnerable locked dependencies was found in this audit.
