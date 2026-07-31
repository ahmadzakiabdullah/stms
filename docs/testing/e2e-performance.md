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

Local execution on 31 July 2026 prepared and seeded the isolated E2E database successfully. All six desktop/mobile Chromium journeys passed, including the login/dashboard axe checks. Connected CI remains the repeatable enforcement environment.

## Coverage Policy

PCOV generates a Clover artifact in CI. Record the first successful percentage in `CURRENT_STATE.md`, then set a minimum slightly below that value. Raise it incrementally when new tests improve coverage; do not introduce an arbitrary 80% gate without evidence.

## Performance Policy

- PHP feature tests enforce query-count regression budgets.
- k6 owns latency/error thresholds for deployed environments.
- The bundle-budget script prevents oversized individual JS/CSS assets.
- Performance limits may change only with measured evidence and a documented trade-off.

## TypeScript Status

`npm run build` succeeds, but `npx tsc --noEmit` is not yet a passing gate. Current debt includes declarations for legacy JSX UI components, Ziggy's global `route`, and page/model/form type mismatches. Do not equate Vite transpilation success with strict TypeScript correctness.
