# ADR-001: Session-Based Architecture

**Status:** Accepted  
**Date:** 2026-06-03  
**Deciders:** STMS Architecture Team

## Context

The STMS must support multiple independent sporting events over time (e.g., Olympics 2024, Olympics 2028, SUKMA XXI, SUKIPT 2026). These events are not necessarily annual and can overlap. A simple "year-based" or "tournament-based" model is insufficient.

The system requires a clear, first-class concept that groups related tournaments, sports, participants, and results.

## Decision

We will introduce **Session** as a first-class entity in the system hierarchy:


- A `Session` represents a major sporting event cycle (e.g., "SUKMA 2026", "Olympics 2028").
- All tournaments, sports, participants, and results must belong to a Session.
- Sessions can be created, archived, and managed independently.

## Consequences

### Positive
- Clear separation between different sporting events.
- Easier data isolation and reporting per event.
- Supports long-term data retention across multiple editions.
- Aligns with real-world sports governance.

### Negative
- Adds one additional layer in the data model.
- Requires careful scoping of all queries and relationships to the current Session.

### Neutral
- Increases complexity slightly in the early phases but provides strong architectural clarity.

## Impact

- Affects almost all core modules (Tournament, Sport, Participant, Result, Ranking).
- Requires all queries and relationships to be scoped to Session.
- Influences the design of the multi-tenant strategy.

## Alternatives Considered

| Alternative | Decision | Reason |
|-------------|----------|--------|
| Using `Tournament` as the top-level entity | Rejected | Too narrow |
| Using `Year` or `Edition` as the grouping key | Rejected | Not flexible enough |
| Flat structure without Session | Rejected | Will cause major data isolation issues later |
| Session as first-class entity | **Accepted** | Best architectural fit |

## References
- CLAUDE.md - Core Architecture section
- ROADMAP.md - Phase 1 & 2

## Implementation Status
Implemented as of June 2026. See `CURRENT_STATE.md` for details.
