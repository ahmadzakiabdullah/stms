# Enterprise System Audit — 31 July 2026

## Executive Summary

STMS is a functional Laravel/Inertia MVP with broad tournament-management coverage and a healthy automated-test baseline. The source contains 104 routes, 16 model files, 36 controller files, 19 policies, 17 services, 35 actions, and 51 migrations. After Sprint 3, the PHP suite passes: **266 tests, 810 assertions**. The frontend production build and bundle budget also pass.

Production-hardening Sprints 1-3 closed the immediate registration, proxy, seeded-credential, Docker exposure, lint, backup tooling, internal health monitoring, worker/scheduler, dependency-audit, frontend warning, bundle-budget, and query-budget gaps. Playwright/axe passes all six local desktop/mobile journeys; PCOV and k6 automation are committed and their connected-environment results still need to be recorded. External alert delivery and a production-scale MySQL/off-site restore drill also remain open. Overall verified repository health after Sprint 3: **87/100**.

| Area | Score | Evidence summary |
|---|---:|---|
| Architecture | 8/10 | Service/Action/Policy separation is established; some controllers still contain workflow logic. |
| Security | 6/10 | Policies and tenant scopes exist; onboarding, proxy trust, and default credentials need hardening. |
| Performance | 7/10 | Pagination/eager loading/indexes are common; no production query profile or load test exists. |
| Maintainability | 8/10 | Clear Laravel conventions, 264 passing PHP tests, and clean Pint; strict TypeScript checking still fails on legacy typing debt. |
| Scalability | 7/10 | Tenant-column architecture and queues are available; no capacity/load evidence or HA design. |
| Documentation | 7/10 | Broad coverage, but several implementation claims were stale or speculative. |
| Testing | 8/10 | 264 PHP tests pass; PCOV and browser/axe suites are configured but await connected-CI evidence. |
| DevOps | 6/10 | CI, Docker, and health endpoint exist; images/actions are unpinned and operations are incomplete. |

## Current System State

- Laravel 13.8+, PHP requirement `^8.3`; audit runtime used PHP 8.4.16.
- React 18, Inertia 2, TypeScript, Vite 8, Tailwind/shadcn-oriented UI.
- Web/Inertia application only; no `/api/v1` implementation.
- Column-based tenancy via `organization_id` and `BelongsToOrganization` global scope.
- Spatie roles/permissions, database notifications, activity logging, PDF/Excel exports, draw/group allocation, settings, and health check.
- SAF seeders define 8 faculties, 24 sports, 30 categories/events, 16 faculty/dean users, one named administrator, and two development/test users.

## Documentation vs Implementation

Accurate documentation includes the core hierarchy, absence of a REST API, UUID-oriented domain schema, Inertia frontend, Docker/CI presence, and deferred accreditation/live-scoring scope.

Corrected during this audit:

- Production-readiness language and test/controller/migration counts in `CURRENT_STATE.md`.
- Seeded user count and shared-password warning in `ROADMAP.md`.
- Nonexistent `EventCategory` model claim in ADR-004.
- Audit-log docs that claimed no dedicated audit table/UI.
- Authentication docs that incorrectly claimed organization creation, Org Admin assignment, multi-org switching, and broad email-verification enforcement.
- Security docs that described nonexistent permission middleware and role names.
- Database schema title/count and missing pools/settings/notifications/activity tables.

The files under `docs/api/` are future contract designs, not available endpoints.

## Findings

### Remediated — Unsafe public tenant assignment

**Description:** Public registration assigns `DEFAULT_ORG_SLUG`; if missing or invalid, it falls back to `Organization::first()`.

**Impact:** A new account can be attached to an unintended tenant, weakening tenant isolation at onboarding.

**Evidence:** `routes/auth.php`; `app/Http/Controllers/Auth/RegisteredUserController.php::store()`.

**Resolution:** Public registration now defaults off in production, requires a valid configured tenant when enabled, and has regression tests for disabled, missing, and invalid configuration. Invitation-based onboarding remains recommended.

### Remediated — Default credentials and operational data in routine production seeding

**Description:** `DatabaseSeeder` and `SAF2026DataSeeder` create named admin, test, representative, and dean accounts using `password`; the deployment runbook instructs `php artisan db:seed --force`.

**Impact:** Predictable credentials can create immediate account compromise.

**Evidence:** `database/seeders/DatabaseSeeder.php`; `database/seeders/SAF2026DataSeeder.php`; `docs/architecture/deployment.md`.

**Resolution:** Routine production seeding now stops after organization/RBAC bootstrap and creates no accounts or SAF operational data. Direct SAF demo seeding is production-guarded. Secure initial-admin provisioning and MFA remain follow-up work.

### Remediated — All proxies were trusted

**Description:** The application configures `setTrustedProxies(['*'], ...)` globally.

**Impact:** If the origin is directly reachable, spoofed forwarded headers can affect scheme, host, client-IP decisions, URL generation, and rate-limit attribution.

**Evidence:** `app/Providers/AppServiceProvider.php::boot()`.

**Resolution:** Proxy trust now uses an explicit comma-separated `TRUSTED_PROXIES` allowlist and defaults to empty.

### Medium — Tenant scope depends on authentication context

**Description:** `BelongsToOrganization` scopes only when an authenticated non-super-admin exists; console/jobs have no automatic tenant constraint. Public bypass helpers are available.

**Impact:** Jobs, commands, or future unauthenticated paths can query all tenants unless explicitly scoped.

**Evidence:** `app/Models/Concerns/BelongsToOrganization.php`.

**Recommendation:** Introduce an explicit tenant-context service for HTTP, jobs, and commands; authorize bypasses; add queue/console isolation tests.

### Medium — Verified-email enforcement is inconsistent

**Description:** Only `/dashboard` uses `verified`; the main authenticated group does not.

**Impact:** Unverified accounts can reach other authenticated features if policies or roles permit them.

**Evidence:** `routes/web.php`.

**Recommendation:** Apply `verified` to all sensitive routes if verification is mandatory; otherwise document it as optional.

### Remediated — Docker defaults were unsuitable for production

**Description:** Compose publishes MySQL and Redis, embeds simple passwords, and has no app health check or secret mechanism. Dockerfile uses `composer:latest` and does not build frontend assets internally.

**Impact:** Larger attack surface, non-reproducible builds, and deployment drift.

**Evidence:** `docker-compose.yml`; `Dockerfile`.

**Resolution:** MySQL/Redis host ports were removed, secrets are required externally, Redis authentication and service health checks were added, frontend assets build in a Node stage, and Composer is version-pinned. Digest pinning remains optional follow-up hardening.

### Partially remediated — Dependency vulnerability status

**Description:** Composer and npm audits could not reach their registries in the restricted environment.

**Impact:** Known vulnerable dependencies may remain undetected.

**Evidence:** `composer audit --locked` and `npm audit --audit-level=moderate` failed with registry connection errors on 31 July 2026.

**Resolution:** CI now runs `composer audit --locked --abandoned=fail` and `npm audit --audit-level=high`. Local results remain unverified in the restricted environment; connected CI is the enforcement point.

### Remediated — CI lint failure

**Description:** Pint reports violations in four files.

**Impact:** The CI lint job will fail on the current worktree/branch.

**Evidence:** `DashboardController.php`, `MatchTest.php`, `NotificationTest.php`, and untracked `tmp_inventory.php` were reported by `php vendor/bin/pint --test`.

**Resolution:** The three source/test files were formatted and the ad hoc `tmp_inventory.php` script was removed. Repository-wide Pint now passes.

### Partially remediated — Production operations

**Description:** There is no tested backup/restore procedure, RPO/RTO, complete worker/scheduler lifecycle, alerting definition, or disaster-recovery drill.

**Impact:** Recovery time and data-loss exposure are unknown.

**Evidence:** `docs/architecture/deployment.md`, Docker/supervisor configuration, and monitoring documentation.

**Resolution:** Encrypted database/upload backup, retention, restore guards, an automated SQLite restore drill, RPO/RTO targets, DB/cache/queue/disk health, and worker/scheduler lifecycle are implemented. External alert delivery and a production MySQL/off-site restore drill remain deployment work.

### Remediated — CSS minifier warnings

**Description:** Vite completes, but Lightning CSS reports unknown Tailwind directives including `@theme`, `@utility`, and `@custom-variant`.

**Impact:** The bundle is emitted, but future tooling changes could mishandle those constructs.

**Evidence:** `npm run build` output; `resources/css/app.css`.

**Resolution:** Tailwind 4-only stylesheet imports and unused packages were removed, equivalent Tailwind 3 animations were defined locally, and the production build is warning-free. Playwright retains screenshots/video/traces on failures; dedicated golden-image comparisons remain optional follow-up.

### Low — Documentation and repository hygiene drift

**Description:** Stale counts, speculative architecture statements, duplicate testing docs, and an untracked `.env.bak` were present.

**Impact:** Misleading onboarding and accidental secret exposure.

**Evidence:** Root/docs Markdown files and `git status`.

**Recommendation:** Keep one canonical testing guide, archive obsolete plans, generate inventories in CI, and never commit environment backups. `.gitignore` was hardened in this audit.

## Security Review Summary

- Broken access control/IDOR: policies and tenant scopes are a good base; onboarding and non-HTTP tenant context remain risks.
- Injection: no dangerous raw SQL or command-execution patterns were found in application code during static pattern review.
- XSS: React escapes by default; SVG uploads use `ParticipantLogoService` and `enshrined/svg-sanitize`. Continue adversarial upload tests.
- CSRF: Laravel web middleware protects state-changing web routes.
- SSRF/RCE: no application fetch or shell-execution surface was found in reviewed source.
- Authentication/session: login throttling and session regeneration exist; broad verified-email enforcement and MFA are absent.
- Security headers: HTTPS is forced in production, but CSP/HSTS/frame/referrer headers were not verified.
- Secrets: `.env` is ignored; `.env.bak` was untracked and is now ignored. Seeder and Compose defaults still require remediation.

## Performance and Database Review

Eager loading, pagination, UUID foreign keys, composite uniqueness, tenant/status indexes, and transaction-oriented services are present. No repeatable production query profile, `EXPLAIN` evidence, cache-hit metrics, dataset-scale benchmark, or load test exists. Measure before adding new repository or caching abstractions. Review indexes against real slow-query logs, especially participant/event dashboards, rankings, and exports.

## API and UI/UX Review

There is no REST API; `docs/api/` is future design intent. Web flows include many loading/error/empty-state patterns. Automated axe checks now cover login and dashboard at desktop/mobile sizes; keyboard, focus, dialog, and screen-reader manual smoke tests remain. The generated main JS chunk is approximately 349 kB (114 kB gzip) and is protected by a CI bundle budget.

## Testing Review and Missing Tests

The PHP suite contains 266 tests after Sprint 3; the final validation record below is the authoritative executed count. Missing or externally unverified:

- Invitation/tenant onboarding and invalid tenant configuration.
- Trusted-proxy spoofing and security-header assertions.
- Queue/command tenant isolation.
- Seeder production guards and credential lifecycle.
- Backup/restore and migration rollback drills.
- Browser E2E for dean, faculty, draw, import, export, and role workflows.
- Connected-CI execution of the locally passing Playwright desktop/mobile and axe suite.
- Authenticated load tests for dashboards, rankings, exports, and imports; a public health k6 baseline is committed.
- First PCOV coverage percentage; CI now publishes Clover evidence, but no arbitrary threshold is claimed.

## Documentation Change Summary

Updated: `.gitignore`, `CURRENT_STATE.md`, `ROADMAP.md`, ADR-004, authentication/security/audit-logging architecture docs, and database schema docs. New: this dated audit report. No files were renamed or deleted because historical documents may remain useful and the worktree contains unrelated user changes.

Recommended structure:

```text
docs/
├── adr/
├── architecture/
├── api/                 # future contracts, clearly labelled
├── audits/              # dated audit snapshots
├── database/
├── deployment/
├── design-system/
├── security/
├── testing/
└── archive/
```

## Prioritized Improvements

**Immediate:** fail-close or disable public registration, remove default production credentials, restrict trusted proxies, correct production seeding/runbook guidance, and fix lint.

**Short term:** connected dependency audits, explicit tenant context for jobs/commands, security headers, tested backup/restore, monitoring/alerts, coverage reporting, and browser/accessibility tests.

**Long term:** capacity/load testing, HA/DR tied to RPO/RTO, API implementation only when approved, and compliance-grade audit retention if required.

## Quick Wins

- Disable production registration with an environment-backed feature flag.
- Replace `Organization::first()` fallback with a validation failure.
- Move Docker passwords to an ignored environment file and stop publishing DB/Redis ports.
- Pin Composer image and JavaScript build-tool versions.
- Apply Pint to owned files and remove `tmp_inventory.php`.
- Add explicit “future/not implemented” banners to every `docs/api/*.md` file.
- Add Composer/npm audit jobs to a connected CI runner.

## Technical Debt and Refactoring Opportunities

- Split demo/SAF operational seeding from production bootstrap.
- Extract tenant resolution from authentication state into explicit HTTP/job/command context.
- Move workflow-heavy faculty squad/import and draw orchestration behind Form Requests and Actions while retaining policy checks.
- Consolidate duplicate testing documentation.
- Replace broad “production-ready” statements with dated evidence and operational acceptance criteria.

## Validation Record

| Check | Result |
|---|---|
| `php artisan route:list --except-vendor` | Passed; 104 routes |
| `php artisan test` | Passed; 266 tests, 810 assertions |
| `php vendor/bin/pint --test` | Passed repository-wide |
| `docker compose --env-file .env.docker.example config --quiet` | Passed; Docker config is structurally valid |
| `npm run build` | Passed without CSS directive warnings |
| `npm run build:budget` | Passed; JS ≤ 400 KB and CSS ≤ 100 KB per asset |
| `npx tsc --noEmit` | Failed; legacy JSX/Ziggy/page-model typing debt documented |
| `composer audit --locked --abandoned=fail` | Passed once with no advisories; registry access was intermittent on repeat |
| `npm audit --audit-level=high` | Passed once with zero vulnerabilities; registry access was intermittent on repeat |
| Playwright/axe local | Passed; 6 desktop/mobile Chromium journeys, including axe WCAG checks |

This repository audit does not prove that a deployed environment has the same revision, configuration, data, network controls, backups, or runtime health.
