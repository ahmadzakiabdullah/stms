# PLAN

> **Pelan sejarah — digantikan.** Dakwaan “semua fasa selesai” di bawah ialah snapshot lama dan bukan release status semasa. Gunakan [`TODOS.md`](../TODOS.md), [`ROADMAP.md`](../ROADMAP.md) dan [audit 17 Ogos](audits/2026-08-17-full-project-and-production-audit.md).

> Cadangan penambahbaikan berdasarkan audit menyeluruh STMS.
> **Baca bersama:** `FINDING.md`, `TODOS.md`, `CURRENT_STATE.md`

---

## Ringkasan Pelan (Status: ✅ Semua Fasa 0-3 Selesai)

| Fasa | Fokus | Tempoh | Usaha | Status |
|------|-------|--------|-------|--------|
| **Fasa 0** | P0 — Kritikal / Security Fixes | Minggu 1 | ~16 jam | ✅ **Selesai** |
| **Fasa 1** | P1 — Code Quality & Documentation | Minggu 2-4 | ~40 jam | ✅ **Selesai** |
| **Fasa 2** | P2 — UX & Testing | Bulan 2 | ~30 jam | ✅ **Selesai** |
| **Fasa 3** | P3 — DevOps & Production | Bulan 2-3 | ~40 jam | ✅ **Selesai** (kecuali Redis) |
| **Fasa 4** | P4 — Future Features | Bulan 4+ | Bergantung | ⏳ Deferred |

---

## FASA 0: Kritikal & Security Fixes ✅ Selesai

> **Matlamat:** Baiki semua isu P0 yang menjejaskan keselamatan dan fungsi.
> **Status:** ✅ Semua selesai (26 Jun 2026). Rujuk `CHANGELOG.md` untuk detail.

### 0.1 — Fix Match/Result Policy Org-Scoping ✅
- **Rujukan:** FINDING F01 | **Usaha:** 2 jam
- **Perubahan:** Tambah org-scoping check dalam MatchPolicy dan ResultPolicy. Cross-org test cases ditambah.

### 0.2 — Fix Broken Action Classes ✅
- **Rujukan:** FINDING F02, F03 | **Usaha:** 4 jam
- **Perubahan:** Tambah `registerToEvent()` dan `withdrawFromEvent()` pada ParticipantService. Unit + feature tests.

### 0.3 — Fix Route Model Binding ✅
- **Rujukan:** FINDING F04, F05 | **Usaha:** 30 minit
- **Perubahan:** `getRouteKeyName()` return `'id'` untuk Result dan Registration.

### 0.4 — Create event_participants Migration ✅
- **Rujukan:** FINDING F06 | **Usaha:** 1 jam
- **Perubahan:** Migration `2026_06_25_000008_create_event_participants_table.php`.

### 0.5 — Fix Public Registration org_id ✅
- **Rujukan:** FINDING F07 | **Usaha:** 2 jam
- **Perubahan:** Auto-assign default organization dari config/env.

### 0.6 — Fix components.json ✅
- **Rujukan:** FINDING F08 | **Usaha:** 5 minit
- **Perubahan:** `"tsx": false` → `"tsx": true`.

---

## FASA 1: Code Quality & Documentation ✅ Selesai

> **Matlamat:** Baiki konsistensi code, hapuskan dead code, populate dokumentasi.
> **Status:** ✅ Semua selesai (26-30 Jun 2026). Rujuk `CHANGELOG.md` untuk detail.

### 1.1 — Deduplicate Tournament Actions ✅
- **Rujukan:** FINDING F09 | **Usaha:** 4 jam
- **Perubahan:** Tambah `updateWithSports()` pada TournamentService. Actions delegate kepada service.

### 1.2 — Dashboard Route → Controller ✅
- **Rujukan:** FINDING F10 | **Usaha:** 2 jam
- **Perubahan:** Cipta DashboardController. Logic dashboard dari closure routes/web.php dipindahkan.

### 1.3 — Remove Double Authorization ✅
- **Rujukan:** FINDING F11 | **Usaha:** 1 jam
- **Perubahan:** Buang Gate::authorize() dari 6 action files (Matches, Results).

### 1.4 — Add Logging to Services ✅
- **Rujukan:** FINDING F12 | **Usaha:** 4 jam
- **Perubahan:** Log::info() + Log::error() pada semua 12 service files.

### 1.5 — Fix UserFactory is_active ✅
- **Rujukan:** FINDING F13 | **Usaha:** 15 minit
- **Perubahan:** Migration `add_is_active_to_users_table` sudah wujud.

### 1.6 — Populate Architecture Documentation (21 files) ✅
- **Usaha:** 16 jam
- **Status:** Semua 21 fail docs/architecture/ diisi dengan kandungan substantive.

### 1.7 — Populate Database Documentation (4 files) ✅
- **Usaha:** 4 jam
- **Status:** Semua 4 fail docs/database/ diisi (schema, erd, migration-guidelines, naming-conventions).

### 1.8 — Populate Design System Documentation (8 files) ✅
- **Usaha:** 6 jam
- **Status:** Semua 8 fail docs/design-system/ diisi (overview, colors, typography, spacing, forms, tables, navigation, dashboard).

### 1.9 — Update Stale Documentation ✅
- **Usaha:** 1 jam
- **Perubahan:** README, CHANGELOG, CURRENT_STATE — updated untuk tech stack terkini.

---

## FASA 2: UX & Testing ✅ Selesai

> **Matlamat:** Tingkatkan UX, hapuskan dead code, tambah test coverage.
> **Status:** ✅ Semua selesai (30 Jun 2026). Rujuk `CHANGELOG.md` untuk detail.

### 2.1 — Remove Dead Frontend Code ✅
- **Rujukan:** FINDING F14 | **Usaha:** 4 jam
- **Perubahan:** Padam 5 fail tidak digunakan (DataTable.tsx, useInertiaForm.ts, NavLink.jsx, ResponsiveNavLink.jsx, Dropdown.jsx).

### 2.2 — Migrate Auth Pages to TypeScript ✅
- **Rujukan:** FINDING F15 | **Usaha:** 4 jam
- **Perubahan:** 6 auth pages .jsx → .tsx. Type definitions ditambah.

### 2.3 — Standardize Action Method Naming ✅
- **Rujukan:** FINDING F16 | **Usaha:** 1 jam
- **Perubahan:** `execute()` → `handle()` dalam 6 Action files.

### 2.4 — Update Profile Pages Styling ✅
- **Rujukan:** FINDING F17 | **Usaha:** 2 jam
- **Perubahan:** `bg-white` → Card, `text-gray-*` → `text-foreground`.

### 2.5 — Create EventParticipantPolicy ✅
- **Rujukan:** FINDING F18 | **Usaha:** 2 jam
- **Perubahan:** Cipta policy, register, +4 permissions, +4 tests.

### 2.6 — Add Cross-Org Tests ✅
- **Rujukan:** FINDING F19 | **Usaha:** 2 jam
- **Perubahan:** Cross-org test cases dalam MatchPolicyTest dan ResultPolicyTest.

### 2.7 — Add Form Request Validation Tests ✅
- **Rujukan:** FINDING — Testing | **Usaha:** 8 jam
- **Perubahan:** 8 Request validation test files.

### 2.8 — Add Missing Edge Case Tests ✅
- **Rujukan:** FINDING — Testing | **Usaha:** 6 jam
- **Perubahan:** Duplicate slug, tournament sports sync, ranking, export tests.

---

## FASA 3: DevOps & Production ✅ Selesai

> **Matlamat:** Jadikan projek production-ready dengan proper DevOps.
> **Status:** ✅ 3.1, 3.2, 3.4, 3.5, 3.6 selesai dari sisi kod/config contoh. 3.3 Sentry dialih keluar (vendor issue).

### 3.1 — Docker Setup ✅
- **Usaha:** 8 jam
- **Status:** Dockerfile, docker-compose.yml, nginx config, supervisor config, .dockerignore — semua siap.

### 3.2 — CI/CD Pipeline ✅
- **Usaha:** 8 jam
- **Status:** `.github/workflows/ci.yml` — Pint lint → PHPUnit (MySQL) → npm build.

### 3.3 — Error Tracking (Sentry) ❌ Dialih keluar
- **Usaha:** 4 jam (reverted 29 Jun 2026)
- **Sebab:** 500 error — vendor/sentry tiada di production. Boleh dipasang semula bila `composer install` boleh dijalankan di server.

### 3.4 — Health Check Endpoint ✅
- **Usaha:** 1 jam
- **Status:** `GET /health` return JSON. Feature test (2 tests).

### 3.5 — Environment Configuration ✅
- **Usaha:** 1 jam
- **Status:** `.env.example`, `.env.production.example`. SENTRY_DSN dialih keluar.

### 3.6 — Configure Redis for Cache and Queues ✅
- **Usaha:** 2 jam (estimated)
- **Status:** `.env.example`, `.env.production.example`, dan `docker-compose.yml` sudah menetapkan Redis untuk cache/queue. Production server masih perlu disahkan menjalankan nilai `.env` yang sama selepas deploy.

---

## FASA 4: Future Features (Bulan 4+)

> **Matlamat:** Features lanjutan selepas MVP stabil dan production-ready.

### 4.1 — REST API Layer

| Item | Detail |
|------|--------|
| **Rujukan** | ADR-007 |
| **Perubahan** | Cipta `/api/v1/` endpoints untuk semua domain modules. Token-based auth (Sanctum). API Resources untuk response formatting. Rate limiting. |
| **Manfaat** | Mobile app integration; third-party integrations. |
| **Usaha** | 40 jam |

### 4.2 — Advanced Notification Channels

| Item | Detail |
|------|--------|
| **Rujukan** | TODOS.md Deferred / CURRENT_STATE.md |
| **Perubahan** | In-app notifications sudah siap. Future scope tinggal email notifications, realtime delivery, dan webhook/third-party delivery untuk registration status dan match schedule changes. |
| **Manfaat** | User engagement; operational efficiency. |
| **Usaha** | 40 jam |

### 4.3 — Accreditation System

| Item | Detail |
|------|--------|
| **Rujukan** | ADR-006 |
| **Perubahan** | Dedicated module untuk accreditation management — officials, volunteers, media, team delegates. |
| **Manfaat** | Extended functionality untuk large-scale events. |
| **Usaha** | 80 jam |

### 4.4 — Live Scoring

| Item | Detail |
|------|--------|
| **Rujukan** | TODOS.md Deferred |
| **Perubahan** | Real-time score updates via WebSockets (Laravel Reverb/Pusher). Live ranking updates. Match status broadcasting. |
| **Manfaat** | Real-time experience untuk spectators and officials. |
| **Usaha** | 120 jam |

---

## Jadual Pelaksanaan

```
Minggu 1    ████████████████████ Fasa 0: Kritikal & Security
Minggu 2    ████████████████████ Fasa 1: Code Quality
Minggu 3    ████████████████████ Fasa 1: Code Quality (sambung)
Minggu 4    ████████████████████ Fasa 1: Documentation
Bulan 2     ████████████████████ Fasa 2: UX & Testing
Bulan 2-3   ████████████████████ Fasa 3: DevOps & Production
Bulan 4+    ████████████████████ Fasa 4: Future Features
```

---

## Anggaran Usaha

| Fasa | Usaha | Kepentingan |
|------|-------|-------------|
| Fasa 0 | 16 jam | Kritikal — mesti siap sebelum production |
| Fasa 1 | 40 jam | Tinggi — tingkatkan kualiti code & dokumentasi |
| Fasa 2 | 30 jam | Sederhana — UX & testing depth |
| Fasa 3 | 40 jam | Tinggi — production readiness |
| Fasa 4 | Bergantung | Masa depan — selepas MVP stabil |
| **JUMLAH** | **~126 jam (tanpa Fasa 4)** | |

---

## Kriteria Kejayaan

### ✅ Production Ready (Selepas Fasa 0-3) — 14/16 Tercapai

- [x] Semua isu P0 dibaiki
- [x] Match/Result policies ada org-scoping
- [x] Tiada action classes rosak
- [x] Semua model ada route binding yang betul
- [x] event_participants migration wujud
- [x] Public registration ada org_id
- [x] Dashboard dalam controller
- [x] Tournament actions tidak duplicate service logic
- [x] Tiada dead code
- [x] Semua pages TypeScript
- [x] Documentation lengkap (architecture + database + design system)
- [x] Docker setup
- [x] CI/CD pipeline
- [ ] ~~Error tracking (Sentry)~~ — Dialih keluar (vendor issue)
- [x] Health check endpoint
- [x] Test coverage > 80% (217 tests, all passing selepas sync 22 Julai 2026)

### Enterprise Ready (selepas Fasa 4)

- [ ] REST API layer dengan versioning
- [ ] Advanced notification channels
- [ ] Accreditation system
- [ ] Live scoring
- [ ] Mobile app support
- [ ] Advanced analytics

---

*Fail ini dikemaskini 22 Julai 2026 — semua Fasa 0-3 telah selesai dilaksanakan. Rujuk `CURRENT_STATE.md` untuk status semasa terkini.*
