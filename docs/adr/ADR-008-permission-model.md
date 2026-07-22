# ADR-008: Permission Model

**Status:** Accepted  
**Date:** 2026-06-03  
**Deciders:** STMS Architecture Team

## Context

STMS requires fine-grained access control because users within the same organization may have different responsibilities (e.g., Tournament Manager, Sport Coordinator, Result Entry Staff, Accreditation Officer, etc.).

A simple role-based system is insufficient. The system needs the ability to assign specific permissions to roles and, in some cases, directly to individual users.

## Decision

We will use the **Spatie Laravel Permission** package to implement Role-Based Access Control (RBAC) with fine-grained permissions.

### Implementation Approach:
- Use `spatie/laravel-permission` package.
- Define roles such as `super-admin`, `org-admin`, `tournament-manager`, `sport-coordinator`, `staff`, and `viewer`.
- Define granular permissions (e.g., `create.tournament`, `edit.result`, `approve.accreditation`, `view.reports`).
- Assign permissions to roles, and roles to users.
- Use Laravel Policies and Gates for authorization checks.
- Support both role-based and direct user permissions when needed.

## Consequences

### Positive
- Highly flexible and scalable permission system.
- Well-maintained and widely used package with good Laravel integration.
- Supports both role-based and direct user-level permissions.
- Provides a clear and auditable permission structure.

### Negative
- Adds a layer of complexity in permission management.
- Requires careful planning and naming conventions for permissions to avoid inconsistency.
- Increases the number of database records (roles, permissions, and assignments).

### Neutral
- Performance impact is minimal when permissions are properly cached.

## Impact

- Affects all modules that require authorization (User, Tournament, Result, Accreditation, Reports, etc.).
- Influences the design of the User Management module.
- Becomes the foundation for future features such as organization-level custom roles.

## Alternatives Considered

| Alternative | Decision | Reason |
|-------------|----------|--------|
| Built-in Laravel Gates and Policies only | Rejected | Not sufficient for complex role management |
| Custom permission system | Rejected | Reinventing the wheel and high maintenance |
| Spatie Laravel Permission | **Accepted** | Most mature, flexible, and suitable solution |

## References
- CLAUDE.md - Backend Standards
- ADR-002: Multi-Tenant Design
- ADR-003: Organization Structure

## Implementation Status
Implemented as of June 2026. Spatie Laravel Permission installed with roles (super-admin, org-admin, staff, sport-coordinator, tournament-manager), 30+ permissions, and 12 Policies. See `CURRENT_STATE.md` for details.
