# TODOS

> Senarai tugasan untuk STMS.
> **Fokus utama:** Maintenance — pastikan kestabilan production.

> **Snapshot:** 12 August 2026. Registration, draw, results, medal-tally, and public Sports programme UX updates are implemented and pushed in commit `912b385`. The latest full Laravel audit is recorded in `docs/audits/2026-08-12-full-laravel-audit.md`; its P0 findings are the current release blockers.

---

## ✅ Current Status: Maintenance Mode

### Current Focus (MVP): Production Hardening

- [x] Compact the Matches add/edit dialog and prevent long select/input content from widening its responsive layout.
- [x] Use concise `Sport — Category` labels in Matches while preserving full official event names for reports and stored records.
- [x] Refactor Matches into a responsive competition workspace with compact event selection, clearer filtering, desktop tables, and mobile-first fixture cards.
- [x] Reconcile the SAF sport-category catalogue to 30 active categories, safely soft-delete 19 unused duplicates, and prevent slug variants from being recreated by SAF seeders.
- [x] Adapt the Cosmic landing-page visual language to the tenant-safe public portal while retaining the Laravel/Inertia architecture, live schedules/results, localization, and accessibility safeguards.
- [x] Add organization-scoped public portal theme controls to Settings with validated UTeM Cosmic Blue defaults and an inline preview.
- [x] Refactor the draw-result workspace with a guided three-step flow, contextual actions, responsive group/fixture layouts, and a compact version-history section.
- [x] Add an event-wide fixture schedule to the draw result workspace so matches from multiple groups can be operated in one official venue order.
- [x] Refactor event registration workspace dialog to support multi-event batch registration for administrators, with selection summary and participant reset safety.
- [x] Refresh event registration workspace UX with accurate totals and a guided administrator quick-registration action.
- [x] Add active/unregistered faculty filtering and a `Show registered` option to the administrator event-registration dialog.
- [x] Add public Sports programme page at `/sports-programme` without conflicting with authenticated `/sports` administration routes.
- [x] Improve public medal tally with podium, summary statistics, progress, search, and logo fallback.
- [x] Improve draw-result pool refresh after participant moves and expose Create Fixtures action.
- [x] Improve results management with pending-match visibility and Record Next Result workflow.

- [x] Refactor the administrator dashboard into a responsive whole-system overview and prevent partial fallback payloads from persisting across refreshes.
- [x] Canonicalize the IIS application-directory request from `/saf/portal` to `/saf/portal/` before Laravel route matching.

- [x] Canonicalize the public URL without index.php and remove the clipped-looking overlap between the SAF hero and summary sections.

- [x] Set English as the default/fallback application language while retaining event-specific Malay public content.
- [x] Add a multi-language module with English (default) and Bahasa Malaysia locale switching in guest/authenticated layouts, plus localized auth and shared navigation labels.
- [x] Harden subfolder session-cookie handling for `/portal` deployments to prevent auth redirect loops when `SESSION_PATH` is omitted.
- [x] Complete the EN/BM localization hardening pass for core MVP administration and competition pages, including fresh-page locale application, locale-aware date formatting, dictionary cleanup, and static key coverage verification.

- [x] Support login using either a unique username or email, including legacy-account backfill and administrator username management.

- [x] Replace the generic welcome page with a tenant-safe Bahasa Malaysia SAF 2026 public hub for schedules, results, event progress, sports, and medal standings at / and /index.php.

- [x] Add a faculty participation confirmation page with tenant-safe filters, role-based faculty access, squad totals, and an A4 printable form.
- [x] Add a per-event Team Registration Form with roster auto-fill, tenant/role-safe access, exact quota rows, English A4 printing, faculty-dashboard access, and administrator access from Event Registrations.
- [x] Refactor `/dashboard` into attention-first role-aware workspaces and replace mixed sidebar flags with an explicit policy-aligned role matrix.

- [x] Disable public registration by default in production and remove first-organization fallback; invitation-based onboarding remains a future enhancement.
- [x] Restrict trusted proxies to explicit environment-provided IPs/CIDRs.
- [x] Split production bootstrap from SAF/demo account seeding and guard direct demo seeding in production.
- [x] Add Composer/npm vulnerability audit gates to connected CI.
- [x] Implement encrypted DB/upload backup, retention, destructive restore guard, SQLite restore drill, health checks, and supervised queue/scheduler lifecycle. A sanitized production-sized MySQL restore drill passed on 5 August 2026; actual production-backup/off-site recovery remains operational follow-up.
- [x] Restore a clean Pint quality gate. Measured coverage, browser E2E, accessibility, and load-test baselines remain Sprint 3 work.

Evidence and priorities: `docs/audits/2026-07-31-enterprise-audit.md`.

### Audit Remediation: Security, Reliability, and Scalability (12 August 2026)

Source of truth: `docs/audits/2026-08-12-full-laravel-audit.md`.

#### P0 — Release Blockers

- [x] Close the user-management escalation exposure with policy-authorized Form Requests plus controller/service defense-in-depth.
- [x] Prevent org-admins from assigning the `super-admin` role or any role outside their permitted role set.
- [x] Force org-admin user creation and updates to the authenticated tenant; reject submitted foreign `organization_id`, `participant_id`, and `sport_id` values.
- [x] Add feature tests proving org-admins cannot create or move users across tenants, assign `super-admin`, or attach foreign participant/sport records.
- [ ] Review current users, role assignments, and activity logs for unexpected super-admin grants or cross-tenant accounts; rotate affected credentials if any are found.
- [x] Add an explicit production guard to `DummyFutsalMenSeeder` and `DummyFootballMensResultsSeeder`, require approved opt-in, and ensure they are absent from normal production seeding paths.
- [x] Repair the five failing email-verification middleware tests by setting the security flag before route bootstrap; the protected-route suite is green.
- [x] Restore local release gates: 407 PHPUnit tests / 1,635 assertions, Pint, tenant checks, dependency audits, typecheck, inventory validation, and mapped-drive frontend production build are green. Connected CI remains required before release.

#### P1 — Next Hardening Sprint

- [x] Apply tenant-aware validation to user, participant, registration, session, event, tournament, match, result, draw, and squad workflows.
- [ ] Require an explicit selected tenant context for super-admin mutations instead of allowing accidental unscoped writes.
- [ ] Move authorization into Form Requests where practical while retaining Policies/Gates as the authoritative access-control layer.
- [x] Refactor `EventParticipantController` into dedicated registration, batch-registration, status, squad-member, and notification services/actions with Form Requests and per-registration database transactions.
- [x] Convert registration, verification, and result database notifications to queued notifications using Laravel `ShouldQueue`; worker retry/backoff, failed-job alerting, and operational health checks remain.
- [x] Define notification retry (3), backoff (10/30/60 seconds), timeout (60 seconds), Redis `after_commit`, and regression tests; failed-job alerting and worker health checks remain operational follow-up.
- [x] Cache the public portal payload and medal tally; query upcoming and completed fixtures separately with SQL limits instead of loading every fixture into memory.
- [x] Add a public portal cache invalidation service hook and wire it into result, fixture, event, participant, and public-setting create/update/delete mutations.
- [x] Add feature coverage proving public portal cache keys are invalidated for the changed organization without affecting another organization.
- [x] Add request correlation IDs and correlation-aware fallback logging across controllers, dashboard, reports, and Inertia shared props.
- [ ] Add a secure production environment baseline requiring `APP_DEBUG=false`, enforcing CSP, HTTPS secure cookies, Redis cache/queue, and a configured mail provider.
- [x] Resolve Pint failures and update the documented inventory to the measured 125 routes, 59 migrations, 39 controllers, 38 pages, and 92 PHP test files.
- [x] Validate `npm run build` from a mapped/local working directory; UNC execution fails because Vite loses the project root, while the mapped-drive build passes.

#### P2 — 30–60 Day Improvements

- [ ] Add tenant-isolation and policy test matrices for every privileged role and destructive tournament workflow.
- [ ] Add query-count regression tests and non-production query profiling to detect N+1 issues.
- [x] Convert fixture and result Excel exports from eager collections to authorized query-based chunked exports (500 rows); ranking/export queue orchestration remains a follow-up.
- [ ] Introduce cache/query budgets for public portal, medal tally, dashboard, registration, results, and reporting endpoints.
- [ ] Add audit alerts for super-admin role changes, repeated denied cross-tenant access, failed queue jobs, backup failures, and elevated HTTP 5xx rates.
- [ ] Complete an approved multi-worker staging load test, verify a real external alert destination, and restore an actual off-host production backup in isolation.
- [ ] Establish and ratchet coverage targets for Policies, tenant validation, upload sanitization, seeders, queues, and critical competition workflows.
- [ ] Create a versioned release tag only after all P0 tasks and release gates pass on a clean tree.

### Sprint 3: Assurance, Accessibility, and Performance

- [x] Add PCOV coverage reporting and publish Clover output in CI; capture the first measured baseline from the connected CI run before setting a ratcheting threshold.
- [x] Add Playwright desktop/mobile critical-journey tests for super-admin, faculty, and dean roles.
- [x] Add axe WCAG 2.2 AA checks for login and dashboard; Chromium execution is enforced in CI.
- [x] Add dashboard query budget and k6 health/load scenario with p95/error thresholds.
- [x] Add frontend JS/CSS bundle budgets and remove Tailwind 3/4 mismatch; production build is warning-free.
- [x] Execute the Playwright suite in connected CI and triage browser/accessibility findings. The `browser-e2e` job passed all six journeys on commit `ae42a50`; the workflow's separate failure was npm audit, not Playwright.
- [x] Establish a clean `tsc --noEmit` gate with legacy JSX compatibility declarations, Ziggy global typing, corrected Inertia v2 options, and aligned page/model payload types; enforce it in CI.
- [x] Execute an isolated encrypted MySQL restore using a sanitized 3.47 MB dataset (larger than the measured 2.66 MB production schema); restore completed in 2.977 seconds with all migrations, health checks, and key counts verified.
- [ ] Commit the k6 CSRF/dynamic-cookie/per-VU account correction now present in the working tree, then rerun the authenticated scenario against an approved multi-worker staging deployment. The earlier localhost development-server run passed 190/190 checks with 0% HTTP errors but failed p95 (4.24 s versus 750 ms).
- [ ] Configure a real external Slack/Papertrail or uptime destination and confirm operator receipt. Local critical-webhook transport passed, but external delivery is not yet evidenced.
- [ ] Copy an actual production backup off-host and complete an approved isolated restore; the sanitized drill validates mechanics and RTO but not production-backup custody/recovery.

### Sprint 4: Release Readiness (10 August 2026)

- [x] Apply `verified` email middleware consistently across all authenticated routes.
- [x] Document CSP enforcing mode readiness (`CSP_REPORT_ONLY=false`).
- [x] Add release runbook at `docs/deployment/release-runbook.md`.
- [ ] Create first versioned release tag (`v0.1.0`) after CI passes on clean tree.
- [ ] Configure production email provider (SMTP/Ses/Postmark) — replace `MAIL_MAILER=log`.
- [x] Enable CSP enforcing mode through the production environment template (`CSP_REPORT_ONLY=false`) and protect it with an automated enforcing-mode test.

- [x] Matches page CRUD restored with pool/round fields and Name–Logo–VS–Logo–Name fixture layout.
- [x] Matches event filter URLs now use existing event slugs instead of UUID query values.
- [x] Participant logo upload repair: securely accept sanitized SVG logos and display upload validation errors.

- [x] Production `/portal/` 405/500 recovery: cleared environment-specific Laravel caches and restored the IIS front controller.
- [x] Production `/portal/` redirect-loop recovery: render the public portal at the IIS mount path instead of redirecting the normalized Laravel route back to itself.
- [x] Temporarily make email verification optional: unverified authenticated users may enter the system and `/verify-email` redirects to the dashboard.
- [x] Link the authenticated desktop/mobile sidebar logo and brand area to the dashboard.
- [x] Link header logo and application name to the public home URL, including the `/saf/portal/` subfolder.
- [x] Remove remaining hardcoded `/portal/` public-header and canonical links so the deployed subfolder is preserved.
- [x] Use the organization logo from Settings in the public portal header.
- [x] Preserve locale selection with a root-path fallback cookie for deployments with legacy session cookie paths.
- [x] Allow the locale preference endpoint to work behind the legacy IIS subfolder CSRF-cookie mismatch.
- [x] Register the locale CSRF exemption through Laravel's global middleware configuration.
- [x] Restore the production public portal data selection with explicit `utem` organization and `saf-2026` session slugs.
- [x] Show undated scheduled fixtures on the public portal as dates to be determined instead of hiding the schedule.
- [x] Production-wide bootstrap 500 recovery: defer the explicit trusted-proxy allowlist lookup until request middleware execution and cover it with a regression test.
- [x] Local `db4stms` schema-drift recovery: encrypted pre-migration backup created, all migrations reconciled without `migrate:fresh`, and HTTP/PHP regression checks passed.
- [x] Restore the complete SAF 2026 local dataset after schema reconciliation and verify the administrator password remains user-controlled.

**Matlamat:** Projek dalam fasa maintenance — semua isu kritikal telah dibaiki. Semua Fasa 0-3 selesai. Semua ciri MVP telah dibangunkan.

---

## ✅ Completed Features (8-10 Julai 2026)

### 1. Event Registration Cut-off Date
- [x] Migration `2026_07_08_000001` — `registration_deadline` column on `events`
- [x] Validation `after:now` — cannot set deadline in the past
- [x] UI: Deadline display in event grid, disabled register button if deadline passed

### 2. Role/Permission Management UI
- [x] `RoleController` — CRUD with grouped permission checkboxes
- [x] `RolePolicy` — protect super-admin role from deletion/edit
- [x] `/roles` route in Administration sidebar section

### 3. In-App Notifications
- [x] `notifications` table migration (`2026_07_08_000002`)
- [x] `EventParticipantConfirmed`, `EventParticipantRejected`, `NewEventRegistration` classes
- [x] `NotificationController` — mark read, mark all read, unread count
- [x] Bell dropdown in AuthenticatedLayout header
- [x] `/notifications` full page with all notifications

### 4. Bulk Squad Import
- [x] `SquadMembersImport` (Excel/CSV with heading row, quota validation)
- [x] Import dialog + template download in Faculty Dashboard
- [x] Flexible athlete quota modes for categories: gender-based, open total, mixed total with optional minimum male/female counts

### 5. Registration Overview (merged into Dashboard)
- [x] Registration Overview section (stats, per-faculty breakdown, per-event breakdown + filters) merged into `/dashboard` — `ParticipantDashboardController`, `/participant-dashboard` route and page removed
- [x] Squad Composition cards (total/male/female athletes/officials) in Registration Overview, filter-aware

### 6. Logo/Crest Upload
- [x] Migration `2026_07_08_000003` — `logo_path` on `participants`
- [x] Upload in Participant form, display in table/view dialog

### 7. Draw/Group Allocation + Auto Fixture Generation
- [x] `pools` table migration (`2026_07_08_000004`)
- [x] `pool_id` on `event_participants` and `matches`
- [x] `format` + `pool_size` on `events`
- [x] `Pool` model, `DrawService`, `DrawController`
- [x] Draw button (super-admin only) on Events page with confirmation dialog
- [x] Round-robin fixture generation via Circle Method algorithm

### 8. Navigation Restructured
- [x] Flow-based sidebar: Main → Setup → Administration → Registration → Competition → Reports
- [x] English labels throughout
- [x] Role-based visibility (super-admin, faculty-rep, dean)

### 9. Authentication & Data Reset
- [x] Database reset (`migrate:fresh`) — all test data cleaned
- [x] Organization: **Universiti Teknikal Malaysia Melaka (UTeM)**
- [x] Session: **Sukan Antara Fakulti 2026** (1-30 September 2026)
- [x] 2 Tournaments: Fasa 1 (11-13 Sept), Fasa 2 (25-27 Sept)
- [x] 24 sports (English names), 30 categories/events with quota fields
- [x] 8 faculties (FTKEK, FTKE, FTKM, FTKIP, FTMK, FPTT, FAIX, STEP)
- [x] 16 users (8 faculty reps + 8 deans) — password: `password`
- [x] `SAF2026DataSeeder` — reusable, idempotent data seeder
- [x] `DatabaseSeeder` — now calls `SAF2026DataSeeder`
- [x] Dean role created with proper permissions

### 10. Squad Quota UX & Matrix No (2 Ogos 2026)
- [x] `matrix_no` column on `squad_members` (nullable, unique per event participant)
- [x] Required `matrix_no` on squad member form + per-row delete confirmations
- [x] CSV import template updated to `name, role, matrix_no, ic_passport, phone` with matrix_no required per row
- [x] Color-coded quota progress bars (male/female/officials) in faculty registration cards
- [x] Quota-complete checkmarks + green "Squad complete" badge when quotas filled

### 11. Super-Admin UI/UX Refactor (2 Ogos 2026)
- [x] Sidebar regrouped by workflow: Overview / Competition Setup / Registration / Competition / Administration / Reports
- [x] Unique icons per menu item (Roles now uses KeySquare) + active-item accent indicator
- [x] Organization context chip for super-admins at sidebar bottom
- [x] Dashboard: prioritized KPI cards (sessions, events, registrations, matches) + compact secondary stats
- [x] Registration pipeline card (pending/confirmed/rejected) with progress bar and review action
- [x] Registrations-by-sport bar chart + Quick Actions panel
- [x] Squad member listings for super-admins on Event Registrations page (expandable rows with role badges and matrix numbers)
- [x] Event Registrations page: clickable status stat cards, status filter, and Approve/Reject actions for super-admins (notifies faculty representative)
- [x] Event Registrations filtering fixed: sport/category/search now filter registration rows and All Events tab, not just the participant list
- [x] Super-admin notification triage: Action Required default tab, personal inbox, organization/type/read filters, severity metadata, same-organization admin recipients, and separately filtered System Activity

### 12. Faculty Dashboard Merged into `/dashboard` (3 Ogos 2026)
- [x] Faculty representatives now do everything on the single `/dashboard` page: register their faculty for sports (searchable, grouped by tournament) and manage squad members with quota bars
- [x] `/faculty` redirects to `/dashboard`; removed the `faculty.dashboard` route, `FacultyDashboardController::index`, and the dashboard summary component (`Dashboard/FacultyDashboard`)
- [x] Faculty dashboard data assembled by new `FacultyDashboardService` (used by `DashboardController`)
- [x] Event registration by faculty reps redirects back to `/dashboard` (was the admin Event Registrations page)
- [x] Sidebar uses an explicit role matrix: faculty representatives see Dashboard, Participation Confirmation, and Notifications; deans see verification, confirmation, and notifications; admin-sport sees competition operations; administrators see policy-authorized setup/administration.

### 13. Multi-Select Event Registration (3 Ogos 2026)
- [x] "Register for Events" dialog supports multi-select: checkboxes, search, grouped by tournament, Select all/Clear all toggle, deadline-passed events disabled
- [x] New batch endpoint `POST /dashboard/registrations` (`event-participants.store-batch`) registers multiple events in one submit; per-event dean/admin notifications; deadline-passed or already-registered events skipped with error summary
- [x] `notifyRegistrationRecipients` helper extracted in `EventParticipantController` (shared by single and batch registration)

---

## ⏳ Deferred (Ciri Lanjutan)

- Accreditation System
- Live Scoring & Real-time Updates
- Mobile App (Flutter)
- Advanced Analytics & Reporting Dashboard
- AI Features
- REST API Layer (`/api/v1`)

---

## 🐛 Known Issues

- **PHP 8 reserved keyword**: Model `Match` renamed to `Fixture` (table still `matches`)
- **UNC path**: `\\10.1.2.22\e\others\saf\portal` cannot run CLI directly — use local drive or `u:;` mapping
- **Production DB**: Must run `php artisan migrate` on server after deploying new migrations
- **OPcache**: Server may cache old PHP files — needs cache clear after deploying PHP changes
- **`npm run build`**: Requires build step on server after React/TypeScript changes

---

## 📌 Rules

- Follow CLAUDE.md + AGENTS.md patterns: Service + Actions + FormRequests + Policies
- All models use UUID PKs, SoftDeletes, BelongsToOrganization trait
- No hardcoded sport names, medal systems, or ranking formulas
- Frontend: React + Inertia.js + shadcn/ui only
- Thin controllers — business logic in Services/Actions
- Update TODOS, CHANGELOG, CURRENT_STATE when making real progress

---

*Fail ini dikemaskini 5 Ogos 2026 — connected-CI Playwright/axe dan restore MySQL terasing bersaiz produksi telah dibuktikan. Prestasi k6 pada staging berbilang worker, restore backup produksi/off-site, dan penerimaan amaran luaran sebenar masih memerlukan bukti persekitaran.*
