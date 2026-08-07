# Database Schema

The schema is migration-driven. There are 59 migration files as of 7 August 2026; do not infer the final table count from migration count because several migrations alter or rename existing tables.

## Tables Overview

### Core / Tenancy

- **`organizations`** — Root tenant entity. Self-referencing `parent_id` for hierarchy. Types: national, state, university, school, private.
- **`users`** — System users with UUID PK. Belong to one organization. Link to participant via `participant_id`. Has a globally unique lowercase `username`, email identity, and Spatie roles.

### Tournament Structure

- **`sports`** — Registry of supported sports (e.g. Football, Badminton). Generic — no sport-specific rules.
- **`sport_categories`** — Sub-classifications within a sport (e.g. "Men's Open", "U-18"). Quota fields support gender-based and total-based rosters: `quota_mode`, `max_athletes_total`, `max_male_athletes`, `max_female_athletes`, `min_male_athletes`, `min_female_athletes`, `max_officials`.
- **`event_sessions`** — Named time-bounded instances (e.g. "SUKMA 2026", "SUKIPT 2025"). Contains start/end dates.
- **`tournaments`** — Competition under a session. Has `ranking_strategy` column (points/win_rate/medal_tally). Links to sports via `tournament_sport` pivot.
- **`tournament_sport`** — Pivot table linking tournaments to sports (many-to-many).
- **`events`** — Specific competition linking tournament + sport + category. E.g. "Football - Men's Open - Group Stage".

### Participants & Registration

- **`participants`** — Individuals or teams. Types: individual/team. Has slug, email, phone, status tracking.
- **`registrations`** — Links participants to tournaments. Unique per `[tournament_id, participant_id]`. Status: pending/confirmed/rejected/cancelled.
- **`event_participants`** — Links participants to specific events. Used for event-level enrolment. Status: registered/pending/confirmed/withdrawn/disqualified/rejected. Has `seed_number`.

### Competition

- **`matches`** — Individual contests within an event. Has home/away participant FKs, venue, scheduled_at, status (scheduled/in_progress/completed/cancelled). Unique `[event_id, match_number]`.
- **`results`** — One-to-one with matches. Stores score_home, score_away, winner_participant_id.

### Squad Management

- **`squad_members`** — Roster members within an event participant. Roles: athlete_male, athlete_female, assistant_manager, manager, coach, physio. Has `matrix_no` (unique within an event registration), identification/passport data, phone, and `organization_id`.

### Draws, Configuration, Notifications, and Audit

- **`pools`** — Event draw/group allocation, tenant-scoped by `organization_id`.
- **`draw_versions`** — Immutable tenant-scoped draw allocation/fixture snapshots with version, seed, actor and action metadata for audit and rollback.
- **`settings`** — Per-organization key/value settings with a unique `[organization_id, key]` constraint.
- **`notifications`** — Laravel database notifications.
- **`activity_log`** — Spatie activity records for selected audited actions.

### Laravel System Tables (7)

- **`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `sessions`** — Standard Laravel internal tables.

### Spatie Permission Tables (5)

- **`permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`** — Standard Spatie RBAC tables.
