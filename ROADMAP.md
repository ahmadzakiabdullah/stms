# ROADMAP

> **Repository update — 22 September 2026:** Public portal/localization, concurrency regression coverage dan backend asynchronous transfer tersedia. Authorization/tenant queue dan query-budget hardening lulus ujian lokal. Distributed concurrency serta P0 production activation kekal terbuka.

> **Kemas kini — 12 November 2026:** UI queued import/export dengan polling/progress/failure handling sudah dihantar (Reports Quick Exports, participant import dan event registration import). Tarikh pertandingan semasa: Fasa 1 3–4 Disember 2026 dan Fasa 2 12–13 Disember 2026.

> Rujuk `CURRENT_STATE.md` dan `TODOS.md` untuk bukti semasa; angka production bertarikh di bawah ialah snapshot sejarah.

## MVP Produk

| Milestone | Skop | Status produk |
|---|---|---|
| M0 | Foundation, Laravel/Inertia/TypeScript/shadcn | Selesai |
| M1 | Organization, User, RBAC | Selesai; read-authorization matrix hijau |
| M2 | Session, Tournament, Sport, Category, Event | Selesai |
| M3 | Participant, Registration, Squad, Dean verification | Selesai |
| M4 | Draw, Match scheduling, Result entry | Selesai; draw regeneration regression tests hijau |
| M5 | Ranking | Selesai untuk MVP; contract/registry dan data-driven rules tersedia |
| M6 | Export, Report, Print, Notifications | Selesai untuk MVP |

“Selesai” di atas bermaksud capability produk wujud, bukan bermaksud release gate semasa hijau.

## Fokus Semasa — Release Hardening

Repository ialah release candidate, tetapi deployment production kekal **NO-GO** sehingga:

1. Runtime production menggunakan baseline selamat: enforcement on, Redis cache/queue/session, real mail, verification on, timezone Malaysia dan CSP enforcement.
2. DB grants disahkan; actual production backup disalin off-host dan isolated restore direkod dengan RPO/RTO.
3. Authenticated staging load test, external alert receipt dan production reset-password delivery dibuktikan.
4. Smoke test deployment serta Playwright/axe production lulus dan tag versi pertama dicipta selepas semua bukti tersedia.

Product owner telah mengesahkan tarikh Fasa 1: 3–4 Disember 2026 dan Fasa 2: 12–13 Disember 2026, satu tournament, 30 acara, 8 kontinjen dan IA single-page. Remediation juga telah dikomit dan connected CI hijau; rekod pertandingan kekal boleh diedit jika maklumat rasmi berubah.

## Portal Production Semasa

Production <https://saf.utem.edu.my/> memaparkan satu homepage dengan anchor sections untuk Sports, Schedule, Results dan Medal standings, halaman awam `/sports`, `/schedule`, `/faculties`, `/venues` dan `/athletes`, serta halaman maklumat `/news`, `/downloads`, `/faq`, `/about` dan `/contact-us`. `/matches`, `/results` dan `/live` ialah redirect 301 ke `/schedule`. Route lama `/sports-programme`, `/medal-tally` dan `/schedules` tidak wujud.

Competition awam kini bertarikh **Fasa 1: 3–4 Disember 2026 dan Fasa 2: 12–13 Disember 2026**. Pada masa audit terdahulu terdapat 23 sukan aktif, 30 event, 8 fakulti dan 12 match belum selesai.

## Selepas Release Stabil

| Workstream | Keutamaan | Status |
|---|---|---|
| Policy/read-access matrix lengkap | Tinggi | Selesai dalam repository |
| Ranking rules/tiebreakers configurable | Tinggi | Selesai untuk MVP |
| External monitoring + operator alert | Tinggi | Ada bukti (GitHub Actions `/up` setiap 5 minit, issue #75 dibuka/ditutup — lihat `CURRENT_STATE.md`); named owner/alert destination production masih P0 |
| Actual off-host backup restore drill | Tinggi | Ada bukti (restore terasing MySQL 8, RTO 7.699 s — lihat `CURRENT_STATE.md`); backup berjadual production belum aktif |
| Multi-worker staging load test | Tinggi | Ada bukti (k6 1,150/1,150 checks, p95 81.543 ms — lihat `CURRENT_STATE.md`) |
| SEO/sitemap | Sederhana | Selesai dalam repository; contact production belum lengkap |
| REST API `/api/v1` | Masa depan | Deferred |
| Accreditation | Masa depan | Deferred |
| Live scoring/realtime | Masa depan | Deferred |
| Mobile app | Masa depan | Deferred |
| Advanced analytics/AI | Masa depan | Deferred |

Rujuk [`TODOS.md`](TODOS.md) untuk checklist aktif dan [laporan audit 17 Ogos](docs/audits/2026-08-17-full-project-and-production-audit.md) untuk bukti.
