# E2E, Accessibility, Coverage, and Performance

## Local E2E Preparation

Use a dedicated database—never a development or production database—then run `migrate:fresh --seed`, build assets, install Chromium, and start Playwright:

```bash
npx playwright install chromium
npm run build
npm run test:e2e
```

The CI job uses `database/e2e.sqlite`, enables demo seeding only in the testing environment, and runs desktop plus mobile Chromium projects.

## Covered Journeys

- Super-admin login and access to dashboard, participants, events, matches, results, rankings, roles, and reports.
- Faculty representative login and faculty dashboard.
- Dean login and verification dashboard.
- Automated axe checks on login and the authenticated dashboard.

Mutation-heavy draw, import, result-entry, and export content assertions should be added after the first connected CI run confirms stable accessible selectors.

The six committed desktop/mobile Chromium journeys cover the role-aware dashboard shell, faculty workspace, dean verification and core super-admin pages. The last recorded local browser run passed on 2 August 2026; connected CI remains the authoritative repeatable browser/accessibility environment. The 4 August dashboard/sidebar refactor passed PHPUnit, Pint, TypeScript and production build checks, while direct production browser inspection was unavailable because the local Windows browser sandbox could not initialize.

## Coverage Policy

PCOV generates a Clover artifact in CI. Record the first successful percentage in `CURRENT_STATE.md`, then set a minimum slightly below that value. Raise it incrementally when new tests improve coverage; do not introduce an arbitrary 80% gate without evidence.

## Performance Policy

- PHP feature tests enforce query-count regression budgets.
- k6 owns latency/error thresholds for deployed environments.
- The bundle-budget script prevents oversized individual JS/CSS assets.
- Performance limits may change only with measured evidence and a documented trade-off.

## TypeScript Status

`npm run typecheck` runs `tsc --noEmit` and is enforced before the production build in CI. Shared declarations cover Ziggy, Inertia page props, and the remaining legacy JSX compatibility boundary; page payload and pagination types are checked in TypeScript.

## Authenticated k6 Scenario

The default scenario checks `/health`. Supplying controlled, non-production load-test credentials also signs in and exercises `/dashboard`:

```bash
k6 run -e BASE_URL=https://staging.example.test -e AUTH_EMAIL=loadtest@example.test -e AUTH_PASSWORD=secret -e VUS=10 -e DURATION=30s tests/performance/smoke.js
```

Use a dedicated least-privilege staging account and inject its password through the CI secret store. Never commit credentials or point mutation/load tests at production without explicit approval.
