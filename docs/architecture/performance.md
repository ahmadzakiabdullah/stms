# Performance

## Repository Baselines

- Dashboard query-budget test: maximum 42 queries for its representative fixture.
- Public homepage and schedule query-budget tests: maximum 35 queries each for a cold-cache representative fixture.
- Events index query-budget test: maximum 45 queries for a representative authenticated admin fixture.
- Results index, Registrations index and Reports index query-budget tests: maximum 70, 35 and 35 queries respectively for representative authenticated fixtures.
- k6 target: <1% failures and p95 <750 ms for approved scenarios.
- Bundle budget: each JS chunk ≤400 KB, CSS asset ≤120 KB uncompressed.

On 15 September 2026, Vite build and bundle budget passed. The CSS budget was raised from 100 KB to 120 KB after the compiled `app-*.css` reached ~102 KB following the addition of intentional self-hosted fonts (`Geist`, `Manrope`, `Noto Sans`, `Barlow Condensed`) and continued admin/public feature growth. Largest JS chunk remained about 357 KB.

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
