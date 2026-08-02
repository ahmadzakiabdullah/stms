# Implementation Status

## Milestone 1: Foundation ✅
- [x] Laravel 13 + Breeze Inertia + shadcn/ui
- [x] Organization model + CRUD + multi-tenancy (BelongsToOrganization trait)
- [x] User CRUD + Spatie RBAC (roles: super-admin, org-admin, staff)
- [x] Role & Permission seeding
- [x] UUID primary keys on all models
- [x] Soft deletes on all domain models

## Milestone 2: Core Domain ✅
- [x] Sport + SportCategory (full CRUD, per-sport categories)
- [x] Session (event_sessions table)
- [x] Tournament (CRUD under session, sport many-to-many)
- [x] Event (CRUD under tournament, FK to sport + category)
- [x] Core hierarchy: Organization → Session → Tournament → Sport → Event

## Milestone 3: Participant & Registration ✅
- [x] Participant model (UUID, org scoping, soft deletes, full CRUD)
- [x] Registration model (UUID, org scoping, soft deletes, links Participant → Tournament)
- [x] Full CRUD UI + Service + Actions + FormRequests + Policy + Tests

## Milestone 4: Match Scheduling & Result Entry ✅
- [x] Fixture model (renamed from `Match` due to PHP 8 reserved keyword)
- [x] Result model (UUID, org scoping, soft deletes, links Match → scores + winner)
- [x] Full CRUD UI + Service + Actions + FormRequests + Policy + Tests

## Milestone 5: Basic Ranking Engine ✅
- [x] RankingService with three strategies (points, win_rate, medal_tally)
- [x] On-the-fly computation from match results
- [x] Frontend Rankings page with tournament selector + strategy switcher

## Milestone 6: Export, Reporting & Print ✅
- [x] PDF exports (Fixtures, Results, Rankings)
- [x] Excel exports (Fixtures, Results, Rankings)
- [x] Printable match sheet
- [x] Reporting dashboard

## Post-Audit Hardening ✅
- [x] Policy assertion fixes
- [x] Cross-org 404 behaviour (global scopes hide cross-org resources)
- [x] Double authorization removed from Match/Result Actions
- [x] Dashboard route migrated to controller
- [x] Logging added to all 12 service files
- [x] Documentation populated (architecture, database, design-system)
- [x] In-app notifications (database notifications, bell dropdown, full page, dean workflow notifications)
- [x] Test drift fixed for event participant pending status and sport-category slug generation

## Infrastructure (Completed)
- [x] **Docker Setup** — Dockerfile (PHP 8.4 FPM + Nginx), docker-compose.yml (app + MySQL 8 + Redis)
- [x] **CI/CD Pipeline** — GitHub Actions: Pint lint → PHPUnit → npm build
- [x] **Health Check Endpoint** — GET /health returns {status, database, cache, timestamp}
- [x] **Production env config** — .env.production.example with secure defaults
- [x] **Test config sanitized** — `phpunit.xml` uses non-secret local defaults; override DB credentials via environment variables
- [ ] **Sentry Error Tracking** — Dialih keluar 29 Jun 2026 (vendor missing on production)

## Future (Deferred)
- [ ] Accreditation System
- [ ] Live Scoring & Real-time Updates
- [ ] Mobile App (Flutter)
- [ ] REST API Layer (/api/v1)
- [ ] Advanced notification channels (email/realtime/webhooks)
