# Domain Model

The STMS domain follows a strict hierarchical structure. Every entity belongs to exactly one parent, forming a single root-to-leaf chain: **Organization → Session → Tournament → Sport → Event → Match → Result**.

## Core Entities

- **Organization** — the top-level tenant. Every tenant-aware model carries an `organization_id`. Self-referencing `parent_id` for hierarchy.
- **Session** (table: `event_sessions`) — a named time-bounded instance (e.g., "SUKMA 2026"). Belongs to one Organization.
- **Tournament** — a competition within a session (e.g., "Men's Football"). Belongs to one Session. Links to Sports via many-to-many pivot `tournament_sport`.
- **Sport** — a generic sport definition (e.g., "Basketball"). Has many `SportCategory` children.
- **SportCategory** — sub-classification within a sport (e.g., "Men's Open", "U-18"). Contains quota fields for gender-based and total-based rosters (`quota_mode`, `max_athletes_total`, `max_male_athletes`, `max_female_athletes`, `min_male_athletes`, `min_female_athletes`, `max_officials`).
- **Event** — a specific competition linking a Tournament + Sport + SportCategory (e.g., "Football - Men's Open - Group Stage"). Belongs to Tournament, Sport, and SportCategory.
- **Match** (table: `matches`, implemented by `Fixture` model class because `Match` is a PHP reserved keyword) — a single contest between participants. Belongs to Event.
- **Result** — the outcome of a match (scores + winner). One-to-one with Match.

## Supporting Entities

- **User** — system users with Spatie roles/permissions. Belongs to Organization. May link to a Participant.
- **Participant** — individual or team competing in tournaments. May have linked User accounts. Belongs to Organization + Session.
- **Registration** — links Participant to Tournament. Status tracked (pending/confirmed/rejected/cancelled).
- **EventParticipant** — links Participant to specific Event within a tournament. Used for event-level registration and squad management.
- **SquadMember** — roster members within an EventParticipant (athletes, coaches, managers). Role-based and total-athlete quota validation is centralized in `SquadQuotaService`.
- **Venue / Schedule / Accreditation** — future/deferred milestones.

## Key Relationships

- All entities in the hierarchy cascade via `belongsTo` from Result upward to Organization.
- Every tenant-scoped model uses UUID primary keys and `organization_id` FK constraint.
- Slug uniqueness enforced per-organization via composite unique indexes.
