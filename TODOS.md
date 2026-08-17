# TODOS

> Backlog aktif STMS/SAF selepas remediation audit 17 Ogos 2026. Kotak hanya ditanda apabila ada bukti; tindakan production/owner tidak dianggap selesai oleh perubahan kod semata-mata.

## Current Focus (MVP): Release Handoff

### Selesai dalam repository/runtime workspace

- [x] Selaraskan draw move/regeneration behavior dan pulihkan PHPUnit penuh.
- [x] Baiki semua isu Pint.
- [x] Kuatkuasakan read authorization pada semua index sensitif.
- [x] Ganti pseudo tenant tests dengan HTTP/Inertia payload assertions.
- [x] Tambah manual URL access matrix bagi enam role aktif.
- [x] Audit users, role assignments dan tenant relations: 17 users/17 assignments, satu super-admin, tiada orphan atau tenant mismatch; credential rotation tidak diperlukan berdasarkan bukti ini.
- [x] Proses 32 queued database notifications; sahkan 0 pending dan 0 failed.
- [x] Kukuhkan production configuration guard untuk Redis, verification, Malaysia timezone, secure session, CSP dan real mail transport.
- [x] Buang Bunny Fonts, self-host font dan sediakan CSP-enforcing code path.
- [x] Naik taraf Guzzle/PSR-7 dan sahkan Composer/npm audit bersih.
- [x] Jalankan quality gate tempatan semasa: PHPUnit 430/430, Pint, TypeScript, build, budget, tenant guard dan Playwright/axe 8/8.
- [x] Pecahkan remediation kepada empat commit logik bagi logo peserta, authorization/draw/ranking, portal/production hardening dan dokumentasi audit.
- [x] Connected CI #101 lulus pada commit `35b9dac`: dependency audit, lint, test, coverage, build dan browser E2E.

### Memerlukan owner/production operator

- [ ] Product owner sahkan SAF 2026 ialah 1–31 Oktober, satu tournament, 30 events dan navigation single-page; betulkan data jika tidak tepat.
- [ ] Konfigurasi mail transport sebenar dan uji reset-password sebelum `EMAIL_VERIFICATION_REQUIRED=true` dihidupkan.
- [ ] Deploy dengan `PRODUCTION_CONFIG_ENFORCE=true`, Redis cache/queue/session, `APP_TIMEZONE=Asia/Kuala_Lumpur`, secure cookie dan CSP enforcing; lakukan smoke test serta pelan session cutover.
- [ ] Lengkapkan contact email, phone dan address production.
- [ ] Ambil backup off-host, deploy release candidate, sahkan smoke test dan cipta release tag.

## Hardening Seterusnya

- [x] Jadikan favicon lookup tenant-explicit untuk guest.
- [x] Pisahkan ranking melalui contract/registry/strategy classes dan simpan validated rule values pada session/tournament.
- [x] Dokumentasikan pengecualian UUID/soft-delete untuk `settings`, `squad_members` dan `draw_versions`.
- [x] Audit Form Request authorization dan GET/read controllers; policy enforcement berada pada controller/gate dengan request-level defense-in-depth bagi target sensitif.
- [x] Tambah meta description, canonical dan sitemap public.
- [x] Hadkan guest Ziggy manifest dan buang global initial prefetch.
- [x] Lengkapkan public refresh success/error status.
- [x] Tambah desktop/mobile keyboard + axe smoke coverage untuk `/`, `/contact-us`, login dan dashboard; feature tests meliputi empty/public data states.
- [ ] Ekstrak baki query orchestration berat dalam `DashboardController`, `EventParticipantController` dan `EventController` secara berperingkat tanpa mengubah behavior.
- [ ] DBA hadkan production principal kepada schema STMS sahaja dan lampirkan grants yang diluluskan pada release evidence.

## Operational Evidence

- [ ] Jalankan authenticated k6 pada staging multi-worker dan capai threshold yang diluluskan.
- [ ] Konfigurasi external uptime/log alert dan bukti penerimaan operator sebenar.
- [ ] Salin actual production backup off-host dan lakukan isolated restore dengan RPO/RTO direkod.
- [ ] Uji reset-password dan mail delivery production end-to-end.
- [ ] Rekod PCOV coverage baseline pada connected CI dan tetapkan ratcheting threshold.
- [ ] Jalankan Playwright/axe terhadap deployment release sebenar selepas deploy; 8/8 local isolated pass ialah pre-deploy evidence sahaja.

## Product/UX Bersyarat

- Tambah public pages berasingan hanya jika product owner memilih IA multi-page.
- Tambah schedule/result filters, print/calendar dan analytics hanya selepas route/data asas stabil.

## Deferred Milestones

- Accreditation
- Live scoring/realtime
- Mobile app
- REST API `/api/v1`
- Advanced analytics dan AI

**Source of truth:** [current state](CURRENT_STATE.md) dan [audit 17 Ogos 2026](docs/audits/2026-08-17-full-project-and-production-audit.md).
