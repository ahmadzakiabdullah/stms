# Changelog

## Unreleased — Single current temperature weather (19 August 2026)

- Replaced the data.gov.my daily min/max forecast with a single Open-Meteo current temperature for the public announcement bar, keeping the stale/last-good fallback and the broken-CA-bundle retry.

## Unreleased — Documentation sync (19 August 2026)

- Synced documentation with the current working tree: updated `system-overview.md` (41 pages, 137 routes, split public portal), the public portal description in `CURRENT_STATE.md` and the product decision in `TODOS.md`.
- Marked `TODOSUIUX.md` as a superseded 17 August baseline following the standalone public page decision.
- Refreshed the workspace data snapshot in `docs/database/schema.md` from a read-only 19 August query (3 tournaments, 1 result, 4 queued jobs, 898 activity logs).

## Unreleased — Public portal page expansion (19 August 2026)

- Added public Sports, Schedule, Results, Faculties, Venues and Live pages backed by the configured public session.
- Added public News, Downloads, FAQ and About information pages, shared Cosmic navigation/footer, sitemap entries and public route tests.

## Unreleased — Public matches page (19 August 2026)

- Added the tenant-scoped public `/matches` page for all official fixtures and latest results; moved authenticated match management to `/manage/matches` while retaining its route names and permissions.

## Unreleased — Release checklist and controller orchestration (18 August 2026)

- Added portable Predis support, hardened the production Docker runtime requirements, and added an isolated production-like staging Compose stack.
- Fixed two deployment-image defects found by the staging drill: PECL Redis could not compile without a build toolchain, and Supervisor's log directory was absent.
- Strengthened authenticated k6 with persistent per-VU cookies, health-token support and a mandatory 100% application-check threshold.
- Recorded an encrypted off-host backup and isolated MySQL 8 restore (RTO 7.699 seconds), plus a passing 10-VU multi-worker staging run with 0% HTTP failures and 81.543 ms p95.
- Added a five-minute GitHub Actions production liveness monitor; forced failure opened and assigned issue #75, while a verified recovery probe closed it.
- Recorded and recovered a brief Windows/IIS autoload-path incident caused by generating live Composer metadata through drive alias `S:` instead of production path `E:\others\saf`; the release runbook now prohibits alternate-path Composer operations on live vendor files.
- Reorganized `TODOS.md` into atomic repository and production release gates, removing duplicate mail, backup, deployment and browser-evidence tasks.
- Extracted Dashboard, Event Participant and Event index query/payload orchestration into dedicated services while preserving controller authorization and mutation boundaries.
- Recorded connected CI #110 on commit `e535e4b` with all six jobs passing, including 75.03% PCOV statement coverage (4,676/6,232) against the new 74.5% ratchet and browser E2E.
- Added the non-destructive `stms:release-preflight` command for configuration, DB/Redis connectivity, mailer completeness, off-repository backup freshness/checksum, monitoring and public tenant selectors.
- Added a reusable sanitized release-evidence template covering CI, mail, DB grants, backup/restore, load, alerts, deployment smoke checks and GO/NO-GO approval.
- Revalidated the working tree locally: PHPUnit 439/439 (1,948 assertions), Pint, TypeScript, inventory, tenant bypass, dependency audits, Vite build/budget and Playwright/axe 8/8 all pass.

## Unreleased — Audit remediation (17 August 2026)

- Restored the complete quality gate: 434/434 PHPUnit tests (1,937 assertions), Pint, TypeScript, tenant guard, Vite build/budget, clean dependency audits and 8/8 isolated desktop/mobile Playwright/axe journeys.
- Enforced policy-backed read authorization across sensitive indexes and added a six-role manual-URL matrix plus real tenant payload assertions.
- Made draw participant movement regenerate scheduled fixtures atomically while protecting started/completed competition data.
- Replaced hardcoded ranking orchestration with a strategy contract/registry and validated session/tournament rule storage.
- Added tenant-explicit guest branding, self-hosted fonts, SEO metadata, sitemap, filtered guest routes, public refresh feedback and accessibility fixes.
- Drained 32 queued database notifications to 0 pending/0 failed after confirming their local-only delivery channel.
- Realigned installed dependencies with the safe lockfile (Guzzle 7.15.2 and PSR-7 2.13.0) to close newly reported security advisories.
- Confirmed connected CI run #101 on commit `35b9dac`: dependency audit, lint, test, coverage, build and browser E2E all passed.
- Recorded product-owner confirmation for the 1–31 October 2026 dates, one tournament, 30 events, eight contingents and single-page public information architecture; operational records remain editable for later official changes.
- Upgraded `actions/checkout`, `actions/setup-node` and `actions/upload-artifact` to official v7 releases using the Node.js 24 action runtime; CI #105 passed with no Node.js 20 deprecation annotations.
- Added tenant-editable public contact settings for the official address, email, phone, Facebook, Instagram, TikTok and YouTube channels; validated and safely filtered values now render on the public Contact page.

## Unreleased — Documentation and production audit baseline (17 August 2026)

- Audited the repository, runtime workspace, database state and public production portal; recorded the evidence in `docs/audits/2026-08-17-full-project-and-production-audit.md`.
- Reconciled canonical Markdown with the current 125-route / 60-migration / 39-controller / 38-page / 92-test-file inventory.
- Corrected public-portal documentation: production currently exposes a single homepage with Sports/Schedule/Results/Medal sections plus `/contact-us`; previously documented standalone public routes are absent.
- Recorded the current NO-GO release state: 420 of 422 PHPUnit tests pass, Pint reports six files, the working tree is not clean, and no version tag exists.
- Documented runtime production-baseline drift, read-authorization gaps, ranking configurability debt, and UUID/soft-delete exceptions without changing application behavior.

## Unreleased — Cosmic-inspired public portal (14 August 2026)

- Added an optional inverse logo for every participant, including sanitized SVG/raster upload, independent replace/remove controls, light/dark previews, public payload support, and a shared surface-aware renderer that falls back safely when one variant is missing.
- Increased faculty logos from 36px to 48px in public fixture/result cards and from 48px to 56px in medal-standing cards; the faculty dashboard now shows the same logo at 48px on mobile and 56px on larger screens, while draw-result matchups use 48px logos and compact group rosters use 40px logos.
- Tightened the Matches add/edit dialog to a more compact responsive width and constrained all form controls so long event or participant values no longer stretch the modal layout.
- Simplified event labels throughout Matches to the contextual `Sport — Category` format while retaining the full official event and tournament names in stored data and secondary context.
- Corrected the Matches event relation mapping from frontend camelCase to Laravel's serialized `sport_category` key so category names appear in concise event labels.
- Refactored Matches into a cleaner operations workspace with a compact event selector, consolidated search/status toolbar, manual refresh control, and purpose-built mobile fixture cards while retaining the detailed desktop tables.
- Reduced the live SAF sport-category catalogue from 49 to the intended 30 by soft-deleting 19 verified duplicates unused by active events; added a guarded dry-run cleanup command and made SAF seeders reuse an active same-name category instead of recreating slug variants.
- Adapted the Cosmic visual language for the Laravel/Inertia public homepage without importing its incompatible Next.js 15, React 19, or Tailwind CSS 4 runtime.
- Added a floating glass navigation shell, atmospheric hero treatment, competition progress panel, staggered overview cards, responsive fixture/result cards, medal cards, and reduced-motion-safe decorative animation.
- Brought the public Contact page onto the same Cosmic navigation, hero, card, typography, and footer system so public routes now present one consistent identity.
- Added tenant-scoped public portal colour controls and a live preview to Settings, with validated semantic CSS tokens and UTeM Cosmic Blue defaults for future branding changes without code edits.
- Consolidated the public header Login action into a reusable, theme-aware button with stronger mobile sizing, focus visibility, interaction feedback, and consistent rendering across Home and Contact.
- Added an accessible mobile hamburger menu for public navigation, including localized open/close labels and a compact header treatment for narrow screens.
- Rebalanced the public header into stable desktop brand/navigation/action columns and a simplified mobile brand/menu layout; locale and Login actions now live inside the mobile menu instead of competing for header space.
- Consolidated desktop navigation into a shared pill-style component with active-page indication, consistent Home/Contact links, balanced three-zone alignment, and an `xl` breakpoint that avoids crowding on tablet-sized screens.
- Refocused the public homepage hero around the competition itself with tighter typography, schedule/results actions, a dedicated date card, theme-driven progress dashboard, and a live next-fixture preview.
- Replaced the hero's hardcoded green atmosphere with a theme-driven UTeM blue gradient using the configurable Dark, Primary, and Accent tokens.
- Replaced the announcement message with a localized current date on the left and a cached, server-fetched Durian Tunggal temperature on the right, with a safe unavailable-state fallback.
- Updated public typography to Barlow Condensed 700/800 for competition headings and display numbers, paired with Plus Jakarta Sans Variable for navigation and body copy; both families are bundled locally.
- Switched the public Barlow Condensed display face to its italic 700/800 cuts for a more dynamic sports identity while retaining upright Plus Jakarta Sans body text.
- Replaced the condensed display face with an all–Plus Jakarta Sans typographic system that approximates the Premier League site's clean regular/bold hierarchy without copying its proprietary PremierLeague font files.
- Preserved live tenant-safe SAF data, locale switching, automatic partial refreshes, public contact navigation, and the existing authenticated application design.

## Unreleased — Draw result workspace UX (13 August 2026)

- Refactored the draw result page into a clear three-step competition workflow with contextual primary actions, responsive group cards, mobile fixture cards, safer pending-change feedback, and a compact expandable audit history.
- Added a consolidated event-wide match schedule ordered by match number, with centered team-logo matchups and shared venue/time context; pool cards now focus on group membership without duplicating fixtures.
- Added English and Bahasa Malaysia labels for the new workflow and status guidance.

## Unreleased — Public portal typography (13 August 2026)

- Changed public portal heading typography to Noto Sans Variable and removed the unused Sora font dependency.

## Unreleased — Competition overview cards (13 August 2026)

- Reworked the public homepage Competition Overview section into responsive statistic cards with icons, hover elevation, and clearer visual hierarchy.

## Unreleased — Public portal header refresh (13 August 2026)

- Updated the public portal header hover/focus state with a navy-to-teal gradient, gold accent border, clearer navigation hover treatment, and stronger contrast for logo and navigation text.

## Unreleased — Audit P0 remediation (12 August 2026)

- Closed an org-admin privilege-escalation and cross-tenant user-management path with policy-authorized Form Requests, tenant-aware organization/participant/sport validation, explicit `super-admin` role rejection, and controller/service defense-in-depth.
- Added user-management regression tests for foreign tenants, foreign participant/sport relations, and forbidden super-admin assignment.
- Guarded the Futsal Men's and Football Men's dummy seeders against production execution unless `ALLOW_DEMO_SEEDING=true` is explicitly approved, with production regression tests.
- Corrected email-verification test bootstrap configuration and restored the complete local quality gate: 407 PHPUnit tests / 1,635 assertions, Pint, TypeScript, tenant and inventory checks, and the Vite production build all pass.
- Fixed the unverified-user redirect loop by clearing the protected intended URL before the verification prompt returns to the dashboard; verified-route protection remains enforced.
- Fixed subfolder session-cookie handling by deriving the cookie path from `APP_URL` (for example `/portal`) when `SESSION_PATH` is not explicitly set.

- Completed a broad EN/BM localization pass across administration and competition pages, forced page remounts after locale switching, added locale-aware date/number helpers, removed duplicate dictionary keys, and verified that all statically referenced translation keys exist in both dictionaries.

## Unreleased

- Fixed guest and authenticated header logo/name links to resolve the public home route under the deployed `/saf/portal/` subfolder.
- Fixed the public portal header and `index.php` canonicalization to use the generated public-home URL instead of hardcoded `/portal/` paths.
- Fixed the public portal header to read the configured organization logo from Settings.
- Fixed locale persistence when deployments retain a legacy `/portal` session-cookie path by adding a root-path locale fallback cookie.
- Fixed the locale switch endpoint returning HTTP 419 on the IIS subfolder deployment by excluding the preference-only endpoint from CSRF validation.
- Registered the locale CSRF exemption through Laravel's global middleware configuration for production consistency.
- Refactored the administrator dashboard into a role-aware system command centre with prioritized KPIs, operational readiness, registration pipeline, participation coverage, match completion, upcoming events, sport participation ranking, recent sessions/tournaments, and responsive quick actions.
- Hardened dashboard refresh behavior: partial query fallbacks are no longer cached, cache keys include tenant context, and regression coverage verifies consistent repeated loads.
- Canonicalized the IIS application-directory request so `/saf/portal` redirects to `/saf/portal/` before Laravel route matching instead of returning 404.
- Added a release runbook at `docs/deployment/release-runbook.md` covering versioned release procedure, deployment steps, rollback, expand/contract migration patterns, and post-release monitoring.
- Applied `verified` email middleware consistently across all authenticated routes (previously only `/dashboard` enforced it).
- Documented CSP enforcing mode readiness: set `CSP_REPORT_ONLY=false` to enforce after report-only observation period.
- Added repository housekeeping updates: ignored local `.Jules/` artifacts, refreshed i18n architecture docs to match the implemented EN/BM locale module, and documented folder hygiene standards under `docs/`.
- Standardized core architecture/database terminology to the canonical hierarchy term `Match` while preserving implementation notes that the model class remains `Fixture` due the PHP reserved keyword.
- Added a non-destructive `tmp/` housekeeping audit report with inventory summary and retention guidance.
- Added a multilingual module with English as default and Bahasa Malaysia as selectable locale. Implemented locale middleware, session-based locale persistence, a global `/locale` switch endpoint, shared Inertia locale metadata, and UI language switchers for guest/authenticated layouts. Localized shared navigation and authentication screens in both languages.
- Added `LocaleSwitchTest` feature coverage for valid locale switching, invalid locale validation, and first-render locale application (`<html lang>`).
- Expanded EN/BM localization coverage on `Dashboard`, `Settings`, and `Notifications` pages, including headings, action labels, filters, and key status copy.
- Expanded EN/BM localization coverage on `Events`, `Matches`, and `Results` pages for key headings, stat labels, actions, and search/filter copy.
- Fixed the production IIS `/portal/` self-redirect that caused `ERR_TOO_MANY_REDIRECTS`, with regression coverage for both trailing-slash variants.
- Temporarily removed mandatory email verification from authenticated system routes and redirect the verification prompt to the dashboard.
- Linked the authenticated sidebar logo and brand area to the dashboard on desktop and mobile navigation.
- Restored the production public portal dataset by configuring its explicit UTeM organization and SAF 2026 session selectors.
- Displayed scheduled fixtures without assigned dates on the public portal, using the existing to-be-determined date state.

## Unreleased — Hardening follow-up (7 August 2026)

- Changed `TenantContext` to a container-scoped lifecycle and added fail-closed tenant-aware queue middleware.
- Made the public portal resolve `PUBLIC_ORG_SLUG` before `PUBLIC_SESSION_SLUG`, with safe empty states and duplicate-slug isolation tests.
- Scoped `User` route-model binding for non-super-admins so cross-organization management requests return 404.
- Added report-only CSP hardening and a CI allowlist check for tenant-scope bypasses.
- Corrected the authenticated k6 scenario to use Laravel's XSRF cookie, dynamic session cookies, per-VU cookie jars, and configurable account lists.
- Added versioned draw snapshots with deterministic seeds, actor metadata, soft-deleted pool history, guarded rollback, and a history UI.
- Sanitized SVG uploads consistently for participant, settings and sport assets with UUID-generated filenames.
- Enabled real verified-email middleware behavior and fixed public registration username persistence.
- Updated PHP and npm lock files to remove all currently reported dependency advisories.
- Made Redis the production cache, queue and session backend and enabled the PHP Redis extension in the container image.

## 5 August 2026

### Operational assurance evidence

- Recorded a successful connected-CI Playwright/axe run for all six desktop/mobile journeys on commit `ae42a50`.
- Completed an isolated AES-256 MySQL restore using a sanitized 3.47 MB dataset, larger than the measured 2.66 MB production schema. Restore completed in 2.977 seconds with all 56 migrations, health checks, and key row counts verified.
- Exercised the authenticated k6 path: corrected in-memory CSRF and dynamic session-cookie handling produced 190/190 successful checks and 0% HTTP errors, while p95 4.24 seconds failed the 750 ms target on the single-process development server.
- Verified controlled degraded-health logging delivered a critical Slack-format POST to a localhost receiver. Real external operator receipt remains pending because no Slack/Papertrail destination is configured.
- Documented that `stms:restore --force` still prompts interactively and that a real production-backup/off-site restore remains outstanding.
- Removed all temporary validation databases, archives, containers, and application servers after the drills.

All notable changes to the STMS project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),  
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

- Hardened the multi-tenant context lifecycle (P0): `TenantContext` state now lives on a container-scoped singleton instead of process-global statics, so it can never leak across requests, queue workers, or long-running servers (e.g. Octane). `SetTenantContext` resets the context before every request, records super-admin and guest requests as an explicit auditable bypass (with a reason), and always cleans up in a `finally` block plus a `terminate` hook. Added `TenantContext::setBypass`/`isBypassing`/`bypassReason`/`isInitialized`/`requireOrganization` (fail-closed when a tenant-required operation has no organization), and fixed `isConsole`/`isQueue` to reflect the recorded context only. Added `TenantContextLifecycleTest` covering sequential-request isolation, super-admin/guest non-inheritance, exception cleanup, queue-context isolation, and fail-closed behavior.

- Split an event draw into a two-phase flow: "Draw" now only creates groups (random pool assignment, no fixtures) and opens the Draw Result page for review; a new "Generate Fixtures" button on the Draw Result page creates round-robin fixtures once groups are confirmed. The Events page offers contextual actions per state: "Draw" when no groups exist, "Re-draw" when groups exist but no fixtures, and a destructive "Reset Draw" (blocked once a match has started) that deletes all groups and fixtures. Added `events.generate-fixtures` and `events.reset-draw` routes and split `DrawService` into `drawGroups`, `generateFixtures`, and `resetDraw` (with `drawAndGenerateFixtures` retained for backward compatibility).

- Fixed the production-wide HTTP 500 caused by resolving config('app.trusted_proxies') before Laravel registered its configuration repository. Proxy allowlists now resolve in an application middleware at request time, preserving explicit TRUSTED_PROXIES handling while allowing Laravel to bootstrap.

- Redirected `/portal` (without trailing slash) permanently to the canonical `/portal/` URL via a Laravel route, fixing a 404 when the site root rewrites bypass the IIS canonical rule.

- Made the public portal refresh live: it now polls every 30 seconds (Inertia partial reload of results/upcoming/medals/stats/updated_at, paused when the tab is hidden, refetched on visibility return), exposes a manual "Muat semula" button, and shows an honest last-updated timestamp derived from the actual session/fixture/result data instead of the render time.

- Fixed completed matches with no `scheduled_at` (e.g. results entered before scheduling) being hidden from the public portal: the results section now shows every completed fixture with a result regardless of schedule, while the upcoming schedule and the completion-progress stat still exclude unscheduled matches.

- Canonicalized the public portal at /portal/, redirected legacy /portal/index.php requests, replaced the IIS filename redirect with an internal rewrite, and removed the negative-margin hero/statistics overlap that could look visually clipped.

- Set English as the application default and fallback language, including the username-or-email login label; the Bahasa Malaysia SAF public portal remains event-specific content.

- Added case-insensitive username-or-email authentication with the existing password and rate limiter. Introduced unique usernames, safe backfill for existing accounts, automatic username generation for legacy creation paths, and username management in registration/admin user forms.

- Replaced the generic public welcome page with a Bahasa Malaysia SAF 2026 information hub at / and /index.php, showing tenant-scoped schedules, latest results, competition progress, sports, and a medal tally derived from completed final/bronze fixtures. Added privacy-focused feature coverage and an optional PUBLIC_SESSION_SLUG deployment selector.

- Refactored dashboard and sidebar UX by system role: administrators get an attention-first operational overview, admin-sport gets competition actions, faculty representatives retain the registration/squad workspace, deans enter verification from /dashboard, Notifications moved to Overview, and navigation now uses an explicit policy-aligned role matrix. Updated current-state, design-system, architecture, roadmap, TODO, README, and future API Markdown.

- Added a tenant-safe, A4 printable Team Registration Form for every event registration. The form auto-fills tournament, sport/category, faculty/team, active officials and athletes, quota-aware blank rows, declaration and signature blocks; faculty representatives access it from each expanded registration card, while super-admins and org-admins open it from the Actions column on Event Registrations. The form UI is fully English and its Back action returns to Event Registrations. Official and athlete table rows now match the configured category quotas exactly, with no fixed minimum rows.

- Added a tenant-scoped Participation Confirmation form matching the supplied layout: UTeM and tournament logos, secretariat address, separate phase tables with Yes/No confirmation, auto-filled dean name/date, signature line, official stamp area, and A4 printing. Tournament branding is configurable in Settings.

- Merged the Faculty Dashboard into the main Dashboard (`/dashboard`) for the `faculty-representative` role: faculty reps now register their faculty for sports and manage squad members on a single page, and are returned to the dashboard after each registration. `/faculty` redirects to `/dashboard`; removed the `faculty.dashboard` route, `FacultyDashboardController::index` (data moved to a new `FacultyDashboardService`), the dashboard summary component, and the sidebar item.
- Faculty registration: the Register dialog now supports multi-select (checkboxes with search, grouped by tournament, "Select all" toggle, per-event deadline protection) and submits once via a new batch endpoint `POST /dashboard/registrations` (`event-participants.store-batch`). Successful registrations notify deans/admins per event; deadline-passed or already-registered events are skipped with an error summary while the rest still register.
- Sidebar: menu items are now whitelisted per role — faculty representatives only see Dashboard, Participation Confirmation, and Notifications (Matches/Results/Rankings and other admin pages are no longer shown to them).
- Sports: the icon field now accepts any image URL (e.g. Simple Icons, Font Awesome CDN) or a direct image upload (PNG/JPG/SVG/WebP) stored under `sport-icons/` via the public disk; replaced icons are cleaned up automatically and icons render as thumbnails in the sports list.
- Deployment: fixed Vite's production base path so JavaScript chunks, CSS, and fonts load from /portal/build/ instead of the domain root; configured an IIS/PHP upload temporary directory and stream-based settings asset storage so logo and favicon uploads work reliably on the Windows network filesystem.

- CI: aligned all runners and the container runtime with the PHP 8.4 dependency baseline, enabled CI on the default `master` branch, and retained Laravel bootstrap/runtime directories in clean checkouts so Composer, tests, coverage, and browser E2E can start reliably.
- Authentication: defaulted the session cookie path to `/` so root deployments retain login sessions; subfolder deployments continue to override `SESSION_PATH` explicitly.
- Notifications: added a super-admin `Action Required` default view, personal inbox tab, read/type/organization filters, organization and severity badges, and a direct link to filtered system activity. New pending registrations now notify same-organization super/org admins plus the participant dean without cross-tenant delivery.
- Activity logs: super-admins can audit all organizations with organization/event/date filters, while org-admins remain restricted to their own tenant.
- Fixed a pagination runtime regression when an Inertia fallback supplies a partial paginator object without a `links` array.
- Quality gates: restored repository-wide Pint compliance and added `npm run typecheck` to connected CI.
- Frontend typing: added Ziggy/Inertia shared declarations, legacy JSX compatibility declarations, corrected Inertia v2 reload/link options, normalized pagination fallbacks, and aligned nested page payload types.
- Accessibility: added accessible names to the dashboard sport, faculty, and status filters, and corrected confirmed-count text contrast so desktop/mobile axe checks pass.
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
# Unreleased

- Added public Sports programme page at `/sports-programme`, improved medal tally dashboard, and documented the route separation from authenticated `/sports`.
- Fixed public portal data compatibility so existing `sports` string payloads remain stable while the Sports page consumes `sports_catalog`.
- Refreshed registration, draw-result, and results-management workflows with clearer operational actions.
- Refreshed the event registration workspace with accurate all-registration totals and a clearer administrator quick-registration callout.
- Refactored event participant registration to support selecting and submitting multiple events in one administrator workflow.
