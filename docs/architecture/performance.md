# Performance

## Repository Baselines

- Dashboard query-budget test: maximum 56 queries for its representative fixture (raised from 42 on 12 November 2026 after trend snapshots, session/tournament filters and roster progress were added; measured 54, no N+1).
- Public homepage and schedule query-budget tests: maximum 35 queries each for a cold-cache representative fixture.
- Events index query-budget test: maximum 45 queries for a representative authenticated admin fixture.
- Results index, Registrations index and Reports index query-budget tests: maximum 70, 35 and 48 queries respectively for representative authenticated fixtures (Reports raised from 35 on 12 November 2026 after the operations monitor, data-quality and data-transfer panels were added; measured 45, no N+1).
- k6 target: <1% failures and p95 <750 ms for approved scenarios.
- Bundle budget: each JS chunk ≤400 KB, CSS asset ≤120 KB uncompressed.

On 15 September 2026, Vite build and bundle budget passed. The CSS budget was raised from 100 KB to 120 KB after the compiled `app-*.css` reached ~102 KB following the addition of intentional self-hosted fonts (`Geist`, `Manrope`, `Noto Sans`, `Barlow Condensed`) and continued admin/public feature growth. Largest JS chunk then remained about 357 KB.

Vite 8 (Rolldown) now splits the shared runtime via `build.rolldownOptions.output.codeSplitting.groups` in `vite.config.js` into `react-vendor` (~140 KB), `inertia-vendor` (~239 KB) and `i18n` (~157 KB) chunks, keeping every JS chunk under the 400 KB budget (CSS ≤120 KB). Re-measure with `npm run build && npm run build:budget` on the release candidate.

## Production Smoke Observation

Eight sequential unauthenticated GETs to `https://saf.utem.edu.my/` produced roughly:

- median 237 ms;
- average 289 ms;
- max 683 ms.

This is a lightweight observation, not a load test. The earlier single-process authenticated k6 run missed the latency target; multi-worker staging evidence remains required.

## Current Characteristics

- Public portal queries upcoming/completed fixtures separately, limits result sets and caches for two minutes.
- Guest Ziggy routes are filtered and global initial Vite prefetch has been removed.
- Audited runtime uses database cache/queue instead of Redis.
- Dashboard, EventParticipant and Event index query assembly now lives in dedicated services; DrawController remains the next large orchestration candidate only when release work permits.

## Priorities

1. Move production cache/queue/session to Redis.
2. Run authenticated multi-worker k6 with representative data.
3. Keep the results, registration and reports budgets aligned with measured representative fixtures.
4. Monitor slow queries and add indexes only from measured query plans.

## MySQL Query-Plan Evidence

Use the read-only Artisan profiler on staging or production-like MySQL data before adding indexes:

```bash
php artisan stms:query-profile --organization=utem --json > storage/app/query-profile-$(date +%F).json
```

The command profiles representative query-budget paths:

- public schedule upcoming fixtures;
- authenticated results index;
- event registrations index;
- Reports export-governance summary.

Review `rows`, `type`, `possible_keys`, selected `key` and `Extra` for each plan. Add an index only when the measured plan shows a tenant-scoped high-cardinality scan or filesort that affects a documented query budget. SQLite/local runs may report `skipped`; that is expected because the required evidence is MySQL-specific.

## MySQL Evidence Log

On 23 September 2026, `php artisan stms:query-profile --organization=utem --json` ran successfully against MySQL for organization `utem`.

- Public schedule upcoming fixtures used `matches_org_event_stage_status_idx` with `type=ref`, `rows=1`; event join used `eq_ref`.
- Results index used `results_organization_id_index` with `type=ref`, `rows=1`; match/event joins used `eq_ref`.
- Event registrations index used `event_participants_organization_id_index` with `type=ref`, `rows=1`; participant/event joins used `eq_ref`.
- Reports export governance used `data_transfers_org_idempotency_unique` with `type=ref`, `rows=1`.

Several plans include `Using filesort`, but the measured row counts are currently too low to justify a new index. Re-profile after production data grows or if query-budget tests/monitoring show latency regression.
