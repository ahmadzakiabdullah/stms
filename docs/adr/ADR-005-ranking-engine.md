# ADR-005: Ranking Engine

**Status:** Accepted  
**Date:** 2026-06-03  
**Deciders:** STMS Architecture Team

## Context

STMS requires a flexible ranking system that can handle different sports and tournament formats. Ranking logic varies significantly between sports:

- Some sports use medal tally (Gold > Silver > Bronze)
- Some use points-based ranking
- Some use win-loss records or goal difference
- Some require complex tiebreakers and specific rules

Hardcoding ranking logic is not sustainable and violates the project's core principles.

## Decision

We will create a dedicated **Ranking Engine** that is configurable and extensible.

### Key Design Decisions:
- Ranking logic will be driven by configuration and rules stored in the database where possible.
- The engine will support multiple ranking strategies (medal count, points, win rate, etc.).
- Ranking calculations will be handled through dedicated Service Classes and Action Classes.
- Results from matches will feed into the Ranking Engine.
- The engine must support both automatic and manual ranking adjustments.

## Consequences

### Positive
- Highly flexible across different sports and tournament types.
- Easy to add or modify ranking rules without changing core code.
- Supports both simple and complex ranking requirements.
- Aligns with the principle of configuration over hardcoding.

### Negative
- Requires more upfront design and abstraction.
- Testing becomes more important due to variability in ranking rules.
- May require background jobs for large-scale ranking calculations.

### Neutral
- Adds a layer of abstraction in the architecture.

## Impact

- Directly affects `Result`, `Match`, and `Event` modules.
- Influences the design of the reporting and analytics modules in later phases.
- Requires careful consideration of performance when calculating rankings for large events.

## Alternatives Considered

| Alternative | Decision | Reason |
|-------------|----------|--------|
| Hardcode ranking formulas | Rejected | Violates core principles |
| Simple points-based ranking only | Rejected | Not flexible enough |
| Dedicated Ranking Engine | **Accepted** | Best approach for long-term flexibility and maintainability |

## References
- CLAUDE.md - Development Principles
- ADR-004: Sports Engine

## Implementation Status
Implemented as of June 2026. `RankingService` supports Points, Win Rate, and Medal Tally strategies. See `CURRENT_STATE.md` for details.
