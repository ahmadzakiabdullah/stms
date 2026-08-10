# ROADMAP

> Status terkini STMS — semua fasa MVP telah selesai.

## ✅ MVP Selesai (Phase 0 – 5)

| Phase | Focus Area                          | Status | Duration |
|-------|-------------------------------------|--------|----------|
| 0     | Project Setup & Foundation          | ✅ **Selesai** | 2 weeks  |
| 1     | Organization + User + RBAC          | ✅ **Selesai** | 3 weeks  |
| 2     | Session + Sport + Tournament        | ✅ **Selesai** | 4 weeks  |
| 3     | Participant Registration            | ✅ **Selesai** | 3 weeks  |
| 4     | Match Scheduling & Result Entry     | ✅ **Selesai** | 4 weeks  |
| 5     | Basic Ranking Engine                | ✅ **Selesai** | 3 weeks  |

**Tambahan selepas MVP:**
| Feature | Status |
|---------|--------|
| M6: Export, Reporting & Print | ✅ |
| Faculty Dashboard + Squad Management | ✅ |
| Dean Verification Workflow | ✅ |
| Post-Audit Hardening (Fasa 0-3) | ✅ |
| Docker + CI/CD | ✅ |
| Event Registration Cut-off Date | ✅ |
| Role/Permission Management UI | ✅ |
| In-App Notifications | ✅ |
| Bulk Squad Import (Excel/CSV) | ✅ |
| Participant Dashboard | ✅ |
| Logo/Crest Upload | ✅ |
| Draw/Group Allocation + Fixtures | ✅ |
| SAF 2026 Complete Data Seeding | ✅ |
| Role-aware Dashboard + Policy-aligned Navigation | ✅ |
| Participation Confirmation + Team Registration Forms | ✅ |
| Connected-CI Playwright + axe Evidence | ✅ |
| Sanitized Production-sized MySQL Restore Drill | ✅ |

## Current Production Data

- **Organization:** Universiti Teknikal Malaysia Melaka (UTeM)
- **Session:** SAF 2026 (1-30 September 2026)
- **2 Tournaments:** Fasa 1 (11-13 Sept), Fasa 2 (25-27 Sept)
- **8 Faculties:** FTKEK, FTKE, FTKM, FTKIP, FTMK, FPTT, FAIX, STEP
- **19 seeded users:** 8 faculty reps + 8 deans + 1 named super-admin + 2 development/test accounts
- **Credential warning:** current seeders use the shared development password `password`; do not run them unchanged in production.

## Long-term Vision (Future Phases)

| Phase | Focus Area                  | Priority | Status |
|-------|-----------------------------|----------|--------|
| 6     | Accreditation System        | Low      | ⏳ Deferred |
| 7     | Live Scoring & Real-time    | Low      | ⏳ Deferred |
| 8     | Mobile App (Flutter)        | Low      | ⏳ Deferred |
| 9     | Analytics & Reporting       | Low      | ⏳ Deferred |
| 10    | AI Features                 | Low      | ⏳ Deferred |
| 11    | REST API Layer              | Low      | ⏳ Deferred |

**Fokus semasa:** Release readiness — complete operational evidence (k6 multi-worker, external alerting, actual DR drill) and create first versioned release tag.

**Bukti operasi 5 Ogos 2026:** connected-CI Playwright/axe dan restore MySQL AES-256 terasing telah lulus. Baki sebelum tuntutan production-ready:
1. k6 berautentikasi pada staging berbilang worker (p95 < 750ms)
2. restore sebenar daripada backup produksi/off-site
3. pengesahan penerimaan amaran oleh operator luar
4. release tag `v0.1.0`

Rujuk `docs/testing/2026-08-05-operational-drill.md` dan `docs/deployment/release-runbook.md`.
