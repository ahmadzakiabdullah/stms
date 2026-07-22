# FINDING

> Laporan penemuan audit menyeluruh STMS.
> **Tarikh audit asal:** 25 Jun 2026 | **Tarikh kemaskini:** 22 Julai 2026
> **Baca bersama:** `CLAUDE.md`, `AGENTS.md`, `CURRENT_STATE.md`, `PLAN.md`, `TODOS.md`

---

## Ringkasan Eksekutif

| Metrik | Nilai (25 Jun) | Nilai (3 Julai) |
|--------|:--------------:|:----------------:|
| Skor Keseluruhan | **61/100** | **82/100** |
| Gred | **C+** | **B+** |
| Kematangan | **MVP (Beta Candidate)** | **MVP (Maintenance / Production)** |
| Jumlah Model | 12 | 15 |
| Jumlah Controller | 16 + 9 Auth | 34 (25 app + 9 auth) |
| Jumlah Service | 12 | 13 |
| Jumlah Action | 35 | 35 |
| Jumlah Policy | 11 | 13 |
| Jumlah Form Request | 24 | 24 |
| Jumlah Migration | 28 | 46 |
| Jumlah Test | 162 (46 fail) | **217 (0 fail selepas sync 22 Julai 2026)** |
| Jumlah Dokumentasi | 67 fail (17 lengkap, 50 KOSONG) | 77 fail (56 lengkap, 21 KOSONG — API docs sengaja dikosongkan) |

---

## Skor Mengikut Kategori

| Kategori | Skor (Jun) | Skor (Julai) | Perubahan |
|----------|:----------:|:------------:|:---------:|
| Project Structure | 7.5/10 | 8.5/10 | +1.0 |
| Software Architecture | 7.0/10 | 8.0/10 | +1.0 |
| Database Design | 7.5/10 | 8.0/10 | +0.5 |
| Code Quality | 6.5/10 | 8.0/10 | +1.5 |
| Security | 5.5/10 | **8.5/10** | **+3.0** |
| Performance | 7.0/10 | 7.5/10 | +0.5 |
| UI Design | 7.0/10 | 7.5/10 | +0.5 |
| User Experience | 6.5/10 | 8.0/10 | +1.5 |
| Documentation | **3.5/10** | **8.0/10** | **+4.5** |
| DevOps | **2.5/10** | **7.0/10** | **+4.5** |
| Testing | 7.0/10 | **8.5/10** | +1.5 |
| Business Process | 7.5/10 | 8.0/10 | +0.5 |
| Maintainability | 6.0/10 | 7.5/10 | +1.5 |
| Scalability | 6.0/10 | 7.0/10 | +1.0 |
| Production Readiness | 5.0/10 | **7.5/10** | +2.5 |

**Nota:** Peningkatan ketara dalam Security (+3.0), Documentation (+4.5), dan DevOps (+4.5) hasil dari post-audit hardening.

---

## P1 — KRITIKAL (Selesai DibaiKi)

### F01: Match/Result Policy Tiada Org-Scoping ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Policies/MatchPolicy.php`, `app/Policies/ResultPolicy.php` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Tinggi~~ ✅ |
| **Perubahan** | Tambah semakan `$model->organization_id === $user->organization_id` dalam `update()` dan `delete()` kedua-dua policy. Cross-org test cases ditambah dalam MatchPolicyTest dan ResultPolicyTest. |

### F02: Action Classes Rosak — RegisterParticipantToEvent ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Actions/Participants/RegisterParticipantToEvent.php` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Tinggi~~ ✅ |
| **Perubahan** | Tambah method `registerToEvent()` pada `ParticipantService`. Unit test dan feature test untuk registration flow ditambah. |

### F03: Action Classes Rosak — WithdrawParticipantFromEvent ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Actions/Participants/WithdrawParticipantFromEvent.php` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Tinggi~~ ✅ |
| **Perubahan** | Tambah method `withdrawFromEvent()` pada `ParticipantService`. Unit test dan feature test untuk withdrawal flow ditambah. |

### F04: Result::getRouteKeyName() Kembali 'slug' (Tiada Kolom slug) ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Models/Result.php` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Tinggi~~ ✅ |
| **Perubahan** | Tukar `getRouteKeyName()` ke `return 'id'`. Route binding test ditambah. |

### F05: Registration::getRouteKeyName() Kembali 'slug' (Tiada Kolom slug) ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Models/Registration.php` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Tinggi~~ ✅ |
| **Perubahan** | Tukar `getRouteKeyName()` ke `return 'id'`. Route binding test ditambah. |

### F06: Tiada Migration untuk event_participants Pivot Table ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `database/migrations/2026_06_25_000008_create_event_participants_table.php` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Sederhana~~ ✅ |
| **Perubahan** | Migration dicipta untuk `event_participants` dengan columns: id (uuid PK), event_id (FK), participant_id (FK), registration_date, status, seed_number, notes, timestamps, softDeletes. Unique constraint pada `[event_id, participant_id]`. |

### F07: Public Registration Tiada organization_id ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Http/Controllers/Auth/RegisteredUserController.php` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Sederhana~~ ✅ |
| **Perubahan** | Pilihan B: auto-assign default organization dari config/env. Feature test ditambah: register user → verify org_id tidak null. |

---

## P2 — SEDERHANA (Selesai DibaiKi)

### F08: components.json tsx:false — Mismatch dengan Project ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `components.json` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Sederhana~~ ✅ |
| **Perubahan** | Tukar `"tsx": false` ke `"tsx": true`. |

### F09: Tournament Actions Ganda Logik Service ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Services/TournamentService.php`, `app/Actions/Tournaments/CreateTournament.php`, `UpdateTournament.php` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Sederhana~~ ✅ |
| **Perubahan** | Tambah `updateWithSports()` pada `TournamentService`. Kedua-dua actions delegate sepenuhnya kepada service. Unit test ditambah. |

### F10: Dashboard Route Closure 40+ Baris ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Http/Controllers/DashboardController.php` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Sederhana~~ ✅ |
| **Perubahan** | Cipta `DashboardController::index()`. Pindahkan semua logic daripada closure dalam routes/web.php. DashboardTest verify. |

### F11: Double Authorization dalam Match/Result Actions ✅

| Item | Detail |
|------|--------|
| **Lokasi** | 6 action files dalam `app/Actions/Matches/` dan `app/Actions/Results/` |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Rendah~~ ✅ |
| **Perubahan** | Buang `Gate::authorize()` daripada semua 6 action files. Controller handle authorization sepenuhnya. |

### F12: Tiada Logging dalam Services ✅

| Item | Detail |
|------|--------|
| **Lokasi** | Semua 12 service files |
| **Status** | **FIXED** (26 Jun 2026) |
| **Risiko** | ~~Sederhana~~ ✅ |
| **Perubahan** | Tambah `use Illuminate\Support\Facades\Log` + `Log::info()` untuk operasi CRUD berjaya + `Log::error()` untuk exceptions pada semua 12 service files. |

### F13: UserFactory Rujuk is_active (Kolom Tidak Wujud) ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `database/factories/UserFactory.php` |
| **Status** | **FIXED** (27 Jun 2026) |
| **Risiko** | ~~Rendah~~ ✅ |
| **Perubahan** | Pilihan B: migration `add_is_active_to_users_table.php` (sudah wujud). Factory kini boleh guna `is_active`. |

---

## P3 — RENDAH (Selesai DibaiKi)

### F14: Dead Frontend Code (5 Fail Tidak Digunakan) ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `resources/js/components/` |
| **Status** | **FIXED** (30 Jun 2026) |
| **Risiko** | ~~Rendah~~ ✅ |
| **Perubahan** | 5 fail dipadam: `DataTable.tsx`, `useInertiaForm.ts`, `NavLink.jsx`, `ResponsiveNavLink.jsx`, `Dropdown.jsx`. |

### F15: Auth Pages Masih .jsx (Bukan TypeScript) ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `resources/js/Pages/Auth/*.jsx` (6 fail) |
| **Status** | **FIXED** (30 Jun 2026) |
| **Risiko** | ~~Rendah~~ ✅ |
| **Perubahan** | Semua 6 auth pages ditukar ke `.tsx`. Type definitions untuk props ditambah. `npm run build` compiled auth pages sebagai chunks berasingan. |

### F16: Inconsistent Action Method Naming (handle vs execute) ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Actions/Matches/*.php`, `app/Actions/Results/*.php` |
| **Status** | **FIXED** (30 Jun 2026) |
| **Risiko** | ~~Rendah~~ ✅ |
| **Perubahan** | `execute()` → `handle()` dalam semua 6 Action files. Controller calls diupdate. Semua 215 tests pass. |

### F17: Profile Pages Guna Styling Lama Breeze ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `resources/js/Pages/Profile/Edit.tsx`, `Partials/*.tsx` |
| **Status** | **FIXED** (30 Jun 2026) |
| **Risiko** | ~~Rendah~~ ✅ |
| **Perubahan** | `bg-white` → `Card` component, `text-gray-*` → `text-foreground`/`text-muted-foreground`. Dark mode ready. |

### F18: EventParticipant Tiada Policy ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `app/Policies/EventParticipantPolicy.php` |
| **Status** | **FIXED** (30 Jun 2026) |
| **Risiko** | ~~Rendah~~ ✅ |
| **Perubahan** | Cipta `EventParticipantPolicy` dengan org-scoping. Register dalam `AppServiceProvider`. Tambah 4 permissions dalam seeder. 4 tests dalam EventParticipantPolicyTest. |

### F19: ResultPolicyTest & MatchPolicyTest Tiada Cross-Org Test ✅

| Item | Detail |
|------|--------|
| **Lokasi** | `tests/Feature/Policies/MatchPolicyTest.php`, `tests/Feature/Policies/ResultPolicyTest.php` |
| **Status** | **FIXED** (26 Jun — digabung dengan F01) |
| **Risiko** | ~~Rendah~~ ✅ |
| **Perubahan** | Cross-org test cases ditambah dalam kedua-dua policy test. |

---

## Penemuan Documentation

### Dokumentasi Lengkap (46 fail)

| Fail | Status | Kualiti |
|------|--------|---------|
| CLAUDE.md | Lengkap | 9/10 |
| AGENTS.md | Lengkap | 9/10 |
| README.md | Lengkap | 8/10 |
| CURRENT_STATE.md | Lengkap | 9/10 |
| TODOS.md | Lengkap | 9/10 |
| ROADMAP.md | Lengkap | 7/10 |
| CHANGELOG.md | Lengkap | 8/10 |
| PLAN.md | Lengkap | 8/10 |
| CONTRIBUTING.md | Lengkap | 7/10 |
| SECURITY.md | Lengkap | 5/10 |
| MASTER_PROMPT.md | Lengkap | 7/10 |
| ADR-001 hingga ADR-008 (8) | Lengkap | 9/10 |
| Architecture docs (21) | **Lengkap (dahulu KOSONG)** | 8/10 |
| Database docs (4) | **Lengkap (dahulu KOSONG)** | 8/10 |
| Design System docs (8) | **Lengkap (dahulu KOSONG)** | 7/10 |
| IMPLEMENTATION_STATUS.md | Lengkap | 8/10 |

### Dokumentasi KOSONG (21 fail — sengaja dikosongkan)

| Kategori | Jumlah Fail | Status |
|----------|:-----------:|--------|
| API Docs | 21 | **Sengaja dikosongkan** — REST API adalah ciri akan datang (deferred) |

**Nota:** Berbanding audit asal (50 fail kosong), kini hanya 21 fail API docs yang kosong — dan ini adalah dengan design yang sengaja, kerana REST API belum dibina. Semua dokumentasi architecture, database, dan design-system telah diisi dengan kandungan substantive.

---

## Penemuan Database

### Kekuatan

- Semua tenant tables ada `organization_id` FK dengan cascade delete
- Per-organization slug uniqueness dikuatkuasakan via composite unique constraints
- Foreign key constraints dengan cascade/null-on-delete
- Soft deletes pada 11 daripada 13 domain models
- UUID primary keys pada semua domain tables
- **Migration event_participants**: ✅ sudah wujud (sebelumnya kritikal)

### Kelemahan (Masih Ada)

| Item | Perincian |
|------|-----------|
| Users table PK migration | HIGH RISK — migration menukar primary key users kepada UUID; perlu berhati-hati pada production data lama |
| event_sessions naming | Beberapa fix migrations untuk rename daripada `sessions` (stabil, tapi messy) |
| Duplicate migration timestamps | Dua migration berkongsi `2026_06_12_000002` |

---

## Penemuan Testing

### Ringkasan (Selepas Post-Audit Hardening)

| Kategori | Fail | Methods |
|----------|:----:|:-------:|
| Feature (CRUD + Dashboard) | 18 | ~75 |
| Feature (Auth) | 6 | ~17 |
| Feature (Policies) | 12 | ~58 |
| Feature (Form Requests) | 8 | ~26 |
| Unit (Services + Ranking) | 13 | ~40 |
| **JUMLAH** | **57** | **~216** |

### Kekuatan

- Multi-tenancy testing konsisten (orgA/orgB pattern)
- RBAC diuji dengan 3-4 roles setiap entity
- Service layer diuji secara langsung
- Cross-organization attack prevention diuji
- **Policy tests**: Lengkap untuk semua 12 policies (sebelumnya 11)
- **Form Request tests**: 8 files baru ditambah (sebelumnya 0)
- **Edge case tests**: Duplicate slug, tournament sports sync, ranking calculation, export generation
- **EventParticipant tests**: Ditambah (sebelumnya tiada)
- **Semua 217 tests passing** selepas sync 22 Julai 2026

### Kelemahan (Masih Ada)

- Sesetengah Feature tests hanya assert `assertOk()` tanpa verify data
- Tiada unit test untuk DashboardController, ExportController, ReportingController
- Tiada unit test khusus untuk beberapa controller report/export/dashboard; coverage sedia ada datang melalui feature tests.

---

## Penemuan Security

### Lulus

| Semakan | Status |
|---------|--------|
| Laravel Breeze Inertia | ✓ |
| Password Hashing | ✓ |
| Email Verification | ✓ |
| Rate Limiting (Login) | ✓ |
| CSRF Protection | ✓ |
| XSS Protection | ✓ |
| SQL Injection Prevention | ✓ |
| Input Validation (Form Requests) | ✓ |
| Policy org-scoping (semua 12 policies) | ✅ **FIXED** |
| Public registration org_id | ✅ **FIXED** |
| EventParticipant policy | ✅ **FIXED** |

### Isu Kecil (Masih Ada)

| # | Isu | Risiko |
|---|-----|--------|
| 1 | SafePaginatedQuery catch semua Throwable | Rendah |
| 2 | Notification/email verification belum dioptimumkan | Rendah |

---

## Status Modul (Selepas Post-Audit)

| Modul | Status | Siap | Architecture | Security | Performance | Code Quality |
|-------|--------|:----:|:------------:|:--------:|:-----------:|:------------:|
| Organization | Selesai | 100% | 9/10 | 9/10 | 8/10 | 9/10 |
| User + RBAC | Selesai | 100% | 9/10 | 9/10 | 8/10 | 8/10 |
| Sport | Selesai | 100% | 9/10 | 9/10 | 8/10 | 9/10 |
| SportCategory | Selesai | 100% | 9/10 | 9/10 | 8/10 | 9/10 |
| Session | Selesai | 100% | 9/10 | 9/10 | 8/10 | 9/10 |
| Tournament | Selesai | 100% | 8/10 | 9/10 | 8/10 | 8/10 |
| Event | Selesai | 100% | 8/10 | 9/10 | 8/10 | 8/10 |
| Participant | Selesai | 100% | 8/10 | 9/10 | 8/10 | 8/10 |
| Registration | Selesai | 100% | 8/10 | 9/10 | 8/10 | 8/10 |
| Match/Fixture | Selesai | 100% | 8/10 | 8/10 | 8/10 | 8/10 |
| Result | Selesai | 100% | 8/10 | 8/10 | 8/10 | 8/10 |
| Ranking | Selesai | 100% | 8/10 | 9/10 | 7/10 | 8/10 |
| Export | Selesai | 100% | 8/10 | 9/10 | 7/10 | 8/10 |
| Reporting | Selesai | 95% | 7/10 | 9/10 | 7/10 | 7/10 |
| Dashboard | Selesai | 100% | 8/10 | 9/10 | 7/10 | 8/10 |
| Faculty Dashboard | Selesai | 100% | 8/10 | 8/10 | 7/10 | 7/10 |
| Dean Verification | Selesai | 100% | 8/10 | 8/10 | 7/10 | 7/10 |
| Squad Members | Selesai | 100% | 7/10 | 7/10 | 7/10 | 7/10 |
| EventParticipant | Selesai | 85% | 7/10 | 8/10 | 7/10 | 7/10 |
| API | Dirancang | 0% | — | — | — | — |
| Notifications | Selesai | 100% | 8/10 | 8/10 | 7/10 | 8/10 |
| Accreditation | Dirancang | 0% | — | — | — | — |
| Docker | Selesai | 100% | 8/10 | 7/10 | 8/10 | 8/10 |
| CI/CD | Selesai | 100% | 8/10 | 7/10 | 8/10 | 8/10 |
| Health Check | Selesai | 100% | 9/10 | 9/10 | 9/10 | 9/10 |

---

## Penemuan DevOps

| Aspek | Status | Nota |
|-------|--------|------|
| Environment Management | Baik | .env + .env.example + .env.production.example |
| Docker | **Selesai** | Dockerfile (PHP 8.3 FPM + Nginx), docker-compose.yml (app + MySQL 8 + Redis), supervisor config |
| CI/CD | **Selesai** | `.github/workflows/ci.yml` — Pint lint → PHPUnit (MySQL service) → npm build + artifact |
| Deployment | Hybrid | Manual deploy via UNC + Docker-ready |
| Monitoring | Asas | Health check endpoint (`GET /health`) wujud. Sentry dialih keluar (vendor issue) |
| Logging | Baik | Laravel logging + service-layer Log::info()/Log::error() |
| Backup | Tiada | Tiada backup configuration |
| Production Readiness | Baik | Docker + CI/CD sedia. Redis dikonfigurasi dalam env contoh; production server perlu disahkan. Sentry boleh dipasang semula |

---

*Fail ini dikemaskini 22 Julai 2026 — dokumentasi diselaraskan semula dengan kod semasa, termasuk notifications, kiraan modul, dan status ujian.*
