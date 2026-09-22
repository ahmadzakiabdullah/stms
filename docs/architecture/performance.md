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
