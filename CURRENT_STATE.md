# CURRENT STATE

> **Snapshot of the STMS project implementation status.**
> This document reflects the reality as of the latest full system review. It should be read together with `CLAUDE.md`, `AGENTS.md`, `TODOS.md`, and `ROADMAP.md`.

**Overall Status:** MVP complete and deployed for SAF 2026. All core features implemented: Organization/User/RBAC → Sport/Category/Session/Tournament/Event hierarchy → Participant/Registration → Event Registration with deadline → Squad Management → Match/Fixture/Result → Ranking Engine → Export/Reporting → Faculty Dashboard → Dean Verification → Draw/Group Allocation → Notifications → Role Management UI. Database seeded with complete SAF 2026 data (8 faculties, 24 sports, 30 events/categories, 19 users).
**Code Maturity:** Production-ready following CLAUDE/AGENTS patterns (trait scoping, Actions+Requests+Policies, thin controllers, factories, tests, defensive prod code).

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
| Domain Models  | 14 models (Organization, Session, Tournament, Sport, SportCategory, Event, Participant, Registration, Fixture, Result, EventParticipant, SquadMember, Pool, User) + RankingService | Full hierarchy | Implemented |
| API            | None (web/Inertia only)                      | RESTful `/api/v1` (future)                   | Future      |
| Tests          | 57+ test classes, 217 test methods           | Feature + Unit + Policy tests                | Good base   |

---

## What Actually Exists Today

- **Full domain**: 14 models + RankingService + Export infrastructure. HasUuids + SoftDeletes + full relations chain.
- **Multi-tenancy**: `BelongsToOrganization` trait + global scope on all tenant-aware models; per-org slug uniqueness.
- **RBAC**: Spatie with roles: super-admin, org-admin, staff, faculty-representative, dean. 30+ granular permissions. 12 Policies.
- **Actions + Form Requests**: Complete Create/Update/Delete Actions for every domain module.
- **Controllers**: 22+ controllers, thin (index uses paginate; store/update/delete = authorize + action.handle + redirect).
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
- **Routes**: 100+ routes (web + auth) — RESTful under auth + export endpoints + faculty/dean dashboards + health check + draw + notifications + roles.
- **Migrations**: 40+ migration files covering all tables + fixes + features.
- **Seeding**: `DatabaseSeeder` seeds the 24-sport SAF master list, then `SAF2026DataSeeder` seeds SAF 2026 categories/events with quota fields. Reusable and idempotent.
- **Tests**: 57+ test classes (217 test methods) — Feature, Auth, Policy, Form Request, Unit/Service.
- **Docker**: `Dockerfile` + `docker-compose.yml` + nginx/supervisor config.
- **CI/CD**: `.github/workflows/ci.yml` (Pint lint → PHPUnit → npm build).
- **Health check**: `GET /health` endpoint.

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

## How to Re-seed

```bash
php artisan migrate:fresh --seed
```

This will drop all tables, re-run migrations, and seed with:
1. Core bootstrap (org, roles, permissions)
2. SAF 2026 data (session, tournaments, sports, categories with quotas, events, faculties, users, registrations)

All user passwords: `password`

---

**Last Updated:** 22 July 2026 — SAF 2026 seed documentation synced to 24 sports and 30 categories/events with quota fields. All MVP features complete, including in-app notifications, role management UI, bulk import, participant dashboard, logo upload, and draw/fixture generation.
