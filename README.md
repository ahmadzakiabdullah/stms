# STMS - Sports Tournament Management System

STMS is a multi-tenant sports tournament platform for organizations ranging from schools and universities to national and international event operators. This repository currently hosts the SAF 2026 / UTeM web implementation.

## Current status

The web MVP is operational and in production-hardening/maintenance mode. Implemented flows cover:

- Organization, users, RBAC, settings, and activity logs
- Session, tournament, sport, category, and event setup
- Faculty/event registration, dean verification, squad quotas, bulk import, and printable team forms
- Draws, pools, fixtures, results, configurable rankings, exports, reports, and notifications
- Tenant-safe Bahasa Malaysia SAF 2026 public portal with schedules, results, progress, and medal tally
- Role-aware dashboards and sidebar navigation for super-admin, org-admin, admin-sport, staff, faculty representative, and dean
- CI quality gates, connected-CI Playwright/axe evidence, encrypted backup/restore tooling, and internal health checks

A sanitized production-sized MySQL restore has passed. Multi-worker authenticated performance, actual production/off-site recovery, and real external operator alert receipt remain open hardening evidence. REST APIs, accreditation, live scoring, mobile apps, advanced analytics, and AI remain deferred. Files under `docs/api/` describe future contracts and are not available endpoints.

## SAF 2026 data profile

The guarded SAF data seeder can provide one UTeM organization, one SAF 2026 session, two tournament phases, 24 sports, 30 category/events, eight faculties, representatives/deans, and event registrations. Demo accounts use a shared development password and must not be seeded unchanged in production.

## Technology

- PHP 8.4 and Laravel 13
- React 18, TypeScript, Inertia.js, Vite
- Tailwind CSS and shadcn/ui; Lucide icons
- React Hook Form and Zod where forms have been migrated
- MySQL 8 in production; SQLite for isolated tests
- UUID domain keys, soft deletes, Spatie RBAC
- Column-based multi-tenancy using `organization_id` and model scopes
- Database or Redis-backed cache/queues depending on environment

## Start here

1. [`CLAUDE.md`](./CLAUDE.md) — product and architecture rules
2. [`AGENTS.md`](./AGENTS.md) — contributor/agent rules
3. [`CURRENT_STATE.md`](./CURRENT_STATE.md) — honest implementation snapshot
4. [`TODOS.md`](./TODOS.md) — current operational focus
5. [`ROADMAP.md`](./ROADMAP.md) — completed and deferred phases
6. [`docs/design-system/navigation.md`](./docs/design-system/navigation.md) — role menu matrix

## Development principles

Keep tenant data isolated, prefer configuration over hardcoding, use policies and service/action patterns, reuse shadcn/ui components, and add proportionate automated tests for every change.

## License

MIT License
