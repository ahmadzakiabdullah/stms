# TODOS

> Senarai tugasan untuk STMS.
> **Fokus utama:** Maintenance — pastikan kestabilan production.

---

## ✅ Current Status: Maintenance Mode

### Current Focus (MVP): Production Hardening

- [x] Disable public registration by default in production and remove first-organization fallback; invitation-based onboarding remains a future enhancement.
- [x] Restrict trusted proxies to explicit environment-provided IPs/CIDRs.
- [x] Split production bootstrap from SAF/demo account seeding and guard direct demo seeding in production.
- [x] Add Composer/npm vulnerability audit gates to connected CI.
- [x] Implement encrypted DB/upload backup, retention, destructive restore guard, SQLite restore drill, health checks, and supervised queue/scheduler lifecycle. Production MySQL/off-site restore drill remains operational follow-up.
- [x] Restore a clean Pint quality gate. Measured coverage, browser E2E, accessibility, and load-test baselines remain Sprint 3 work.

Evidence and priorities: `docs/audits/2026-07-31-enterprise-audit.md`.

### Sprint 3: Assurance, Accessibility, and Performance

- [x] Add PCOV coverage reporting and publish Clover output in CI; capture the first measured baseline from the connected CI run before setting a ratcheting threshold.
- [x] Add Playwright desktop/mobile critical-journey tests for super-admin, faculty, and dean roles.
- [x] Add axe WCAG 2.2 AA checks for login and dashboard; Chromium execution is enforced in CI.
- [x] Add dashboard query budget and k6 health/load scenario with p95/error thresholds.
- [x] Add frontend JS/CSS bundle budgets and remove Tailwind 3/4 mismatch; production build is warning-free.
- [ ] Execute the Playwright suite in connected CI and triage any real browser/accessibility findings.
- [ ] Establish a clean `tsc --noEmit` gate by typing legacy JSX UI modules, Ziggy's global `route`, and remaining page/model mismatches.
- [ ] Execute production-sized MySQL restore, authenticated k6 scenarios, and external alert delivery drills.

- [x] Matches page CRUD restored with pool/round fields and Name–Logo–VS–Logo–Name fixture layout.
- [x] Matches event filter URLs now use existing event slugs instead of UUID query values.
- [x] Participant logo upload repair: securely accept sanitized SVG logos and display upload validation errors.

- [x] Production `/portal/` 405/500 recovery: cleared environment-specific Laravel caches and restored the IIS front controller.
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

### 5. Participant Dashboard
- [x] `ParticipantDashboardController` — stats, per-faculty breakdown, per-event breakdown
- [x] Sport/faculty/status filters
- [x] `/participant-dashboard` route in Overview section

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
- **`npm run build`**: Requires build step on server after React/JSX changes

---

## 📌 Rules

- Follow CLAUDE.md + AGENTS.md patterns: Service + Actions + FormRequests + Policies
- All models use UUID PKs, SoftDeletes, BelongsToOrganization trait
- No hardcoded sport names, medal systems, or ranking formulas
- Frontend: React + Inertia.js + shadcn/ui only
- Thin controllers — business logic in Services/Actions
- Update TODOS, CHANGELOG, CURRENT_STATE when making real progress

---

*Fail ini dikemaskini 31 Julai 2026 — Sprint 1 production hardening selesai; fokus seterusnya ialah dependency audit, operasi/restore, observability, dan quality baselines.*
