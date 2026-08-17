# Performance

## Repository Baselines

- Dashboard query-budget test: maximum 40 queries for its representative fixture.
- k6 target: <1% failures and p95 <750 ms for approved scenarios.
- Bundle budget: each JS chunk ≤400 KB, CSS asset ≤100 KB uncompressed.

On 17 Ogos 2026, Vite build and bundle budget passed. Largest JS chunk was about 351 KB and CSS about 92 KB.

## Production Smoke Observation

Eight sequential unauthenticated GETs to `https://saf.utem.edu.my/` produced roughly:

- median 237 ms;
- average 289 ms;
- max 683 ms.

This is a lightweight observation, not a load test. The earlier single-process authenticated k6 run missed the latency target; multi-worker staging evidence remains required.

## Current Characteristics

- Public portal queries upcoming/completed fixtures separately, limits result sets and caches for two minutes.
- Public initial HTML is about 68 KB and includes Ziggy routes plus many Vite prefetch hints.
- Audited runtime uses database cache/queue instead of Redis.
- Query assembly remains substantial in Dashboard/EventParticipant/Draw controllers.

## Priorities

1. Move production cache/queue/session to Redis.
2. Run authenticated multi-worker k6 with representative data.
3. Add query budgets for public portal, results, registration and reports.
4. Review Ziggy/prefetch payload and remove redundant external font requests.
5. Monitor slow queries and index only from measured query plans.
