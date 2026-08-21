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

Suite kini mempunyai empat scenario merentas desktop/mobile (8 journeys): core super-admin, faculty/dean, login/dashboard axe, dan public Home/Contact keyboard+axe. Run terasing pada 18 Ogos 2026 lulus **8/8** dalam 2.3 minit menggunakan SQLite; connected CI #112 turut lulus pada `4b04c46`. Authenticated post-deploy run terhadap deployment sebenar masih diperlukan.

Pada workstation yang `.env` menetapkan production `ASSET_URL`, Playwright memaksa test asset URL mengikuti test `APP_URL`; ini mengelakkan HTML local mengambil JavaScript production.

## Coverage Policy

PCOV generates a Clover artifact in CI. Record the first successful percentage in `CURRENT_STATE.md`, then set a minimum slightly below that value. Raise it incrementally when new tests improve coverage; do not introduce an arbitrary 80% gate without evidence.

## Performance Policy

- PHP feature tests enforce query-count regression budgets.
- k6 owns latency/error thresholds for deployed environments.
- The bundle-budget script prevents oversized individual JS/CSS assets.
- Performance limits may change only with measured evidence and a documented trade-off.

## Public Lighthouse Baseline — 21 August 2026

Measured against `https://saf.utem.edu.my/` with Lighthouse 13.4.1 and the Playwright Chromium executable. These are a production baseline, not an approval threshold:

| Page | Performance | FCP | LCP | TBT | CLS |
| --- | ---: | ---: | ---: | ---: | ---: |
| `/` | 64 | 3.7 s | 5.6 s | 20 ms | 0.004 |
| `/schedule` | 71 | 3.7 s | 5.4 s | 10 ms | 0.068 |

The main follow-up is LCP optimization on both pages. Repeat the measurement from a consistent CI/monitoring region before changing the target or declaring the public performance work complete.

## LCP follow-up — 21 August 2026

The homepage now skips initial layout/paint work for below-the-fold sections with `content-visibility: auto` and defers participant logo decoding/loading. This keeps the hero path smaller without removing content or changing the public payload. Re-run Lighthouse after deployment; the change is an optimization candidate, not evidence of an improved LCP until the production measurement confirms it.

## TypeScript Status

`npm run typecheck` runs `tsc --noEmit` and is enforced before the production build in CI. Shared declarations cover Ziggy, Inertia page props, and the remaining legacy JSX compatibility boundary; page payload and pagination types are checked in TypeScript.

## Authenticated k6 Scenario

Scenario menerima `HEALTH_TOKEN` dan menghantarnya sebagai `X-Health-Token`; production menyembunyikan `/health` sebagai 404 tanpa token. Dengan credentials bukan production yang terkawal, ia juga boleh sign in dan exercise `/dashboard`:

```bash
k6 run -e BASE_URL=https://staging.example.test -e HEALTH_TOKEN=secret-health-token -e AUTH_LOGINS=loadtest1,loadtest2 -e AUTH_PASSWORDS=secret1,secret2 -e VUS=10 -e DURATION=30s -e K6_SUMMARY_PATH=test-results/k6-summary.json tests/performance/smoke.js
```

Use dedicated least-privilege staging accounts and inject passwords through the CI secret store. Each VU owns its own cookie jar and is mapped to a supplied account; provide at least as many accounts as VUs to avoid shared sessions and login throttling. Never commit credentials or point mutation/load tests at production without explicit approval.
Publish `test-results/k6-summary.json` as the release artifact after every approved staging run.

The scenario retains each VU's cookie jar between iterations and requires `checks: rate==1`; HTTP-level thresholds alone are insufficient because a redirect or validation response can still be a successful HTTP transaction while failing the intended application journey.

## Evidence Recorded on 5 August 2026

- Connected-CI Playwright/axe: all six journeys passed on commit `ae42a50`.
- Sanitized MySQL restore: 3.47 MB AES-256 archive restored in 2.977 seconds with healthy checks and matching counts.
- Corrected authenticated k6 behavior: 190/190 checks and 0% HTTP errors; p95 4.24 seconds failed the 750 ms target on the single-process development server.
- The CSRF/dynamic-cookie correction was committed, but at that date a multi-worker staging load run was still required; the 18 August evidence below closes that historical gap.

## Evidence Recorded on 18 August 2026

- Isolated Nginx/PHP-FPM multi-worker staging with MySQL 8 and Redis 7.
- 10 dedicated staging accounts, 10 VUs for 30 seconds.
- 1,150/1,150 application checks, 0% HTTP failures and p95 81.543 ms.
- See [release drill evidence](2026-08-18-release-drill.md).

See `2026-08-05-operational-drill.md` for the complete record.
