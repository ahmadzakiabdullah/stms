# Documentation Index

This directory contains architecture, operations, security, testing, and audit references for STMS.

## Directory Map

- `adr/`: Architecture Decision Records.
- `architecture/`: System architecture, domain model, frontend/backend, runtime concerns.
- `database/`: ERD, schema, and migration-oriented references.
- `deployment/`: Deployment and recovery runbooks.
- `design-system/`: UI system guidance and usage rules.
- `security/`: Security notes, controls, and hardening references.
- `testing/`: Testing guidance and test strategy references.
- `audits/`: Point-in-time assessment reports.
- `api/`: Deferred API planning notes (not active endpoint docs).

## Root-Level Files

- `FINDING.md`: Consolidated findings summary.
- `IMPLEMENTATION_STATUS.md`: Milestone progress snapshot.
- `PLAN.md`: Working plan and execution notes.
- `MASTER_PROMPT.md`: Prompt/governance scaffold used for analysis tasks.
- `testing.md`: Top-level testing notes.

## Maintenance Rules

- Keep architecture and implementation status aligned when behavior changes.
- Mark deferred functionality explicitly to avoid false readiness signals.
- Prefer adding a short update note instead of rewriting historical audit files.
- Keep tenant and hierarchy terms consistent with project canon:
  `Organization -> Session -> Tournament -> Sport -> Event -> Match -> Result`.