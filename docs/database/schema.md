# Database Schema (26 Tables)

## Tables Overview

### Core / Tenancy

- **`organizations`** — Root tenant entity. Self-referencing `parent_id` for hierarchy. Types: national, state, university, school, private.
- **`users`** — System users with UUID PK. Belong to one organization. Link to participant via `participant_id`. Has Spatie roles.

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

- **`squad_members`** — Roster members within an event participant. Roles: athlete_male, athlete_female, manager, coach, physio. Has identification_no, phone, and `organization_id`.

### Laravel System Tables (7)

- **`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `sessions`** — Standard Laravel internal tables.

### Spatie Permission Tables (5)

- **`permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`** — Standard Spatie RBAC tables.
