# ADR-006: Accreditation System

**Status:** Accepted  
**Date:** 2026-06-03  
**Deciders:** STMS Architecture Team

## Context

Large-scale sporting events such as SUKMA, SUKIPT, SEA Games, and Olympics require a formal **accreditation** process. This process typically involves:

- Application submission by athletes, officials, media, and support staff
- Document verification
- Multi-level approval workflow
- Issuance of physical or digital accreditation passes
- Role-based access during the event

The accreditation requirements vary significantly between different organizing bodies and event scales.

## Decision

We will build a dedicated **Accreditation System** module with support for configurable workflows and state management.

### Key Design Decisions:
- Accreditation records will be linked to entities such as `Participant`, `Official`, and `Media`.
- The system will support multiple accreditation types (e.g., Athlete, Official, Media, Technical, VIP).
- A state machine will manage statuses such as `Pending`, `Under Review`, `Approved`, `Rejected`, and `Revoked`.
- All accreditation data will be scoped to a `Session`.
- The module will be designed to be **optional** — small tournaments can disable it.

## Consequences

### Positive
- Supports complex real-world accreditation requirements for large events.
- Provides clear audit trail for all approval decisions.
- Flexible enough to adapt to different organizational policies.
- Can be disabled or simplified for smaller tournaments.

### Negative
- Adds significant complexity to the system.
- Requires careful workflow design to prevent the module from becoming overly rigid.
- Increases testing and maintenance effort.

### Neutral
- Not required for small tournaments, but critical for enterprise-grade events.

## Impact

- Affects `Participant`, `Official`, and related modules.
- Requires integration with User roles and permissions.
- Will influence future mobile app features (digital accreditation passes).

## Alternatives Considered

| Alternative | Decision | Reason |
|-------------|----------|--------|
| Simple status field on Participant | Rejected | Too limited for real accreditation workflows |
| External accreditation tool | Rejected | Not aligned with all-in-one platform goal |
| Dedicated Accreditation Module with Workflow | **Accepted** | Required for enterprise-grade events |

## References
- CLAUDE.md - Core Architecture
- ADR-001: Session-Based Architecture
- ADR-008: Permission Model

## Implementation Status
Deferred — not yet implemented. This feature is planned for a future milestone after MVP is stable. See `CURRENT_STATE.md` for details.
