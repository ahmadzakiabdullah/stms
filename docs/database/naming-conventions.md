# Database Naming Conventions

## Standard Baharu

- Jadual: plural `snake_case` (`event_sessions`, `sport_categories`).
- Kolum: `snake_case`; FK: singular `{model}_id`.
- Primary key domain: UUID `id` kecuali kontrak model sedia ada yang didokumentasikan.
- Timestamp: `created_at`, `updated_at`; soft delete: `deleted_at` apabila retention diperlukan.
- Indeks/unique mesti mencerminkan tenant dan natural key, contohnya `[organization_id, slug]`.
- Pivot sebenar: `tournament_sport` dan `sport_user`.

## Model ke Jadual

| Model | Jadual |
|---|---|
| `Organization` | `organizations` |
| `User` | `users` (PK `uuid`) |
| `EventSession` | `event_sessions` |
| `Sport` | `sports` |
| `SportCategory` | `sport_categories` |
| `Tournament` | `tournaments` |
| `Event` | `events` |
| `Participant` | `participants` |
| `Registration` | `registrations` |
| `EventParticipant` | `event_participants` |
| `Fixture` | `matches` |
| `Result` | `results` |
| `SquadMember` | `squad_members` |
| `Pool` | `pools` |
| `DrawVersion` | `draw_versions` |
| `Setting` | `settings` (integer PK legacy) |

Jangan mencipta model `Match`. Jangan menamakan pivot baharu `sport_tournament` kerana skema kanonik menggunakan `tournament_sport`.
