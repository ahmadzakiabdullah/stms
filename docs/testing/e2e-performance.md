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

Satu connected CI run sejarah mengesahkan enam journey pada commit `ae42a50`. Bukti itu tidak meliputi HEAD/working tree 17 Ogos; draw, import, result-entry, export dan route public semasa masih memerlukan rerun dengan test data terasing.

Suite kini mempunyai empat scenario merentas desktop/mobile (8 journeys): core super-admin, faculty/dean, login/dashboard axe, dan public Home/Contact keyboard+axe. Run terasing pada working tree 17 Ogos 2026 lulus **8/8** dalam 2.4 minit menggunakan SQLite. Connected CI dan post-deploy run masih diperlukan.

Pada workstation yang `.env` menetapkan production `ASSET_URL`, Playwright memaksa test asset URL mengikuti test `APP_URL`; ini mengelakkan HTML local mengambil JavaScript production.

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

Scenario perlu membekalkan token untuk `/health` atau menggunakan endpoint staging yang sesuai; production menyembunyikan `/health` sebagai 404 tanpa token. Dengan credentials bukan production yang terkawal, ia juga boleh sign in dan exercise `/dashboard`:

```bash
k6 run -e BASE_URL=https://staging.example.test -e AUTH_LOGINS=loadtest1,loadtest2 -e AUTH_PASSWORDS=secret1,secret2 -e VUS=10 -e DURATION=30s -e K6_SUMMARY_PATH=test-results/k6-summary.json tests/performance/smoke.js
```

Use dedicated least-privilege staging accounts and inject passwords through the CI secret store. Each VU owns its own cookie jar and is mapped to a supplied account; provide at least as many accounts as VUs to avoid shared sessions and login throttling. Never commit credentials or point mutation/load tests at production without explicit approval.
Publish `test-results/k6-summary.json` as the release artifact after every approved staging run.

## Evidence Recorded on 5 August 2026

- Connected-CI Playwright/axe: all six journeys passed on commit `ae42a50`.
- Sanitized MySQL restore: 3.47 MB AES-256 archive restored in 2.977 seconds with healthy checks and matching counts.
- Corrected authenticated k6 behavior: 190/190 checks and 0% HTTP errors; p95 4.24 seconds failed the 750 ms target on the single-process development server.
- The CSRF/dynamic-cookie correction is committed in the working tree. A multi-worker staging load run is still required before the latency gate can be marked complete.

See `2026-08-05-operational-drill.md` for the complete record.
