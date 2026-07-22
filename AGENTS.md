# AGENTS.md

> Instructions for AI coding agents and automated tools working on the STMS project.

## Project Context

STMS (Sports Tournament Management System) is a large-scale, multi-tenant sports tournament management platform. The system must remain generic, configurable, and scalable to support everything from school-level tournaments to international multi-sport events.

## Mandatory Reading Order

Before performing any task, **always** read the following files in order:

1. `CLAUDE.md`
2. `AGENTS.md` (this file)
3. `CURRENT_STATE.md` (root) — honest snapshot of what is actually built vs the target
4. `ROADMAP.md`
5. `TODOS.md`
6. `docs/adr/*`
7. `docs/architecture/*`
8. `docs/database/*`

Documentation takes precedence over assumptions.

## Core Rules for AI Agents

### 1. Focus on Current Milestone Only
- Always follow the **Current Focus** section in `TODOS.md`.
- Do **not** work on future phases (Accreditation, Live Scoring, Mobile App, AI, etc.) unless explicitly instructed.
- Milestone 1 must be completed before moving to Milestone 2.

### 2. Architecture Compliance
- Follow the core hierarchy: `Organization → Session → Tournament → Sport → Event → Match → Result`.
- Every tenant-aware model **must** have `organization_id`.
- Use **Service Layer + Action Classes** pattern. Avoid fat controllers.
- Use **UUID** as primary keys.
- Apply soft deletes where appropriate.

### 3. Technology Constraints
- **Frontend**: React + Inertia.js + TypeScript + shadcn/ui only.
- **No FilamentPHP**.
- **No** other UI frameworks (Material UI, Ant Design, Chakra, etc.).
- Do **not** hardcode sport names, rules, or ranking formulas.

### 4. Multi-Tenancy
- Always scope queries by `organization_id`.
- Use Global Scopes or traits to enforce tenant isolation.
- Never allow cross-organization data leakage.

### 5. Code Quality
- Follow PSR-12 and Laravel best practices.
- Write Feature Tests and Unit Tests for every feature.
- Use Form Requests for validation.
- Use Policies for authorization.

### 6. Documentation
- When architecture changes, update `docs/adr/` and `docs/architecture/`.
- When functionality changes, update `TODOS.md` and `CHANGELOG.md`.
- Never leave documentation outdated.

## Prohibited Actions

- Do not introduce new UI libraries.
- Do not hardcode business logic related to specific sports or tournaments.
- Do not skip the planning phase.
- Do not work on features outside the current milestone without approval.
- Do not modify core Laravel files unless absolutely necessary.

## Preferred Patterns

- Service Classes for business logic
- Action Classes for single-responsibility operations
- Repository Pattern (when needed)
- Laravel Policies + Gates for authorization
- React Hook Form + Zod for forms
- TanStack Table for data tables

## Communication

When making significant architectural decisions or encountering blockers, document them clearly and update the relevant ADR or `TODOS.md`.

## Current Focus

Refer to `TODOS.md` → **Current Focus (MVP)** section for the latest development priority.

---

**Note**: This file should be read together with `CLAUDE.md`.
