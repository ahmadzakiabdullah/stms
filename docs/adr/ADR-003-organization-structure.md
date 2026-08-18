# ADR-003: Organization Structure

**Status:** Accepted  
**Date:** 2026-06-03  
**Deciders:** STMS Architecture Team

## Context

STMS must support various types of organizations with different structures, including:

- National sports associations
- State sports councils
- Universities
- Schools
- Private tournament organizers

Some organizations may have parent-child relationships (e.g., a national body overseeing multiple state associations).

## Decision

We will create an `organizations` table with support for hierarchical structure using a self-referencing `parent_id` column.

### Design Decisions:
- The `organizations` table will store all organizations.
- A `parent_id` column allows organizations to form hierarchies.
- An `organization_type` field will differentiate between types of organizations (e.g., `national`, `state`, `university`, `school`, `private`).
- All users and sessions must belong to one organization.

## Consequences

### Positive
- Flexible enough to support various organizational structures.
- Enables future features such as delegated administration and organization hierarchies.
- Clear ownership of data per organization.

### Negative
- Adds slight complexity to queries involving organization relationships.
- Requires careful handling when changing an organization's parent.

### Neutral
- Most organizations are expected to operate independently in the early phases.

## Impact

- Affects the `User`, `Session`, and all child entities.
- Influences the design of multi-tenancy scoping.
- May affect future reporting and permission structures.

## Alternatives Considered

| Alternative | Decision | Reason |
|-------------|----------|--------|
| Flat organization structure (no hierarchy) | Rejected | Not flexible for real-world use cases |
| Multiple organization tables | Rejected | Violates DRY principle |
| Self-referencing `parent_id` | **Accepted** | Simple, effective, and widely used |

## References
- CLAUDE.md - Core Architecture
- ADR-002: Multi-Tenant Design

## Implementation Status (audited 2026-08-17)

The table, self-reference and organization types exist, and services validate hierarchy changes. Because Organization is the unscoped tenant root, its list/create operations now enforce explicit policy checks; organization management is restricted to super-admin and covered by the six-role direct-URL matrix. Delegated hierarchy administration remains a future decision rather than an implicit org-admin capability.
