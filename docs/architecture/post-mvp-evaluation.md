# Post-MVP Capability Evaluation

> Status: decision matrix for post-MVP discovery. These capabilities are **not active MVP scope** unless `TODOS.md` or a new ADR explicitly moves them into the current milestone.

STMS currently ships as a Laravel + Inertia modern monolith. The following capabilities should be evaluated only after the MVP is stable, production operations have evidence, and there is a named owner for the additional operating model.

## Evaluation Matrix

| Capability | Current posture | Start trigger | MVP-safe first step | Primary risks |
| --- | --- | --- | --- | --- |
| REST API versioning | Deferred. ADR-007 accepts URL versioning via `/api/v1`, but no REST API is active. | A confirmed integration partner, mobile client, public data feed, or external dashboard requires machine access that cannot be served by existing web exports. | Define `/api/v1` read-only resources for sessions, sports, events, fixtures and public results with token auth, policies, resources and OpenAPI docs. | Tenant leakage, unsupported API clients, duplicate controller logic, long-term version maintenance. |
| Mobile/offline workflow | Deferred. Current UX is responsive web with authenticated Inertia workflows. | On-site staff need result entry, check-in or accreditation workflows in venues with unreliable connectivity. | Build a narrow offline queue for one workflow, likely match result capture, with conflict detection and replay audit. | Conflict resolution, stale rosters, device security, sync failures, support burden. |
| Realtime updates | Deferred. Current public schedule/results refresh through normal page requests. | Live scoreboard, venue display, or public event broadcast requires sub-minute updates. | Add server-side events or broadcast channels for public schedule/result updates only, backed by existing approved-result visibility rules. | Redis/broadcast runtime dependency, fan-out cost, stale cache, leaking unapproved results. |
| Accreditation | Deferred. ADR-006 accepts a dedicated optional module. Current registration/verification is not accreditation. | Event requires badges, venue/zone access, identity verification, media/VIP workflows or QR check-in. | Create a separate accreditation bounded context for session-scoped credential requests and approval states. | Scope creep, privacy obligations, badge fraud, access-control mistakes. |
| Analytics | Deferred beyond current Reports dashboard. Reports now cover operations, data quality, governance and basic comparison. | Organizers need trend, participation, performance or executive dashboards beyond operational reports. | Add curated aggregate tables or materialized summaries for participation, fixture completion and medal/result trends. | Misleading metrics, expensive queries, privacy exposure, hardcoded sport assumptions. |

## Decision Rules

1. A capability needs a named business owner, technical owner and support owner before implementation starts.
2. Every new API/realtime/offline path must keep `organization_id` scoping, policies and audit metadata.
3. Read-only public data must reuse the existing approved-result and public visibility rules.
4. Offline and realtime work requires production-ready queue/cache/broadcast evidence first.
5. Accreditation must remain optional per session and must not overload participant registration with badge-specific state.
6. Analytics must use generic sport/event/result dimensions and must not hardcode sport names or ranking formulas.

## Recommended Order

1. Stabilize production runtime evidence: queue worker, cache, backup retention, alerting and post-deploy smoke.
2. Add read-only `/api/v1` resources only if an integration partner exists.
3. Add realtime public updates only if live operations require it and Redis/broadcast readiness is proven.
4. Pilot one offline workflow after conflict handling is designed.
5. Build accreditation as its own optional module for sessions that require credentialing.
6. Expand analytics after the reporting questions are known and query budgets are measured on production-like MySQL data.

## Explicit Non-Goals For MVP

- No mobile app build.
- No public REST API release.
- No realtime broadcast layer.
- No accreditation migrations or UI.
- No AI/advanced analytics module.
- No sport-specific analytics formulas unless represented through configurable rules.

## References

- `docs/adr/ADR-006-accreditation-system.md`
- `docs/adr/ADR-007-api-versioning.md`
- `docs/architecture/accreditation.md`
- `docs/architecture/data-transfers.md`
- `docs/architecture/monitoring.md`
- `CURRENT_STATE.md`
- `TODOS.md`
