# CURRENT STATE

> **Snapshot of the STMS project implementation status.**
> This document reflects the reality as of the latest full system review. It should be read together with `CLAUDE.md`, `AGENTS.md`, `TODOS.md`, and `ROADMAP.md`.

**Overall Status:** The SAF 2026 web MVP is operational and in maintenance/production-hardening mode. Core flows cover Organization/User/RBAC → Sport/Category/Session/Tournament/Event → Participant/Registration → Squad Management and printable team forms → Dean Verification → Match/Result → Rankings → Exports/Reporting → Draw/Groups → Notifications → Settings and Activity Logs.
**Code Maturity:** Operational MVP with production-hardening Sprints 1-3 implemented in the repository. CI covers lint, TypeScript, PHPUnit, dependency audits, PCOV coverage artifacts, build budgets, and Playwright desktop/mobile journeys with axe. Backend query and k6 thresholds are defined; encrypted backup/restore and internal health monitoring are implemented. Connected-CI Playwright/axe is now evidenced on commit `ae42a50`. An isolated encrypted MySQL restore drill also passed with a sanitized production-sized dataset. The corrected authenticated load path passed all functional checks but missed its latency target on a single-process development server; multi-worker staging performance, real external alert receipt, and actual production-backup/off-site recovery remain open.

**Unreleased hardening work (10 August 2026):** The working tree contains scoped tenant lifecycle handling, tenant-aware queue middleware, explicit public organization/session selection, tenant-safe `User` route binding, report-only CSP hardening (enforcing-ready via `CSP_REPORT_ONLY=false`), a CI tenant-bypass allowlist, corrected per-VU k6 authentication, versioned draw rollback, consistent SVG sanitization, Redis-backed production sessions, consistent `verified` email middleware across all authenticated routes, and a release runbook. A local-disk verification copy passes 394 PHPUnit tests / 1,555 assertions, Pint, TypeScript, production build, bundle budget and both dependency audits. These changes are not yet a connected-CI, tagged or deployed release.

---

## Actual Tech Stack Today

| Layer          | Current Reality                              | Target (per CLAUDE.md)                       | Status      |
|----------------|----------------------------------------------|----------------------------------------------|-------------|
| Backend        | Laravel 13 + inertia-laravel                 | Laravel 13+                                  | Implemented |
| Frontend       | React 18 + Inertia.js (`.tsx` — TypeScript)  | React + Inertia.js + TypeScript              | Implemented |
| UI             | Full shadcn/ui + Lucide                      | shadcn/ui only                               | Implemented |
| Auth           | Laravel Breeze username/email login + Spatie RBAC         | Breeze Inertia + Spatie RBAC + org scoping   | Implemented |
| Database       | 30+ tables (domain + Spatie + Laravel). UUID PKs on all domain tables. Soft deletes on all models | UUID PKs, soft deletes, org_id on tenant tables | Implemented |
| Multi-tenancy  | Column-based + BelongsToOrganization Global Scope trait | Column-based (organization_id) + Global Scopes | Implemented |
| Authorization  | Spatie + 12 Policies + Gate in controllers   | Spatie Laravel Permission + Policies + Gates | Implemented |
| Domain Models  | 16 model files, including tenant/domain models and Setting | Full hierarchy | Implemented |
| API            | None (web/Inertia only)                      | RESTful `/api/v1` (future)                   | Future      |
| Tests          | 87 PHP test files; 394 tests and 1,555 assertions in the current local suite, plus 6 Playwright/axe journeys | PHP + browser + accessibility | PHPUnit, Pint, type-check, dependency audits and production build pass from the local verification copy; connected-CI Playwright/axe passed on `ae42a50` |

---

## What Actually Exists Today

- **Full domain**: 14 models + RankingService + Export infrastructure. HasUuids + SoftDeletes + full relations chain.
- **Multi-tenancy**: `BelongsToOrganization` trait + global scope on all tenant-aware models; per-org slug uniqueness.
- **RBAC**: Spatie with roles: super-admin, org-admin, staff, faculty-representative, dean. 30+ granular permissions. 12 Policies.
- **Actions + Form Requests**: Complete Create/Update/Delete Actions for every domain module.
- **Controllers**: 38 controller files including authentication controllers.
- **Frontend**: 37 TypeScript Inertia pages, shadcn/ui components, a global Error page, role-aware dashboards, and an explicit policy-aligned sidebar role matrix.
- **Exports**: PDF (Dompdf) + Excel (Maatwebsite) for Fixtures, Results, Rankings. Printable match sheet.
- **Reports**: Dashboard with stats, completion rate, recent results, quick export links.
- **Faculty Dashboard**: Squad member management with role-based and total-athlete quota validation. Bulk Excel/CSV import uses the same quota rules. Each event registration links to a tenant-safe A4 Team Registration Form populated from the current roster.
- **Dean Verification**: Event participant approval/rejection workflow + notifications.
- **In-App Notifications**: Bell dropdown, personal inbox, super-admin Action Required queue, read/type/organization filters, severity badges, and dean/admin registration notifications. System Activity remains a separate tenant-safe audit view.
- **Role Management UI**: Full CRUD at `/roles` with grouped permission checkboxes.
- **Draw/Group Allocation**: Random balloting with round-robin fixture generation via Circle Method.
- **Role-aware dashboard**: administrators receive an attention-first operational overview; admin-sport receives competition actions; faculty representatives receive registration/squad management; deans are routed to verification.
- **Logo Upload**: Faculty crest/logo upload and display.
- **Dashboard**: Real data with safe guards, Cache, try/catch (prevents 500s on partial prod DBs).
- **Public portal**: Bahasa Malaysia SAF 2026 hub at `/` and `/index.php` with schedules, results, progress, sports, and medal standings; only participant display names/logos are exposed.
- **Routes**: 121 application routes (web + auth); no REST API routes.
- **Migrations**: 59 migration files covering domain, framework, fixes, and later features.
- **Seeding**: `DatabaseSeeder` seeds the 24-sport SAF master list, then `SAF2026DataSeeder` seeds SAF 2026 categories/events with quota fields. Reusable and idempotent.
- **Tests**: 87 PHP test files; the current local suite covers 394 tests / 1,555 assertions. Six Playwright desktop/mobile journeys, including axe checks, passed in connected CI on commit `ae42a50`.
- **Docker**: `Dockerfile` + `docker-compose.yml` + nginx/supervisor config.
- **CI/CD**: `.github/workflows/ci.yml` (Pint lint → PHPUnit → npm build).
- **Assurance**: PCOV/Clover reporting, Playwright critical journeys, axe WCAG checks, dashboard query budget, k6 thresholds, and frontend bundle budgets.
- **Frontend quality**: Vite production build, bundle budget, and `npm run typecheck` are clean. Legacy JSX components are covered by explicit compatibility declarations while new and migrated application pages remain TypeScript.
- **Operations**: encrypted backup/restore commands, daily retention schedule, supervised Redis queue worker and scheduler.
- **Health check**: `GET /health` and `stms:health-check` cover database, cache, queue, and disk without exposing exception details.
- **Operational evidence (5 August 2026)**: connected-CI browser/axe passed; an AES-256 MySQL restore of a sanitized 3.47 MB dataset completed in 2.977 seconds with matching counts and healthy checks; corrected authenticated k6 produced 190/190 successful checks but p95 4.24 seconds exceeded the 750 ms target on the single-process development server; local critical-webhook transport passed while real operator delivery remains unverified. See `docs/testing/2026-08-05-operational-drill.md`.

---

## Current SAF 2026 Data

| Item | Count |
|------|-------|
| Organization | 1 (UTeM) |
| Session | 1 (SAF 2026 - 1-30 Sept) |
| Tournaments | 2 (Fasa 1: 11-13 Sept, Fasa 2: 25-27 Sept) |
| Sports | 24 |
| Categories | 30 |
| Events | 30 |
| Faculties | 8 (FTKEK, FTKE, FTKM, FTKIP, FTMK, FPTT, FAIX, STEP) |
| Users | 19 (1 super-admin + 8 reps + 8 deans + 2 test) |
| Event Participants | 240 (8 faculties × 30 events) |

---

## What Is Missing / Known Gaps

- **REST API**: Not yet built — deferred for future phase.
- **Internationalization**: Session-based locale module is implemented with English default and Bahasa Malaysia option. Authentication flows and shared navigation/layout labels are localized; full page-by-page translation coverage is still incremental.
- **Accreditation, Live Scoring, Mobile App**: All deferred future features.
- **UNC path limitation**: `\\10.1.2.22\e\others\saf\portal` cannot run CLI directly — use local drive or `u:;` mapping.
- **Authenticated load evidence**: `tests/performance/smoke.js` still needs its validated CSRF/session-cookie correction committed, followed by a multi-worker staging run.
- **External monitoring evidence**: the critical webhook transport is verified locally, but no real Slack/Papertrail destination or operator receipt has been demonstrated.
- **Disaster-recovery evidence**: the isolated sanitized MySQL restore mechanics passed; an actual production backup, off-host copy, and isolated recovery drill are still required.

---

## Seeding

```bash
php artisan migrate:fresh --seed # local/demo only when SEED_DEMO_DATA=true
```

This will drop all tables, re-run migrations, and seed with:
1. Core bootstrap (org, roles, permissions)
2. SAF 2026 data (session, tournaments, sports, categories with quotas, events, faculties, users, registrations)

Production defaults to `SEED_DEMO_DATA=false`; `DatabaseSeeder` then creates only the tenant/RBAC bootstrap and no users or SAF operational data. `SAF2026DataSeeder` is blocked in production unless `ALLOW_DEMO_SEEDING=true` is deliberately approved. Demo accounts still use `password` and must never be loaded unchanged into production.

---

**Last Updated:** 10 August 2026 — authenticated routes now enforce `verified` email middleware consistently. CSP enforcing mode is configuration-ready (`CSP_REPORT_ONLY=false`). Release runbook added at `docs/deployment/release-runbook.md`. Multi-worker authenticated performance, actual production/off-site recovery, real external alert receipt, and a clean tagged release remain tracked in `TODOS.md`.
