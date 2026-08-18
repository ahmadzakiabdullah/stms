# ADR-002: Multi-Tenant Design

**Status:** Accepted  
**Date:** 2026-06-03  
**Deciders:** STMS Architecture Team

## Context

STMS is designed to support multiple organizations (schools, universities, state sports councils, national sports associations, and private tournament organizers) using the same codebase. Each organization must have its own data isolation while sharing the same application logic.

## Decision

We will implement **column-based multi-tenancy** using an `organization_id` foreign key on all relevant tables.

### Key Points:
- Every major entity (`User`, `Session`, `Tournament`, `Sport`, `Participant`, etc.) will have an `organization_id`.
- Data isolation will be enforced at the application layer using Laravel Global Scopes or query scopes.
- We will **not** use separate databases or schemas per organization in the initial version.

## Consequences

### Positive
- Simple to implement and maintain.
- Lower infrastructure and operational cost.
- Easier to perform cross-organization reporting when needed.
- Suitable for both small and large organizations.

### Negative
- Requires strict discipline to always scope queries by `organization_id`.
- Higher risk of data leakage if scopes are not applied correctly.
- Authorization logic becomes slightly more complex.

### Neutral
- Performance impact is minimal when implemented properly.

## Impact

- Affects almost all models and queries in the system.
- Requires consistent application of query scopes across the codebase.
- Influences the design of User Management and Role & Permission modules.

## Alternatives Considered

| Alternative | Decision | Reason |
|-------------|----------|--------|
| Separate database per tenant | Rejected | High complexity and maintenance cost |
| Schema-based multi-tenancy | Rejected | Poor support in MySQL |
| Row-level security at database level | Rejected | Too complex for current requirements |
| Column-based multi-tenancy (`organization_id`) | **Accepted** | Best balance between simplicity and control |

## References
- CLAUDE.md - Multi-Tenant section
- ADR-001: Session-Based Architecture
- ADR-003: Organization Structure

## Implementation Status (audited 2026-08-17)

Implemented for domain models via `TenantContext`, `BelongsToOrganization`, policies and tenant-aware services. `Organization` is the unscoped tenant root and `User` uses explicit handling. The read/index authorization and guest favicon gaps found by the 17 August audit were remediated and covered by direct-URL plus HTTP/Inertia payload tests. Production runtime evidence remains a separate release concern; see `CURRENT_STATE.md`.
