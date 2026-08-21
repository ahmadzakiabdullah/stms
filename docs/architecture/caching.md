# Caching Strategy

## Current State

Cache driver is environment-controlled. The audited workspace runs as `production` with `CACHE_STORE=database`.

Implemented caches include:

- role/permission cache from Spatie;
- dashboard payloads keyed by user/tenant/filter;
- public portal payload keyed by public session and limit for two minutes.

`PublicPortalService` invalidation is called by relevant event/fixture/result/participant/setting mutations and keeps organization boundaries.

## Production Target

Production and multi-worker staging must use Redis. Database cache adds load to the primary database and does not satisfy the current production baseline.

Public portal payloads use Laravel stale-while-revalidate caching: values remain fresh for two minutes and may be served stale for up to ten minutes while a deferred refresh rebuilds the expensive dashboard/directory payload. This keeps public LCP independent of a cache-expiry query spike; it does not replace Redis or freshness monitoring.

`.env.production.example` sets Redis, but an example file is not runtime evidence. `PRODUCTION_CONFIG_ENFORCE` should reject non-Redis production cache/session/queue values.

## Rules

- Every key containing tenant data must include organization/session context.
- Do not cache partial fallback/error payloads.
- Every mutation affecting cached output must invalidate only the affected tenant.
- Add query/cache budgets for dashboard, registration, results, reports and public portal.
