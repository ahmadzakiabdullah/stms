# TODOS

> Backlog aktif STMS/SAF dikemas kini 18 Ogos 2026 selepas remediation audit. Kotak hanya ditanda apabila ada bukti; tindakan production/owner tidak dianggap selesai oleh perubahan kod semata-mata.

## Current Focus (MVP): Release Handoff

### Bukti repository/runtime workspace yang selesai

- [x] Tutup remediation P0 bagi draw, authorization read/index, tenant HTTP/Inertia assertions, ranking strategy, production guard, CSP/font, dependency advisory dan accessibility smoke coverage.
- [x] Audit 17 users/17 role assignments dan tenant relations; proses 32 queued database notifications kepada 0 pending/0 failed.
- [x] Quality gate tempatan 18 Ogos lulus: PHPUnit 439/439 (1,948 assertions), Pint, TypeScript, inventory, tenant guard, build/budget, dependency audits dan Playwright/axe 8/8.
- [x] Connected CI [#112](https://github.com/ahmadzakiabdullah/stms/actions/runs/32097257726) lulus pada `4b04c46`: dependency audit, lint, PHPUnit/inventory, 75.03% PCOV coverage, build dan browser E2E.
- [x] Product owner mengesahkan SAF 2026 ialah 1–31 Oktober 2026, satu tournament, 30 acara, 8 kontinjen dan navigation single-page; rekod pertandingan boleh dikemas kini kemudian jika maklumat rasmi berubah.
- [x] Lengkapkan alamat, e-mel, telefon dan empat pautan media sosial rasmi Pusat Sukan sebagai tetapan tenant yang boleh diedit; data workspace UTeM telah dikemas kini dan cache portal dibersihkan.

### Repository handoff yang selesai

- [x] Review dan commit refactor/documentation 18 Ogos sebagai `e535e4b`; connected CI #110 hijau pada SHA yang sama.
- [x] Sediakan `stms:release-preflight` baca-sahaja dan release evidence template bagi konfigurasi, DB/Redis, mailer, backup freshness, monitoring serta public tenant selectors.
- [x] Tambah halaman awam semua jadual perlawanan dan keputusan di `/matches`; fungsi pengurusan dalaman dipindahkan ke `/manage/matches` dengan navigasi portal, sitemap dan ujian tenant/session scope.

### Release blockers — memerlukan owner/production operator

- [ ] Konfigurasi mail transport sebenar dan buktikan reset-password delivery end-to-end sebelum `EMAIL_VERIFICATION_REQUIRED=true` dihidupkan.
- [ ] DBA hadkan production principal kepada schema STMS sahaja dan lampirkan grants yang diluluskan pada release evidence.
- [x] Ambil actual production-labelled workspace backup, salin off-host dan lakukan isolated MySQL 8 restore; SHA-256, row/upload evidence dan RTO 7.699 saat direkod dalam `docs/testing/2026-08-18-release-drill.md`.
- [x] Jalankan authenticated k6 pada staging multi-worker terasing: 1,150/1,150 checks, 0% HTTP failures dan p95 81.543 ms pada 10 VU/30 saat.
- [x] Konfigurasi GitHub Actions external uptime alert; forced failure membuka dan assign [issue #75](https://github.com/ahmadzakiabdullah/stms/issues/75), kemudian recovery run #32097609744 menutupnya selepas `/up` kembali HTTP 200.
- [ ] Sediakan dan laksanakan session/runtime cutover: `PRODUCTION_CONFIG_ENFORCE=true`, Redis cache/queue/session, `EMAIL_VERIFICATION_REQUIRED=true`, `APP_TIMEZONE=Asia/Kuala_Lumpur`, secure cookie dan CSP enforcing.
- [ ] Deploy commit yang diluluskan, restart worker/scheduler dan jalankan smoke test serta Playwright/axe terhadap deployment sebenar.
- [ ] Cipta annotated release tag hanya selepas semua bukti di atas diluluskan dan direkodkan.

## Hardening Seterusnya

- [x] Jadikan favicon lookup tenant-explicit untuk guest.
- [x] Pisahkan ranking melalui contract/registry/strategy classes dan simpan validated rule values pada session/tournament.
- [x] Dokumentasikan pengecualian UUID/soft-delete untuk `settings`, `squad_members` dan `draw_versions`.
- [x] Audit Form Request authorization dan GET/read controllers; policy enforcement berada pada controller/gate dengan request-level defense-in-depth bagi target sensitif.
- [x] Tambah meta description, canonical dan sitemap public.
- [x] Hadkan guest Ziggy manifest dan buang global initial prefetch.
- [x] Lengkapkan public refresh success/error status.
- [x] Tambah desktop/mobile keyboard + axe smoke coverage untuk `/`, `/contact-us`, login dan dashboard; feature tests meliputi empty/public data states.
- [x] Ekstrak query orchestration daripada `DashboardController`, `EventParticipantController` dan `EventController` kepada service khusus tanpa mengubah behavior; 27 targeted tests dan suite penuh hijau.
- [x] Rekod PCOV connected-CI semasa 75.03% statement coverage (4,676/6,232) dan kuatkuasakan ratchet minimum 74.5%.
- [x] Harden Docker/Redis release path melalui Predis, production-like staging Compose, authenticated k6 check ratchet dan validated container health startup.
- [x] Tambah venue di aras Event (boleh disunting di `/events`); perlawanan awam mewarisi venue event apabila tiada venue per-match, dan senarai venue awam menggabungkan sumber event + match.
- [x] Sokong pelbagai venue bagi satu event (`events.venues` JSON, migration `2026_08_20_000002`): dialog Event boleh tambah/buang senarai venue, dialog match menawarkan dropdown venue event (default venue pertama) atau teks bebas, dan portal awam mewarisi venue pertama serta menggabungkan semua venue event dalam senarai awam.

## Product/UX Decisions

- [x] Kekalkan portal awam homepage single-page sebagai landasan; halaman berasingan (`/sports`, `/schedule`, `/results`, `/faculties`, `/venues`, `/live`, `/news`, `/downloads`, `/faq`, `/about`, `/matches`) ditambah dan memerlukan penilaian change request jika mahu dikembangkan lagi.
- [x] Gabungkan halaman perlawanan awam yang bertindih menjadi satu: `/schedule` ialah satu-satunya halaman jadual/keputusan (tab All/Live/Upcoming/Completed + filter); `/matches`, `/results` dan `/live` redirect 301 ke `/schedule`; nav portal diringkaskan kepada Home/Sports/Schedule/Contact.
- [ ] Post-MVP: nilai schedule/result filters, print/calendar dan analytics selepas release production stabil.

## Deferred Milestones

- Accreditation
- Live scoring/realtime
- Mobile app
- REST API `/api/v1`
- Advanced analytics dan AI

**Source of truth:** [current state](CURRENT_STATE.md) dan [audit 17 Ogos 2026](docs/audits/2026-08-17-full-project-and-production-audit.md).
