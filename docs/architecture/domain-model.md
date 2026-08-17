# Domain Model

## Ownership and Catalogs

STMS uses `Organization` as the tenant root. The implemented schema combines tenant ownership with reusable organization-level catalogs:

```text
Organization
├─ EventSession
│  ├─ Tournament
│  │  └─ Event ─ Fixture (matches) ─ Result
│  └─ Participant
├─ Sport ─ SportCategory
└─ Users / Settings

Tournament <-> Sport through tournament_sport
Event -> Tournament + Sport + SportCategory
```

Every tenant-aware domain row carries `organization_id`, even where the organization can also be inferred through relations.

## Core Models

| Model | Table/key | Notes |
|---|---|---|
| Organization | `organizations.id` UUID | Root tenant; parent hierarchy |
| User | `users.uuid` UUID | One organization; roles/permissions |
| Session | `event_sessions.id` UUID | Competition edition/cycle; ranking configuration |
| Tournament | `tournaments.id` UUID | Belongs to session; many-to-many sports; ranking configuration |
| Sport | `sports.id` UUID | Organization catalog |
| SportCategory | `sport_categories.id` UUID | Quota-bearing category under sport |
| Event | `events.id` UUID | Tournament + sport + category |
| Participant | `participants.id` UUID | Session participant/faculty/team |
| Registration | `registrations.id` UUID | Participant to tournament |
| EventParticipant | `event_participants.id` UUID | Participant to event; pool/status |
| Pool | `pools.id` UUID | Draw group |
| Fixture | `matches.id` UUID | PHP model name avoids reserved `Match` |
| Result | `results.id` UUID | One result per `match_id` |
| SquadMember | `squad_members.id` UUID | Deliberate no-soft-delete exception |
| DrawVersion | `draw_versions.id` UUID | Immutable draw snapshot; deliberate no-soft-delete exception |
| Setting | `settings.id` integer | Organization key/value; deliberate UUID/soft-delete exception |

## Important Relationships

- `results.match_id` references `matches.id`; `matches` does not contain `result_id`.
- Participant belongs directly to Organization + Session.
- Sport is organization-scoped and reusable across sessions and tournaments.
- Event joins tournament, sport and category.
- User sport assignment uses tenant-aware `sport_user`.
- Draw versions snapshot pool allocations and fixtures for rollback.
- Session and Tournament store validated JSON `ranking_rules`; a tournament overrides its session defaults.

## Deletion and Key Exceptions

Most core entities use UUID keys and soft deletes. The accepted MVP exceptions are:

- `settings`: compact organization-scoped key/value rows use an integer key and physical replacement/deletion semantics.
- `squad_members`: subordinate roster rows are physically deleted and protected through their parent workflow.
- `draw_versions`: immutable audit snapshots are retained without soft-delete semantics.
- Pivot and Laravel framework tables follow their framework-specific key/deletion conventions.

Any change to these exceptions requires an ADR and a data migration plan.

## Deferred Domain Areas

Accreditation, Venue and dedicated Schedule models are not implemented in the current milestone.
