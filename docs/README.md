# Documentation Index

> **Current implementation — 21 August 2026:** Public athlete/team profiles, configured individual scoring events, participant-grouped public scorers and the result score editor UX are implemented. Historical audit files retain their original snapshot dates by design.

Dokumentasi STMS dibahagikan kepada sumber kanonik, keputusan seni bina, panduan operasi dan rekod sejarah.

## Sumber Kanonik

1. [`../CURRENT_STATE.md`](../CURRENT_STATE.md) — keadaan produk/repository/runtime/production semasa.
2. [`../TODOS.md`](../TODOS.md) — backlog aktif dan Current Focus.
3. [`../ROADMAP.md`](../ROADMAP.md) — milestone produk dan release.
4. [Audit 17 Ogos 2026](audits/2026-08-17-full-project-and-production-audit.md) — bukti audit semasa.
5. Fail dalam [`architecture/`](architecture/) dan [`database/`](database/) — rujukan implementasi.

Jika nombor atau status bercanggah, sumber bertarikh paling baharu menang; dakwaan production mesti disokong oleh production/runtime evidence, bukan contoh konfigurasi sahaja.

## Direktori

- `adr/` — Architecture Decision Records. Keputusan kekal, implementation status boleh dikemas kini.
- `architecture/` — current architecture dan jurang diketahui.
- `database/` — schema, ERD, naming dan migration rules.
- `deployment/` — release, backup dan restore runbooks.
- `design-system/` — frontend/design usage semasa.
- `security/` — controls dan production checklist.
- `testing/` — current quality gates dan rekod drill.
- `audits/` — point-in-time reports.
- `api/` — future REST API placeholders; bukan endpoint aktif.

## Dokumen Sejarah

`FINDING.md`, `PLAN.md`, `AUDIT_REPORT_AND_RECOMMENDATIONS.md`, audit 31 Julai/12 Ogos dan operational drill ialah snapshots. Jangan menukar angka sejarah mereka kepada angka semasa; gunakan banner/status atau rujuk audit terkini.

## Portal Awam Semasa

- `/` — homepage dengan anchor sections Sports, Schedule, Results dan Medal standings.
- `/matches` — halaman awam semua jadual perlawanan dan keputusan terkini.
- `/sports`, `/schedule`, `/results`, `/faculties`, `/venues` dan `/live` — halaman kandungan pertandingan awam.
- `/news`, `/downloads`, `/faq` dan `/about` — halaman maklumat awam tambahan.
- `/contact-us` — halaman hubungan.
- `/login` — login.

`/sports-programme`, `/medal-tally` dan `/schedules` tidak wujud pada production semasa. `GET /results` bukan public results page.

## Maintenance Rules

- Pisahkan repository capability, runtime workspace, production observation dan historical evidence.
- Jangan tandakan item selesai hanya kerana ia wujud dalam `.env.*.example` atau runbook.
- Perubahan architecture memerlukan ADR/architecture update.
- Perubahan functionality memerlukan `CURRENT_STATE.md`, `TODOS.md` dan `CHANGELOG.md`.
- Kekalkan istilah domain: Organization → Session → Tournament → Event, dengan Sport/Category sebagai catalog/relasi; Match → Result.
- Semua tenant read/write paths perlu Policy, tenant scope dan regression tests yang sepadan.
