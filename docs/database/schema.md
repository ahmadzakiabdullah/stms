# Database Schema

> Snapshot 17 Ogos 2026: **61 migration files**. Migrasi baharu menambah `ranking_rules` JSON pada session/tournament; fresh SQLite migration 61/61 lulus. Runtime production memerlukan migrasi melalui release runbook.

## Domain dan Tenancy (20 jadual)

| Jadual | Tujuan utama |
|---|---|
| `organizations` | Root tenant dan hierarki `parent_id`. |
| `users` | Akaun UUID (`uuid` sebagai PK), organisasi, participant, username/e-mel. |
| `event_sessions` | Tempoh pertandingan organisasi, default ranking strategy dan JSON rules. |
| `sports` | Katalog sport organisasi. |
| `sport_categories` | Kategori dan quota roster. |
| `tournaments` | Tournament di bawah session; ranking strategy dan JSON rules override. |
| `tournament_sport` | Pivot many-to-many tournament–sport. |
| `events` | Tournament + sport + category, format dan pendaftaran. |
| `participants` | Individu/pasukan bagi organisasi dan session; `logo_path` serta `inverse_logo_path`. |
| `registrations` | Participant pada tournament. |
| `event_participants` | Participant pada event, seed, pool dan status. |
| `squad_members` | Roster/pegawai bagi event registration. |
| `pools` | Kumpulan draw event. |
| `matches` | Fixture; model Eloquent bernama `Fixture`. |
| `results` | Satu result bagi satu match melalui unique `results.match_id`. |
| `match_scoring_events` | Event jaringan individu yang menghubungkan result, pasukan dan atlet roster. |
| `draw_versions` | Snapshot draw berversi dengan actor/action/seed. |
| `sport_user` | Pivot tugasan pengguna kepada sport. |
| `settings` | Tetapan key/value setiap organisasi. |
| `notifications` | Database notifications Laravel. |
| `activity_log` | Activity log Spatie. |

## Framework/RBAC (12 jadual)

- Framework: `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `sessions`.
- Spatie Permission: `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`.

Jadual ke-33 ialah `migrations`.

## Konvensyen dan Pengecualian

- Data domain tenant menggunakan `organization_id`; parent/child mesti seorganisasi.
- Kebanyakan entity domain menggunakan UUID dan soft deletes.
- `users` menggunakan kolum PK bernama `uuid`, bukan `id`.
- `settings` ialah pengecualian legacy: integer auto-increment `id` dan tiada soft delete.
- `squad_members` serta `draw_versions` tidak mempunyai soft delete; draw version ialah snapshot sejarah.
- `tournament_sport`, `sport_user` dan pivot RBAC tidak memerlukan model/soft delete biasa.
- `match_scoring_events` dipadam bersama result; score agregat kekal pada `results`.
- Jangan mengubah migration yang telah deployed. Tambah migration baharu untuk pembetulan.

## Data Workspace Yang Diaudit

Baris aktif: 1 organization, 1 session, 3 tournaments (Sukan Antara Fakulti 2026, SAF 2026 Fasa 1, SAF 2026 Fasa 2), 24 sports, 30 categories, 30 events, 8 participants, 248 event participants, 24 matches, 1 result dan 17 users. Database juga mempunyai 4 queued jobs, 0 failed jobs dan 898 activity logs. Angka ini ialah snapshot baca-sahaja 19 Ogos 2026 daripada pangkalan data yang disambungkan, bukan kontrak seed atau production guarantee. Snapshot asal 17 Ogos 2026: 1 tournament, 30 categories, 30 events, 0 results, 32 queued jobs dan 882 activity logs.

Lihat [ERD](erd.md), [migration guidelines](migration-guidelines.md) dan [audit penuh](../audits/2026-08-17-full-project-and-production-audit.md).
