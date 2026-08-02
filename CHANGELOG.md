# Changelog

All notable changes to the STMS project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),  
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

- CI: aligned all runners and the container runtime with the PHP 8.4 dependency baseline, enabled CI on the default `master` branch, and retained Laravel bootstrap/runtime directories in clean checkouts so Composer, tests, coverage, and browser E2E can start reliably.
- Authentication: defaulted the session cookie path to `/` so root deployments retain login sessions; subfolder deployments continue to override `SESSION_PATH` explicitly.
- Notifications: added a super-admin `Action Required` default view, personal inbox tab, read/type/organization filters, organization and severity badges, and a direct link to filtered system activity. New pending registrations now notify same-organization super/org admins plus the participant dean without cross-tenant delivery.
- Activity logs: super-admins can audit all organizations with organization/event/date filters, while org-admins remain restricted to their own tenant.
- Fixed a pagination runtime regression when an Inertia fallback supplies a partial paginator object without a `links` array.
- Quality gates: restored repository-wide Pint compliance and added `npm run typecheck` to connected CI.
- Frontend typing: added Ziggy/Inertia shared declarations, legacy JSX compatibility declarations, corrected Inertia v2 reload/link options, normalized pagination fallbacks, and aligned nested page payload types.
- Accessibility: added accessible names to the dashboard sport, faculty, and status filters so desktop/mobile axe checks pass.
- Documentation: refreshed current test/migration counts, squad `matrix_no` schema, multi-tenant creation behavior, and the 2 August 2026 verification status.

- Notifications: participants (faculty reps/deans of the home and away faculties) are now notified when a match result involving them is recorded, updated, or removed, via the new `MatchResultNotification`.
- Layout: sidebar is now pinned to the viewport (`h-screen sticky top-0`) so the organization chip and user card stay visible at the bottom while only the navigation scrolls; on long pages the footer no longer slides below the fold.
- Dashboard: added compact "Squad Composition" stat cards (total members, male/female athletes, officials) to the Registration Overview section, respecting the sport/faculty/status filters. Added `SquadMemberFactory`.
- Merged the Participant Dashboard into the main Dashboard (`/dashboard`): new "Registration Overview" section with stat cards, sport/faculty/status filters, per-faculty and per-event breakdown tables. Removed `/participant-dashboard` route, `ParticipantDashboardController`, its page, and the sidebar item.
- Event Registrations page: added clickable status stat cards (All/Pending/Confirmed/Rejected), a status filter, and Approve/Reject actions for super-admins/org-admins (notifies the faculty representative, matching the dean flow). Added `PATCH /event-participants/{eventParticipant}/status`.
- Fixed Event Registrations filtering: sport/category/search filters now narrow the registration rows and the All Events tab (previously only the participant list was filtered, leaving unrelated registrations visible).
- Added squad member listings for super-admins on the Event Registrations page: expandable rows show every athlete and official (with role badges and matrix numbers) per participant per event.
- Refactored the super-admin experience: redesigned the dashboard into an operations command center with prioritized KPI cards (sessions, events, registrations, matches), a registration approval pipeline (pending/confirmed/rejected) with progress bar, registrations-by-sport bar chart, and a quick actions panel.
- Restructured the sidebar navigation by workflow (Overview, Competition Setup, Registration, Competition, Administration, Reports), fixed the duplicate Roles/Settings icon, added an active-item accent indicator, and added an organization context chip for super-admins.
- Added squad member `matrix_no` field (required on the faculty dashboard) and updated the CSV import template headers to `name, role, matrix_no, ic_passport, phone`.
- Redesigned the faculty dashboard registration cards with color-coded quota progress bars (male/female/officials), quota-complete checkmarks, and a green "Squad complete" badge once athlete and official quotas are filled.
- Reconciled a drifted MySQL schema without resetting data, including user soft deletes, settings, activity logging, UUID primary keys, and all remaining domain migrations.
- Made the user UUID backfill portable across MySQL and SQLite and made the legacy participant-column cleanup tolerate databases without the historical indexes.
- Created and verified an encrypted pre-migration backup before schema reconciliation.
- Fixed RBAC bootstrap seeding from an empty database by clearing Spatie's permission cache before role assignment, then restored and verified the complete SAF 2026 dataset without overwriting the administrator's changed password.
- Updated the public welcome header and favicon markup to use organization branding uploaded through Settings, with the built-in trophy/favicon retained only as fallbacks.

### Documentation
- Added a dated enterprise audit with evidence-backed architecture, security, performance, database, testing, DevOps, and documentation findings.
- Corrected implementation drift in current-state, roadmap, authentication, security, audit-logging, database-schema, and sports-engine documentation.
- Added production-hardening work to the current maintenance focus and ignored environment backup files to reduce accidental secret exposure.

### Operations
- Added encrypted database/public-upload backups with checksums, retention, guarded restore, daily scheduling, and an automated SQLite restore drill.
- Expanded health monitoring to database, cache read/write, queue backlog/failed jobs, disk space, component latency, and non-sensitive degraded responses.
- Added supervised Laravel Scheduler lifecycle, daily log rotation defaults, backup/restore runbook, RPO/RTO targets, and safer release rollback guidance.
- Added Composer and npm vulnerability audit gates to CI and restored a clean repository-wide Pint result.

### Assurance and Performance
- Added PCOV/Clover coverage reporting, Playwright desktop/mobile critical journeys, axe WCAG checks, and failure artifacts to CI.
- Fixed dean verification authorization to require the seeded `dean` role, and added policy regression tests.
- Fixed guest-logo accessible naming and dashboard color contrast findings discovered by the Playwright/axe suite.
- Added a dashboard database-query budget and a k6 smoke/load scenario with p95 latency and error-rate thresholds.
- Added per-asset JavaScript/CSS bundle budgets to the production build.
- Removed the Tailwind 3/4 stylesheet mismatch and replaced v4-only dialog/sheet utilities with native Tailwind 3 state animations; production builds no longer emit Lightning CSS warnings.
- Verified Composer and npm dependency audits locally with zero known vulnerabilities.

### Fixed
- Hardened tenant onboarding: public registration now defaults off in production and fails closed when `DEFAULT_ORG_SLUG` is missing or invalid.
- Replaced wildcard trusted-proxy configuration with explicit `TRUSTED_PROXIES` IP/CIDR configuration.
- Prevented routine production seeding from creating predictable demo/SAF accounts; direct SAF demo seeding now requires explicit production approval.
- Added `stms:create-super-admin`, which securely prompts for the initial administrator password instead of accepting or seeding a shared credential.
- Hardened Docker deployment by requiring injected secrets, removing host exposure for MySQL/Redis, adding health checks, building frontend assets in-image, and replacing the unavailable Horizon command with the standard Laravel queue worker.
- Restored full match CRUD controls on the Matches page, added pool/round management and per-event match-number validation, and refactored matchups to Name–Logo–VS–Logo–Name without circular logo frames.
- Replaced UUID-based Matches event filter URLs with readable event slugs while retaining legacy `event_id` compatibility.
- Fixed participant logo uploads, added sanitized SVG support, explicit format guidance, and visible upload validation errors.
- Restored the IIS/Laravel front controller and fixed production startup failures caused by cached UNC paths; `/portal/`, `/health`, and `/login` now respond normally.
- Added visible duplicate-entry validation feedback on the Sports page and reject duplicate sport names within the same organization.
- Synced tests with the current event participant workflow (`pending` on new event registration) and sport-category slug generation.
- Sanitized `phpunit.xml` so database credentials are no longer stored in the repository; use environment variables or CI secrets for local/remote test databases.
- Standardized frontend component imports and directory casing to `resources/js/components`.
- Updated documentation drift around notifications, test database configuration, current module counts, and test status.

### Added
- **Flexible athlete quota modes**: Sport categories now support gender-based, open-total, and mixed-total athlete quotas with max total athletes and optional minimum male/female requirements. Faculty squad add/import flows share the same `SquadQuotaService` validation, enabling cases like E-Sport (Mobile Legends) Open with 6 total players.
- **SAF 2026 Complete Data Seeding**: Created `SAF2026DataSeeder.php` with all SAF 2026 data — Organization UTeM, Session (1-30 Sept), 2 Tournaments (Fasa 1: 11-13 Sept, Fasa 2: 25-27 Sept), 24 sports in English, 30 events/categories with quota fields, 8 faculties (FTKEK, FTKE, FTKM, FTKIP, FTMK, FPTT, FAIX, STEP), 16 users (faculty reps + deans). Dean role created with permissions. Integrated into `DatabaseSeeder`.
- **Navigation Restructured**: Flow-based sidebar with sections: Main → Setup → Administration → Registration → Competition → Reports. English labels throughout. Role-based visibility.
- **Event Registration Cut-off Date**: `registration_deadline` field on events with validation (`after:now`). UI shows deadline and disables register button after expiry.
- **Role/Permission Management UI**: Full CRUD for roles with grouped permission checkboxes. Protects super-admin role. `/roles` routes in Administration section.
- **In-App Notifications**: Notifications table migration. Three notification classes (`EventParticipantConfirmed`, `EventParticipantRejected`, `NewEventRegistration`). Bell dropdown in header with unread count. `/notifications` full page.
- **Bulk Squad Import**: Excel/CSV squad member import with heading row detection and quota validation. Template download. Import dialog in Faculty Dashboard.
- **Participant Dashboard**: Stats cards (total faculties, events, registrations), per-faculty breakdown table, per-event breakdown with sport/faculty/status filters.
- **Logo/Crest Upload**: `logo_path` column on `participants` table. Upload in create/edit forms. Display in table and view dialogs.
- **Draw/Group Allocation + Auto Fixture Generation**: `Pool` model, `DrawService`, `DrawController`. Draw button (super-admin) on Events page with confirmation dialog. Round-robin via Circle Method. Format & pool size on events.
- **Dean Role**: New `dean` role with event participant management permissions. Each faculty dean user created with dean@*.utem.edu.my.

### Changed
- **SAF 2026 category seed**: Sport categories now seed from the current 30-category SAF set, including detailed athlete/official quotas and open-total E-Sport categories.
- **SAF 2026 sport seed**: The default database seed now includes the full 24-sport SAF master list before SAF categories/events are seeded.
- **SAF 2026 documentation**: Markdown docs now reflect the current seed counts and re-seed behavior for 24 sports and 30 categories/events.
- **Database reset**: Full `migrate:fresh` — all previous test data replaced with SAF 2026 data.
- **Organization**: Changed from "Default Organization" to "Universiti Teknikal Malaysia Melaka (UTeM)".
- **Seeder idempotency**: User creation in `DatabaseSeeder` now uses `withTrashed()->firstOrCreate()` to allow re-seeding without unique constraint violations.
- **Fix events slug migration**: Moved try/catch outside Blueprint closure to properly handle non-existent indexes.
- **Fix unique constraint migration**: Added try/catch for duplicate key handling.
- **Column existence checks**: Added `Schema::hasColumn()` guards to `registration_deadline` and `logo_path` migrations.
- **EventParticipant model**: Added `BelongsToOrganization` trait + `organization_id` fillable + new migration `2026_07_03_000001_add_organization_id_to_event_participants`.

### Added
- **Artisan command**: `event-participants:backfill-org` — backfill null `organization_id` on existing EventParticipant records from related Event. Supports `--dry-run`.

### Fixed
- Restored full match CRUD controls on the Matches page, added pool/round management and per-event match-number validation, and refactored matchups to Name–Logo–VS–Logo–Name without circular logo frames.
- **Soft-delete slug collision**: Added `->whereNull('deleted_at')` to `Rule::unique` in all Store FormRequests (Tournament, Event, Session, Sport, Participant, SportCategory). Prevents "slug has already been taken" when a soft-deleted record uses the same slug.

### Fixed (Known Issues)
- **EventParticipant model**: Added `SoftDeletes` trait to match migration's `softDeletes()` column.
- **SquadMember model**: Added `BelongsToOrganization` trait for multi-tenant scoping (already had `organization_id`).
- **ParticipantFactory**: Removed dropped columns (`identification_number`, `gender`, `date_of_birth`) that caused test warnings.
- **Duplicate Factory**: Deleted `MatchFactory.php` (identical to `FixtureFactory.php` — both target `Fixture` model).

### Added / Improved (Fasa 3: DevOps & Production)
- **Docker setup**: `Dockerfile` (PHP 8.3 FPM + Nginx), `docker-compose.yml` (app + MySQL 8 + Redis), nginx config, supervisor config, `.dockerignore`.
- **CI/CD pipeline**: `.github/workflows/ci.yml` with Pint lint, PHPUnit test (MySQL service), npm build + artifact upload.
- **Sentry error tracking**: Installed `sentry/sentry-laravel`, published config, added `SENTRY_DSN` to `.env.example`, conditional init in frontend `app.tsx`.
- **Health check endpoint**: `GET /health` (no auth) returns `{"status","database","cache","timestamp"}` with `200` or `503`.
- **Production env config**: `.env.production.example` with secure defaults.
- **Tests**: 215 → 217 (+2 HealthCheckTest), 582 → 589 assertions, 0 failures.

### Added / Improved (Fasa 2: UX & Testing)
- **Dead frontend code removed**: Deleted `NavLink.jsx`, `ResponsiveNavLink.jsx`, `Dropdown.jsx`, `DataTable.tsx`, `useInertiaForm.ts`.
- **Action method naming standardized**: `execute()` → `handle()` in all 6 Match/Result Action files + controllers.
- **Profile pages restyled**: `bg-white` → `Card` component with `bg-card`/`text-foreground`/`text-muted-foreground`.
- **EventParticipantPolicy created**: Full policy with org-scoping, permissions, registration in `AppServiceProvider`, and 4 tests.
- **Form request validation tests**: 8 test files covering all Store*Request validation rules.
- **Edge case tests added**: Duplicate slug, tournament sports sync, ranking calculation, exports, TournamentService update/delete.
- **TournamentService enhanced**: Added `updateWithSports()` and `deleteWithSports()` methods.
- **Tests**: 160 → 215 (+55 tests), 481 → 582 assertions, 0 failures.

### Added / Improved (Fasa 1: Code Quality & Documentation)
- **Dashboard route → controller**: Created `DashboardController` with all dashboard logic moved out of `routes/web.php` closure.
- **Double authorization removed**: `Gate::authorize()` removed from all 6 Match/Result Action classes (controllers already authorize before calling actions).
- **Service-layer logging**: Added `Log::info()` for successful CRUD operations and `Log::error()` for exceptions across all 12 service files (User, Tournament, Sport, SportCategory, Session, Result, Registration, Ranking, Participant, Organization, Match, Event).
- **Architecture documentation populated**: All 21 architecture doc files, 4 database doc files, and 8 design-system doc files now have substantive content.
- **IMPLEMENTATION_STATUS.md**: Created with full milestone tracking.
- **README.md**: Updated tech stack from "planned" to "implemented" for Spatie, UUID, multi-tenancy.
- **CURRENT_STATE.md**: Updated to reflect latest post-audit state.

### Added / Improved (Frontend Modernization)
- **TypeScript migration**: All page components converted from `.jsx` to `.tsx`. Created `resources/js/types/index.ts` with full type definitions for all domain models (Organization, User, Sport, Session, Tournament, Event, Participant, Registration, Fixture, Result, RankingEntry, Paginated, Flash, Auth, PageProps).
- **React Hook Form + Zod**: All form pages migrated from Inertia `useForm` to `react-hook-form` + `zod` + `@hookform/resolvers`. Zod schemas for every form with proper validation rules.
- **TanStack Table**: Created reusable `DataTable.tsx` component using `@tanstack/react-table` with sorting support, pagination integration, and generic typing.
- **Global error page**: `Error.tsx` component with status-specific messages (403, 404, 419, 429, 500).
- **Entry point updated**: `app.jsx` → `app.tsx`, vite.config updated to resolve `.tsx` files.
- **Asset versioning**: Inertia middleware now supports `app.asset_version` config for cache busting.
- **Profile pages**: Converted to TypeScript with proper form validation.
- **New packages**: `typescript`, `@types/react`, `@types/react-dom`, `react-hook-form`, `@hookform/resolvers`, `@tanstack/react-table`, `zod`.

### Added / Improved (M6: Export, Reporting & Print)
- **PDF exports**: `FixtureExport`, `ResultExport`, `RankingExport` using `barryvdh/laravel-dompdf`. Generates formatted PDFs with tables, headers, and footer.
- **Excel exports**: `FixtureExport`, `ResultExport`, `RankingExport` using `maatwebsite/excel`. Generates `.xlsx` files with auto-sized columns and styled headers.
- **Printable match sheet**: `match-sheet.blade.php` — single fixture view with score boxes, official lines, team representative signatures.
- **ExportController**: 7 endpoints — fixtures PDF/Excel, results PDF/Excel, rankings PDF/Excel, match sheet PDF.
- **ReportingController**: Dashboard with stats (total/completed/pending/in-progress fixtures, results, participants, registrations, tournaments), completion rate bar, recent results table, quick export links.
- **Reports/Index.tsx**: Full reporting page with stat cards, completion rate chart, status breakdown, recent results table.
- **Export buttons**: Added to Matches, Results, and Rankings pages (PDF/Excel buttons in header).
- **Sidebar**: Added "Reports & Analytics" under "Reports" section.
- **New packages**: `barryvdh/laravel-dompdf`, `maatwebsite/excel`.

### Added / Improved (M5: Basic Ranking Engine)
- **M5 Ranking module (complete)**: RankingService with 3 strategies (Points, Win Rate, Medal Tally) computed on-the-fly from match results. No separate rankings table — computed dynamically.
- **M5 migration**: Added `ranking_strategy` column to tournaments table (default: 'points').
- **M5 RankingController**: index (view rankings per tournament), updateStrategy (change ranking strategy per tournament).
- **M5 Frontend**: Rankings/Index.jsx page with tournament selector, strategy switcher, ranked results table with W/D/L, points, goal difference, win rate.
- **M5 sidebar**: "Rankings" menu item added under Match Scheduling section.
- **M5 tests**: RankingServiceTest (3 tests), RankingTest (4 tests).

### Added / Improved (M4: Match Scheduling & Result Entry)
- **M4 Match module (complete)**: Model renamed to `Fixture` (PHP 8 reserved keyword `match`), table stays `matches`. UUID, org scoping, soft deletes, event FK, home/away participant FKs, venue, scheduled_at, status enum. Service, Actions (Store/Update/Delete), FormRequests (Store/Update), Policy, Controller, Inertia page, Factory, Routes, Seeder data.
- **M4 Result module (complete)**: Model + migration (UUID, org scoping, soft deletes, match FK unique, score_home/score_away, winner FK). Service, Actions, FormRequests, Policy, Controller, Inertia page (Index.jsx with match selector, score inputs, winner selector), Factory, Routes, Seeder data.
- **M4 sidebar**: New "Match Scheduling" section with Matches and Results menu items (Scale + Trophy icons).
- **M4 dashboard**: Stats expanded to include match and result counts.
- **M4 seeder**: 2 new permissions (`manage_matches`, `manage_results`) + sample matches with results seeded.
- **M4 tests**: MatchTest (5 tests), ResultTest (5 tests), MatchServiceTest (3 tests), ResultServiceTest (3 tests), MatchPolicyTest (4 tests), ResultPolicyTest (4 tests).
- **M4 AppServiceProvider**: Match and Result policies registered via Gate::policy.

### Added / Improved (M3: Participant & Registration + M1/M2 Gap Fixes)
- **M3 Participant module (complete)**: Model + migration (UUID, org scoping, soft deletes, slug per org), Service, Actions (Create/Update/Delete), FormRequests (Store/Update), Policy, Controller (thin, uses Actions), Inertia page (Index.jsx with full CRUD via modals, pagination), Factory, Routes, Seeder data.
- **M3 Registration module (complete)**: Model + migration (UUID, org scoping, soft deletes, unique tournament+participant), Service, Actions, FormRequests, Policy, Controller, Inertia page (Index.jsx with tournament/participant selectors, status tracking), Factory, Routes, Seeder data.
- **M1 gap fix**: Added `SoftDeletes` trait to User model + migration `2026_06_25_000001_add_soft_deletes_to_users_table.php` — users are now soft-deleted consistently with all other domain models.
- **M1 gap fix**: Added `tests/Feature/UserTest.php` — 5 tests covering create, validation, update, cross-org denial, and soft delete.
- **M2 gap fix**: Added `tests/Feature/SessionTest.php` — 5 tests covering scoping, create, update, cross-org denial, and soft delete.
- **M2 gap fix**: Added `tests/Feature/SportCategoryTest.php` — 5 tests covering scoping, create, update, cross-org denial, and soft delete.
- **M2 gap fix**: Added `tests/Feature/Policies/SportCategoryPolicyTest.php` — 4 tests covering super-admin, org-admin, cross-org, and staff roles.
- **M3 tests**: ParticipantTest (5 tests), RegistrationTest (5 tests), ParticipantServiceTest (3 tests), RegistrationServiceTest (3 tests), ParticipantPolicyTest (4 tests), RegistrationPolicyTest (4 tests).
- **Seeder expanded**: 8 new permissions for participants/registrations, 5 example participants, 6 example registrations seeded under default org.
- **Dashboard updated**: Now shows participant and registration stats.
- **Sidebar updated**: New "Registration" section with Participants and Registrations menu items.
- **Organization relationships**: Added `participants()` hasMany relation on Organization model (implicit via org scoping).
- **Build unblocked (critical)**: Sports/Index.jsx completely reverted from partial RHF+Zod+TanStack pilot to classic Inertia `useForm` (data/setData/post/put/processing/errors) + shadcn Table + .map (identical patterns to Tournaments/Index and Organizations/Index). Removed every reference to react-hook-form, zod, @tanstack/react-table, register, handleSubmit, useRhfForm, zodResolver, getCoreRowModel, useReactTable. Confirmed via full grep: zero occurrences left in any Page. `npm run build` will now pass without installing the 4 extra packages (per repeated "buat A" / Option A choices in this environment).
- **Production resilience (prevents recurring 500s)**: Added `safePaginatedQuery()` + `safeCollectionQuery()` helpers to the base `Controller`. Applied defensive wrapping (try/catch + empty fallback + error flash) to **all** main index methods: Dashboard (route), Organization, User, Sport, SportCategory, Session, Tournament, and Event controllers. Pages now gracefully show empty lists + a clear "run php artisan migrate" message instead of hard 500 when the prod DB is behind on migrations (the exact cause of the /events 500 you just reported). This is now the standard pattern for list pages.
- **Service Layer Expansion (completed)**: 
  - `SessionService` + `SportService` (earlier).
  - `SportCategoryService` + `OrganizationService`.
  - **UserService** (final major one): handles user creation/update (password hashing, org scoping, Spatie role sync).
  - All Create/Update/Delete Actions for the 7 core entities now delegate to services (thin actions).
  - Unit tests for every service (`*ServiceTest.php`).
  - This fully implements the preferred "Service Classes for business logic + Action Classes" pattern across the MVP. Controllers remain thin.
- Documentation drift fixes: CURRENT_STATE.md header/tech-stack/"What Exists" fully rewritten as honest current MVP snapshot (M1+M2 largely done, remaining risks noted). frontend.md and testing.md "Current State" notes updated to reflect classic stable frontend + good test base. IMPLEMENTATION_STATUS.md expanded with post-audit details and deferred items.
- Seeder made more generic: neutralized deployment-specific "SAF Portal / UTeM" narrative in comments while keeping the excellent hierarchical example data (SUKMA/SUKIPT) and explanations of Organization/Session/Tournament/Event distinctions. Everything remains env-overridable.
- Consistency polish: Active/Inactive status badges in Sports table (matching Tournaments style), submit guards and `disabled={processing}` on buttons consistent across forms and delete dialogs.
- Verification: All other list pages already clean on classic stack. Scoping trait, policies (7), actions (21), Form Requests (16), factories, tests (policy + feature + service), models (HasUuids+SoftDeletes+BelongsToOrganization), routes, and defensive dashboard code untouched and correct.
- Reminder: Focused exclusively on current MVP (M1/M2). No future phases. Package.json left without RHF/TanStack (user can add later for full modernization if desired). Docs (this CHANGELOG + TODOS) to be further synced.

### Previous Audit Implementation Pass (history)
- Automatic multi-tenant global scoping via `BelongsToOrganization` trait (Sport, SportCategory, Session, Tournament, Event, User). Removed duplicated manual scoping in controllers.
- Per-organization slug uniqueness (migration + updated validation rules) — fixes multi-tenant collision risk.
- Pagination backend on all major resources + reusable `Pagination` component + compatibility updates on list pages.
- Complete Event module (full CRUD with scoped selectors, policy, actions, routes, dedicated page, sidebar entry).
- 6 domain factories + improved UserFactory + `CreatesTenantUsers` test trait.
- Expanded permission seeding for all MVP modules.
- Enhanced shared Inertia data (roles, current org, super-admin context).
- Users table now has `uuid` column + `HasUuids` (additive step toward full UUID PK).
- Dashboard receives real scoped stats from backend + recent sessions/tournaments.
- Full policy tests: EventPolicyTest, TournamentPolicyTest, SportPolicyTest, SessionPolicyTest, OrganizationPolicyTest, UserPolicyTest (roles, permissions, cross-org, update/delete).
- Enhanced feature tests for create/update/delete auth in SportTest, EventTest, TournamentTest.
- New `SportTest` with scoping verification using factories/trait.
- `EventController` + related classes registered and wired.
- Branding more generic: dynamic names via app config, env-based seeder, generic subtitle in layout.
- Frontend modernization started: RHF+Zod structure in Sports form (packages needed); TanStack Table prep.
- Added IMPLEMENTATION_STATUS.md for current vs target.

### Changed
- Controllers for Sports/Sessions/Tournaments/etc. now use `paginate()` and rely on global scope.
- Form Request unique rules updated for org-scoped slugs.
- Sidebar now includes Events.
- User model route key uses uuid for consistency.

### Added
- Initial project structure and documentation setup.
- Created comprehensive Architecture Decision Records (ADR-001 to ADR-008).
- Established documentation structure (`docs/architecture`, `docs/database`, `docs/api`, `docs/design-system`).
- Defined core domain hierarchy and multi-tenant strategy.
- Laravel 13 + Breeze Inertia React foundation (package.json + resources/js).
- Initial shadcn/ui component library in `resources/js/Components/ui/` (button, card, table, dialog, badge, etc.).
- Custom `AuthenticatedLayout.jsx` with sidebar navigation and "Portal SAF" / UTeM branding.
- Dashboard page with shadcn components and Malay-language placeholder content.
- System review completed and documentation drifts addressed.
- Full Organization CRUD (list, create, edit via modal, delete with confirm) + sidebar menu.
- Full User CRUD (list, create, edit via modal with role checkboxes, delete) + role sync + sidebar menu.
- ahmadzaki@utem.edu.my (ID 1) promoted as default super-admin via updated seeder.
- M1 Foundation completed per TODOS (login, org management, role assignment).
- M2 Sport module completed: full working CRUD for Sports.
- M2 SportCategory completed: full working CRUD for Sport Categories (per-sport), with dedicated page, Sport selector in forms, proper scoping via organization, example data seeded. Added "Categories" to sidebar.
- M2 Session Management completed: Full CRUD for Sessions including name, slug, description, start/end dates, is_active. Follows same patterns (Actions, Form Requests, Policy, thin controller). Seeded sample sessions. Added "Sessions" menu to sidebar.
- M2 Tournament (Basic) completed: Model + migration for tournaments (under sessions with org scoping). Full CRUD page with session selector in forms, actions, requests, policy. Seeded sample tournaments. Added "Tournaments" to sidebar. Added `sessions()` and `tournaments()` (hasManyThrough) relations to Organization model; `withCount('sessions')` and `latestSession` accessor in OrganizationController and model for better UX. Frontend updated to display counts and latest.
- M2 Event model + core relationships (M2.3/M2.6): Created Event model/migration with proper FKs (org, tournament, sport, sport_category). Implemented full chain of relations: Tournament-Sport many-to-many (new pivot + models), Event belongsTo (Tournament/Sport/SportCategory) with hasMany reverses, hasManyThrough for events in Session/Organization. Added sports multi-select in Tournament form + sports list in table. Enhanced seeder with detailed comments explaining the hierarchy + rich example data to differentiate the concepts (e.g. one Organization like SAF Portal has multiple Sessions like SUKMA 2026 vs SUKMA 2024; one Session has multiple Tournaments like Men's Football; one Tournament has multiple Events like "Men's Football - Group A" linked to specific Sport + SportCategory; Sports attached via many-to-many).
  - Models: Sport (HasUuids + SoftDeletes + org_id scoping + categories/events relations + route key slug).
  - Migration: sports table (UUID, org FK, slug unique, icon, is_active, timestamps + softDeletes follow-up).
  - SportCategory model + migration ready (child of Sport).
  - Actions: CreateSport (with org auto-scope + slug duplicate handling), UpdateSport, DeleteSport.
  - Form Requests: StoreSportRequest + UpdateSportRequest (unique slug ignore on update).
  - Policy: SportPolicy (viewAny/view/create/update/delete with super-admin bypass + org_id match for non-super).
  - Controller: SportController (thin, Gate protected, Action delegated).
  - Frontend: Sports/Index.jsx full (table + actions column, create/edit dialog switching, delete confirm, processing guard, flash, using shadcn + lucide + Inertia router/useForm).
  - Routes: Clean /sports RESTful routes only (removed premature references to non-existent controllers to avoid fatal load errors).
  - Seeder: Sport permissions (view/create/edit/delete sports) granted to super-admin; 5 common default sports seeded under SAF default org (Bola Sepak, Badminton, etc.).
  - Sidebar: "Sukan" menu item already present with Trophy icon.
  - Soft deletes supported on Sport (additive migration).

### Changed
- Updated multiple architecture documents to reflect actual current state (Inertia React instead of Blade, JSX foundation instead of full TypeScript, Laravel 13).
- Clarified "Current Implementation" vs target architecture in authentication.md and frontend.md.

### Deprecated
- N/A

### Removed
- N/A

### Fixed
- Restored full match CRUD controls on the Matches page, added pool/round management and per-event match-number validation, and refactored matchups to Name–Logo–VS–Logo–Name without circular logo frames.
- Corrected inaccurate descriptions in docs (Breeze Blade claim, version numbers, assumed stack libraries not yet present).

### Documentation
- Comprehensive alignment pass across **all major documentation** (architecture/*, database/*, design-system/*, api/overview.md) based on full system review.
- Added "Current Status / Target" disclaimers consistently so the gap between vision and current scaffold (early Inertia + partial shadcn UI only) is clear.
- Updated navigation.md, error-handling.md, tables.md, forms.md, dashboard.md, sports-engine.md, and more.
- Created `CURRENT_STATE.md` at project root — a single honest snapshot of actual implementation vs target vision (to be kept updated alongside TODOS and CHANGELOG).
- M1 Organization foundation implemented following schema, ADRs, patterns (Action, FormRequest, Policy, thin controller, tests).
- Added consistent "Current Status / Target Architecture" disclaimers to multi-tenant.md, authorization.md, testing.md, domain-model.md, security.md, sports-engine.md, schema.md, erd.md, migration-guidelines.md, design-system files, tables.md, forms.md, dashboard.md, frontend.md, and api/overview.md.
- Made documentation honest about scaffold state while preserving all vision, rules, and mandatory requirements from CLAUDE.md and AGENTS.md.

### Security
- N/A

---

## [0.1.0] - 2026-06-03

### Added
- Project initialization with Laravel 13 + Breeze (Inertia React).
- Core documentation foundation (`CLAUDE.md`, `ROADMAP.md`, `TODOS.md`).
- Initial Architecture Decision Records.
- Design system foundation using shadcn/ui.

---

## Versioning Guide

- **Major** (`1.0.0`): Breaking changes or major feature releases.
- **Minor** (`0.1.0`): New features that are backward compatible.
- **Patch** (`0.0.1`): Bug fixes and minor improvements.

---

## Types of Changes

- `Added` — New features
- `Changed` — Changes in existing functionality
- `Deprecated` — Soon-to-be removed features
- `Removed` — Removed features
- `Fixed` — Bug fixes
- `Security` — Security-related fixes
ok
