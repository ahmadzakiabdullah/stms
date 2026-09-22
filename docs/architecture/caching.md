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

## Invalidation Matrix

| Cached output | Key scope | Invalidation trigger | Scope of invalidation |
| --- | --- | --- | --- |
| Dashboard payload | User, organization and filter combination | Dashboard-owned data mutation or explicit dashboard refresh | Affected user/organization dashboard keys |
| Public homepage, directory and schedule payload | Public session and limit | Event, participant, match, result, sport document or public setting mutation | The affected organization’s active public session |
| Public athlete directory | Public session | Confirmed registration, squad member or participant mutation | The affected public session |
| Role and permission metadata | Spatie permission cache | Role/permission assignment or definition change | Permission cache for the affected application scope |

Mutation services call `PublicPortalService::forgetForOrganization()` or the explicit session-scoped `forget()` path after a successful write. Tests must verify that the changed tenant is cleared while another tenant’s keys remain intact. Cache invalidation is application behavior; Redis activation and freshness alerting remain production release requirements.
