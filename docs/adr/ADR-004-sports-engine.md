# ADR-004: Sports Engine

**Status:** Accepted  
**Date:** 2026-06-03  
**Deciders:** STMS Architecture Team

## Context

STMS must support a wide variety of sports across different tournaments and sessions. Each sport may have different structures such as:

- Individual sports (e.g., Athletics, Swimming, Badminton)
- Team sports (e.g., Football, Basketball, Hockey)
- Different categories, age groups, and weight classes
- Different scoring and ranking systems

The system must remain generic and must **not** hardcode any specific sport names, categories, or rules.

## Decision

We will build a **Sports Engine** as a core configurable module with the following main entities:

- `Sport`
- `SportCategory`
- `Event`

All sports, categories, and events will be created through the admin interface and stored in the database. The Sports Engine will be designed to be **highly configurable** rather than hardcoded.

## Consequences

### Positive
- Highly flexible and reusable across different sports and tournament types.
- Supports both individual and team sports.
- Easy to add new sports without requiring code changes.
- Aligns with the project principle of "configuration over hardcoding".

### Negative
- Requires more initial setup compared to hardcoding sports.
- Slightly more complex data model.

### Neutral
- Performance impact is negligible.

## Impact

- Affects almost all downstream modules (Tournament, Match, Result, Ranking).
- Becomes the foundation for Event and Match scheduling.
- Requires careful design of relationships between Sport, Category, and Event.

## Alternatives Considered

| Alternative | Decision | Reason |
|-------------|----------|--------|
| Hardcode sports in code or config files | Rejected | Violates project principles |
| Simple `sports` table only | Rejected | Not flexible enough for categories and events |
| Dedicated Sports Engine (configurable) | **Accepted** | Best long-term flexibility and maintainability |

## References
- CLAUDE.md - Development Principles
- ADR-001: Session-Based Architecture
- ADR-005: Ranking Engine

## Implementation Status
Implemented as of June 2026 with `Sport`, `SportCategory`, and `Event`. There is no `EventCategory` model; events reference `sport_category_id`. See `CURRENT_STATE.md` for details.
