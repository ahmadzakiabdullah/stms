# CURRENT STATE

> **Snapshot of the STMS project implementation status.**
> This document reflects the reality as of the latest full system review. It should be read together with `CLAUDE.md`, `AGENTS.md`, `TODOS.md`, and `ROADMAP.md`.

**Overall Status:** MVP functionality is implemented for SAF 2026. Core flows cover Organization/User/RBAC → Sport/Category/Session/Tournament/Event → Participant/Registration → Squad Management → Match/Result → Rankings → Exports/Reporting → Dean Verification → Draw/Groups → Notifications → Settings and Activity Logs.
**Code Maturity:** Operational MVP with production-hardening Sprints 1-3 implemented in the repository. CI covers lint, PHPUnit, dependency audits, PCOV coverage artifacts, build budgets, and Playwright desktop/mobile journeys with axe. Backend query and k6 thresholds are defined; encrypted backup/restore and internal health monitoring are implemented. Connected-CI browser/coverage results, production MySQL/off-site restore, authenticated load tests, and external alert drills still require environment evidence.

---

## Actual Tech Stack Today

| Layer          | Current Reality                              | Target (per CLAUDE.md)                       | Status      |
|----------------|----------------------------------------------|----------------------------------------------|-------------|
| Backend        | Laravel 13 + inertia-laravel                 | Laravel 13+                                  | Implemented |
| Frontend       | React 18 + Inertia.js (`.tsx` — TypeScript)  | React + Inertia.js + TypeScript              | Implemented |
| UI             | Full shadcn/ui + Lucide                      | shadcn/ui only                               | Implemented |
| Auth           | Laravel Breeze Inertia + Spatie RBAC         | Breeze Inertia + Spatie RBAC + org scoping   | Implemented |
| Database       | 30+ tables (domain + Spatie + Laravel). UUID PKs on all domain tables. Soft deletes on all models | UUID PKs, soft deletes, org_id on tenant tables | Implemented |
| Multi-tenancy  | Column-based + BelongsToOrganization Global Scope trait | Column-based (organization_id) + Global Scopes | Implemented |
| Authorization  | Spatie + 12 Policies + Gate in controllers   | Spatie Laravel Permission + Policies + Gates | Implemented |
| Domain Models  | 16 model files, including tenant/domain models and Setting | Full hierarchy | Implemented |
| API            | None (web/Inertia only)                      | RESTful `/api/v1` (future)                   | Future      |
| Tests          | 69 PHP test files; 266 tests and 810 assertions passing on 31 July 2026, plus 6 passing Playwright/axe journeys | PHP + browser + accessibility | Coverage measurement configured in CI; first percentage pending |

---

## What Actually Exists Today

- **Full domain**: 14 models + RankingService + Export infrastructure. HasUuids + SoftDeletes + full relations chain.
- **Multi-tenancy**: `BelongsToOrganization` trait + global scope on all tenant-aware models; per-org slug uniqueness.
- **RBAC**: Spatie with roles: super-admin, org-admin, staff, faculty-representative, dean. 30+ granular permissions. 12 Policies.
- **Actions + Form Requests**: Complete Create/Update/Delete Actions for every domain module.
- **Controllers**: 36 controller files including authentication controllers.
- **Frontend**: 30+ Inertia pages — all TypeScript. shadcn/ui components. Global Error page. Flow-based sidebar.
- **Exports**: PDF (Dompdf) + Excel (Maatwebsite) for Fixtures, Results, Rankings. Printable match sheet.
- **Reports**: Dashboard with stats, completion rate, recent results, quick export links.
- **Faculty Dashboard**: Squad member management with role-based and total-athlete quota validation. Bulk Excel/CSV import uses the same quota rules.
- **Dean Verification**: Event participant approval/rejection workflow + notifications.
- **In-App Notifications**: Bell dropdown in header, full notification page, dean approval notifications.
- **Role Management UI**: Full CRUD at `/roles` with grouped permission checkboxes.
- **Draw/Group Allocation**: Random balloting with round-robin fixture generation via Circle Method.
- **Participant Dashboard**: Stats cards, per-faculty breakdown, per-event breakdown with filters.
- **Logo Upload**: Faculty crest/logo upload and display.
- **Dashboard**: Real data with safe guards, Cache, try/catch (prevents 500s on partial prod DBs).
- **Routes**: 104 application routes (web + auth); no REST API routes.
- **Migrations**: 51 migration files covering domain, framework, fixes, and later features.
- **Seeding**: `DatabaseSeeder` seeds the 24-sport SAF master list, then `SAF2026DataSeeder` seeds SAF 2026 categories/events with quota fields. Reusable and idempotent.
- **Tests**: 69 PHP test files; `php artisan test` passes 266 tests / 810 assertions, plus 6 Playwright desktop/mobile and axe journeys passing locally and committed for connected CI.
- **Docker**: `Dockerfile` + `docker-compose.yml` + nginx/supervisor config.
- **CI/CD**: `.github/workflows/ci.yml` (Pint lint → PHPUnit → npm build).
- **Assurance**: PCOV/Clover reporting, Playwright critical journeys, axe WCAG checks, dashboard query budget, k6 thresholds, and frontend bundle budgets.
- **Known frontend debt**: Vite production build is clean, but `tsc --noEmit` still reports legacy `.jsx` declaration gaps, missing Ziggy global typing, and several model/form type mismatches.
- **Operations**: encrypted backup/restore commands, daily retention schedule, supervised Redis queue worker and scheduler.
- **Health check**: `GET /health` and `stms:health-check` cover database, cache, queue, and disk without exposing exception details.

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
- **Internationalization**: Laravel localization config exists but translations not implemented.
- **Accreditation, Live Scoring, Mobile App**: All deferred future features.
- **UNC path limitation**: `\\10.1.2.22\e\others\saf\portal` cannot run CLI directly — use local drive or `u:;` mapping.

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

**Last Updated:** 31 July 2026 — verified against routes, migrations, source, seeders, and the full automated test suite. Production-hardening gaps are tracked in the dated enterprise audit.
