# ADR-007: API Versioning

**Status:** Accepted  
**Date:** 2026-06-03  
**Deciders:** STMS Architecture Team

## Context

STMS will expose APIs for integration with external systems such as mobile applications, third-party platforms, government systems, and federation dashboards. As the system evolves, API changes are inevitable. Without proper versioning, breaking changes can negatively impact existing integrations.

## Decision

We will implement **URL-based API versioning** using the following format:


### Guidelines:
- All API routes will be prefixed with `/api/v{version}`.
- The first production release will use `v1`.
- Breaking changes will trigger a new version (e.g., `v2`).
- Non-breaking changes (additions) can remain in the same version.
- Old versions will be deprecated gradually, not removed immediately.

## Consequences

### Positive
- Clear and explicit versioning.
- Easy to maintain backward compatibility.
- Simple to document and understand.
- Aligns with common industry best practices.

### Negative
- Requires maintaining multiple versions of controllers and resources over time.
- Slightly more route configuration required.

### Neutral
- Minor increase in initial setup effort.

## Impact

- Affects all future API development.
- Requires consistent use of API Resources and versioning in controllers.
- Influences API documentation strategy (OpenAPI/Swagger).

## Alternatives Considered

| Alternative | Decision | Reason |
|-------------|----------|--------|
| No versioning | Rejected | High risk of breaking integrations |
| Header-based versioning | Rejected | Less visible and harder to document |
| Query parameter versioning | Rejected | Not as clean and standard as URL-based |
| URL-based versioning (`/api/v1`) | **Accepted** | Best balance of clarity and maintainability |

## References
- CLAUDE.md - API Standards

## Implementation Status
Deferred — not yet implemented. The system currently uses Inertia.js (web-only) with no REST API. This decision applies to the future REST API phase. See `CURRENT_STATE.md` for details.
