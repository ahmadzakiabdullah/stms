# ADR-010: Write-side concurrency safety

**Status:** Accepted  
**Date:** 2026-09-21  
**Deciders:** STMS Architecture Team

## Context

Draw generation, result entry, participant registration and match scheduling
can receive duplicate submissions or overlapping requests. Validation performed
only before a transaction leaves a race window in which two workers can both
observe the same stale state.

## Decision

Keep the existing service/action boundaries and make the write transaction the
authoritative concurrency boundary:

- lock the event row for draw/reset/pool mutations;
- make fixture generation idempotent after fixtures already exist;
- lock the match row for result creation and the result row for correction and
  status transitions;
- lock the existing event-participant row and translate the unique-index race
  into the normal duplicate-registration validation response;
- serialize match writes on the organization row, then re-run schedule conflict
  validation inside the same transaction before insert/update.

Database unique constraints remain the final defense. Deterministic regression
tests cover duplicate requests; a multi-worker staging run is still required
for runtime evidence.

## Consequences

Writes for the same event or organization are intentionally serialized at their
critical sections. This slightly reduces write parallelism, but prevents silent
duplicate results, registrations, fixtures or schedule conflicts and keeps
state transitions auditable.
