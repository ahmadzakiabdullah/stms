# TODOS

> Backlog baharu berdasarkan audit keseluruhan sistem pada 20 September 2026.
> Sumber rujukan: `CURRENT_STATE.md`, `ROADMAP.md`, `docs/architecture/`, `docs/database/` dan working tree semasa.

## Status semasa

- MVP web STMS sudah mempunyai asas Laravel + Inertia/React, multi-tenant, public portal, pengurusan peserta, pasukan, sukan, event, match dan keputusan.
- Production belum `GO`: preflight gagal pada konfigurasi production, database/Redis, mail, backup dan monitoring.
- Fokus backlog ini ialah menutup risiko release dan mengukuhkan MVP. Fasa masa depan seperti accreditation, live scoring, mobile app dan AI kekal ditangguhkan.
- Hierarki domain wajib dikekalkan: `Organization → Session → Tournament → Sport → Event → Match → Result`.
- Keputusan operasi semasa: Redis, SMTP/API mail dan email verification ditahan atas arahan pemilik sistem; item lain diteruskan apabila tidak memerlukan credential/provider sebenar.

## P0 — Release blockers production

**Status: HOLD.** Item P0 yang bertanda `[x]` telah selesai. Baki item `[ ]` ditangguhkan sehingga akses production, Redis, mail provider, backup dan monitoring tersedia.

### Runtime, configuration dan deployment

- [ ] Tetapkan `PRODUCTION_CONFIG_ENFORCE=true` dan luluskan `php artisan stms:release-preflight --json` dalam environment sebenar.
  Nota: preflight production pada 20 September 2026 berjaya berjalan dan mengesahkan database MySQL serta public organization/session lulus. Ia masih `error` kerana runtime menggunakan file session, database queue/cache, log mailer dan email verification `false`; backup berjadual serta health monitoring juga belum tersedia. `AppServiceProvider` akan menolak boot web/worker/Artisan biasa sehingga prerequisite dipenuhi. Command preflight dibenarkan boot dalam audit mode untuk melaporkan blocker.
- [x] Tetapkan timezone production kepada `Asia/Kuala_Lumpur` dan sahkan portal live masih HTTP 200 dengan timestamp +08:00.
- [ ] Gunakan Redis untuk session, cache dan queue; aktifkan secure session cookie.
- [ ] Sediakan worker queue yang diselia process manager serta scheduler yang dipantau.
- [ ] Tukar mailer daripada `log` kepada provider SMTP/API sebenar dan uji penghantaran email.
- [ ] Aktifkan email verification dan lengkapkan end-to-end flow reset password.
- [x] Tukar CSP daripada Report-Only kepada enforcing selepas semua violation diperiksa; kurangkan `unsafe-inline` secara berperingkat. Nonce untuk inline Ziggy/style elements dan polisi font self-hosted kini disediakan; `style-src-attr unsafe-inline` masih diperlukan untuk style attributes dinamik.
- [x] Bina dan deploy semula `public/build` selepas perubahan CSP/style, kemudian luluskan Playwright CSP smoke test pada asset live; CSS NProgress Inertia kini dibundle dalam `app.css` supaya tiada lagi `<style>` runtime tanpa nonce.
- [x] Matikan public registration secara eksplisit melalui `PUBLIC_REGISTRATION_ENABLED=false`.

### Backup, restore dan monitoring

- [ ] Sediakan database principal/schema-only yang mempunyai grant minimum untuk aplikasi.
- [ ] Jadualkan backup terenkripsi dengan retention policy, salinan off-host dan semakan freshness.
- [ ] Lakukan restore drill pada environment terasing dan rekodkan sasaran RPO/RTO.
- [ ] Aktifkan health monitoring dengan token bukan kosong, external alert dan owner yang dinamakan.
- [ ] Dokumentasikan runbook incident, rollback, worker queue, scheduler dan restore.

### Cutover release

- [ ] Freeze release candidate dan semak semua perubahan tracked/untracked sebelum deploy.
- [ ] Jalankan migration, cache clear/warm, worker restart dan scheduler verification dalam urutan cutover.
- [ ] Jalankan smoke test authenticated, cross-tenant negative test, Playwright dan axe pada deployment sebenar.
- [ ] Tag commit release dan simpan bukti preflight, smoke test, backup serta rollback readiness.

## P0 — Keselamatan authorization dan tenant isolation

### Faculty dashboard

- [x] Lindungi route `/faculty/squad*` dengan middleware role dan semakan controller untuk faculty representative yang mempunyai participant; jangan bergantung pada `participant_id` sahaja.
- [x] Tambah negative tests untuk user biasa, user tanpa role, peserta lain dan organisasi lain.
- [x] Pastikan user yang auto-created melalui `ParticipantService::ensureUserLinked` tidak mendapat akses faculty sebelum role diberikan; akses route kini memerlukan role dan linked participant.

### Cross-tenant mutation

- [x] Scope `StoreTournamentRequest.organization_id` kepada organisasi user untuk semua user bukan super-admin; session dan sport kini mesti seorganisasi dengan tournament.
- [x] Jangan benarkan `UpdateParticipantRequest` menukar `organization_id` melalui payload client; validasi `session_id` dalam organisasi yang sama.
- [x] Audit mass assignment untuk `organization_id` dan parent foreign key: service layer kini mengunci tenant pada event, participant, registration, match, result, session, sport, user dan squad; parent relations mesti seorganisasi dan regression tests ditambah.
- [x] Pastikan super-admin mutation memilih tenant sasaran daripada event/match/tournament yang dipilih; operasi match/result tidak lagi bergantung pada `auth()->user()->organization` yang kosong.
- [x] Tambah invariant validation bahawa tournament/session/sport dan participant/session berada dalam organisasi yang sama.
- [x] Tambah HTTP tests untuk pola tenant A → tenant B dan payload isolation pada tournament/participant mutation.

### Public portal, activity log dan dokumen

- [x] Pilih session awam aktif terkini dalam `PUBLIC_ORG_SLUG`; pertukaran session tidak lagi memerlukan `PUBLIC_SESSION_SLUG`.
- [x] Selaraskan release preflight supaya `PUBLIC_ORG_SLUG` wujud dan mempunyai sekurang-kurangnya satu session aktif.
- [x] Tapis activity log tenant menggunakan `properties.audit.organization_id` untuk system activity; rekod tanpa causer yang tiada audit organization kini tidak dipaparkan kepada tenant.
- [x] Validasi session/sport pada upload dan select dokumen terhadap organisasi yang sedang aktif.
- [x] Simpan dan deliver dokumen mengikut partition tenant/session yang konsisten; `available`, upload dan select kini menggunakan canonical path tenant/session serta path legacy yang masih selamat.
- [x] Tambah tests untuk public session selection, null-causer activity log, dokumen organisasi lain dan shared-file path traversal/session isolation.

### User model dan schema

- [x] Jadikan `users.is_active` field rasmi: migration baharu, fillable/cast, factory, request/service, toggle UI dan regression test telah diselaraskan; akaun inactive ditolak semasa log masuk.
- [x] Jalankan migration incremental production (`php artisan migrate --force`) dan `php artisan optimize:clear`; smoke test login dummy kembali ke `/login` dengan HTTP 302 tanpa lagi 500 `is_active`.

## P1 — Quality gates dan dokumentasi

- [x] Reconcile inventory sebenar: 173 routes, 71 migrations, 42 controllers, 47 Inertia pages dan 104 test files; `npm run check:inventory` lulus pada runner yang boleh boot Laravel.
- [x] Kemas kini `CURRENT_STATE.md`, `README.md`, `docs/architecture/system-overview.md` dan inventory checks supaya tidak lagi menunjukkan angka lama; angka schema/frontend turut diselaraskan kepada 71 migrations dan 47 pages.
- [x] Selaraskan arahan Composer production mengikut subcommand (`install --optimize-autoloader`, `dump-autoload --optimize`) dan tambah recovery langkah untuk stale package metadata/provider.
- [x] Review working tree: semakan 22 September bermula dengan 44 fail tracked diubah dan 15 fail baharu, termasuk public portal/localization, package font, concurrency dan asynchronous transfer. Pembetulan authorization/queue, query budget dan regression tests ditambah; perubahan disediakan sebagai commit mengikut skop untuk connected CI calon baharu.
- [x] Pulihkan dependency development lengkap supaya PHPUnit dan build native boleh dijalankan dalam environment CI yang konsisten. `composer install` dengan `require-dev` dipasang pada runner lokal terasing 22 September; PHPUnit 563/563 (2,791 assertions), Pint, TypeScript, tenant-bypass, Vite build/budget dan audit dependency lulus. Vendor runtime network workspace dikekalkan.
- [x] Jadikan gate release wajib: `php artisan test`, Pint, typecheck, inventory, tenant-bypass check, production build, budget, E2E, `composer audit` dan `npm audit`. Aggregator `Required quality gate` dan GitHub branch protection telah disahkan aktif; run `35555960111` lulus semua job dan PR #139 telah merged ke `master` (`4689e8e1`).
- [x] Ukur coverage daripada commit yang sama dan kekalkan sekurang-kurangnya baseline 74.5% sebelum menambah feature baharu. Artifact PCOV daripada master commit `7bddf3c8` (CI run `35556552952`) merekod **76.76% statement coverage (6,062/7,897)** dan lulus ratchet minimum 74.5%.
- [ ] Tulis Feature/Unit tests untuk setiap item P0 sebelum menandakan item selesai.

## P1 — Reliability, performance dan operability

- [x] Tetapkan metrics, threshold, owner dan escalation untuk error rate, latency, queue lag, failed jobs, DB saturation, Redis dan disk. Kontrak repository kini didokumenkan dalam `docs/architecture/monitoring.md`; pengaktifan provider, named owner dan alert delivery kekal bergantung pada P0 production.
- [ ] Tetapkan query budget untuk public pages dan admin tables; selesaikan N+1 serta index yang hilang berdasarkan profiling sebenar. Budget dan regression tests kini meliputi dashboard, public homepage, public schedule, Events, Results, Registrations dan Reports index; PHPUnit kini lulus pada SQLite terasing selepas query katalog awam berulang dikurangkan; profiling query plan MySQL sebenar masih diperlukan.
- [ ] Ukur dan tetapkan sasaran LCP, INP dan CLS untuk public portal pada mobile. Sasaran p75 kini didokumenkan sebagai LCP ≤ 2.5s, INP ≤ 200ms dan CLS ≤ 0.1; pengukuran sebenar masih diperlukan.
- [x] Dokumentasikan cache invalidation untuk session, schedule, results, ranking, documents dan localization dalam `docs/architecture/caching.md`, termasuk scope tenant/session dan trigger mutation.
- [ ] Pindahkan export/import besar kepada queue dengan progress, retry, idempotency dan failure report. Backend queue contract, tenant-scoped tracker, status/download endpoints dan queue routes kini tersedia dalam `DataTransfer`; policy requester, tenant queue middleware, file partition, idempotency serialization, overlap lock dan connection khusus telah diuji. Integrasi UI polling serta production worker/Redis evidence masih diperlukan.
- [ ] Uji concurrency untuk draw, result entry, bulk import, correction dan conflict resolution. Deterministic race-regression tests ditambah dalam `tests/Feature/ConcurrencyRegressionTest.php`; regression tests lulus dalam suite lokal 563/563. Multi-worker staging run untuk bukti concurrency sebenar masih diperlukan.

## P1 — Public UI/UX dan accessibility

- [x] Lengkapkan navigasi mobile/tablet supaya Competition dan Information tidak hilang berbanding desktop; susunan awam diseragamkan dengan Information selepas Home, submenu Information kini hanya memaparkan halaman maklumat SAF yang aktif dan Contact kekal sebagai item terakhir.
- [x] Tambah halaman Maklumat Am awam di bawah submenu Information dengan syarat kelayakan peserta dan peraturan minimum penyertaan pasukan.
- [x] Tambah halaman Jawatankuasa Induk awam di bawah submenu Information dengan data jawatankuasa staf, pegawai kanan universiti, wakil fakulti dan ahli jawatankuasa.
- [x] Tambah halaman Jawatankuasa Pelaksana awam di bawah submenu Information dengan data jawatankuasa pelajar dan tugasan sukarelawan.
- [x] Tambah halaman Pengerusi Permainan awam di bawah submenu Information dengan senarai pengerusi kelab serta keperluan teknikal dan pengadil bagi setiap permainan.
- [x] Tambah halaman Tarikh Penting awam di bawah submenu Information dengan jadual mesyuarat, persediaan, pendaftaran dan acara utama SAF.
- [x] Tambah seksyen Sekretariat di halaman Contact dengan senarai 23 penyelaras staf dan pengerusi acara sukan.
- [x] Paparkan quota atlet lelaki/perempuan, pegawai dan venue acara pada halaman Sports berdasarkan data `SportCategory`/`Event` sedia ada.
- [x] Selaraskan semua card public dengan gaya kad Venue melalui utility bersama `public-card`.
- [x] Lengkapkan localization EN/MS untuk halaman maklumat awam, data jawatankuasa, pengerusi permainan, tarikh penting dan seksyen Sekretariat, termasuk menu Home → Utama, Schedule & Results → Jadual & Keputusan, Contact → Hubungi serta terjemahan submenu Information.
- [x] Kemas tipografi public: gunakan Geist untuk body/UI/heading dan Barlow Condensed 700/800 secara terhad untuk nombor paparan serta aksen display.
- [x] Redesign public Venues directory dengan kad venue yang boleh membuka jadual mengikut venue.
- [x] Redesign homepage public supaya state sebelum jadual diterbitkan mempunyai CTA jelas ke sukan, venue dan atlet.
- [x] Sediakan state loading, empty, stale, error dan permission untuk semua public routes. `PublicLayout` mengumumkan loading Inertia dan `aria-busy`; homepage serta semua halaman public mempunyai error state dengan retry, empty/stale/loading state yang relevan; permission state kekal backend HTTP 403/404 kerana route ini anonymous by design.
- [x] Gunakan komponen shadcn untuk filter, input, select, tabs, pagination, alert dan accordion secara konsisten. Public filter/search controls, athlete tabs/pagination, FAQ/roster disclosure, error alert dan refresh actions kini menggunakan primitive shared di `components/ui`.
- [x] Jalankan keyboard navigation, focus management, screen-reader labels dan axe pada semua public routes; production smoke Playwright lulus **6/6** pada 21 September 2026; local suite 22 September meliputi 24 desktop/mobile cases (22 lulus full run, dua assertion localization dibetulkan dan rerun lulus 2/2).
- [ ] Uji viewport mobile/tablet/desktop, zoom 200%, contrast, reduced motion dan touch target minimum 44px. Smoke production 1/1 lulus untuk viewport 390/768/1440px, reduced motion dan touch target; browser zoom 200% sebenar masih memerlukan verifikasi manual. **Ditangguhkan:** audit zoom memerlukan browser automation atau local app yang boleh memberi respons penuh.
- [x] Tambah metadata SEO yang konsisten: title, description, canonical, Open Graph/Twitter, sitemap lengkap dan robots policy; tambah E2E regression check.
- [x] Semak alt text, external links, image loading dan fallback apabila asset atau public data gagal. `SafeImage` kini menyediakan fallback untuk branding, banner dan ikon sport; `ParticipantLogo` kembali kepada initials apabila logo gagal; external links menetapkan `noopener noreferrer`; regression E2E ditambah. Verifikasi browser zoom 200% masih manual dan kekal pada item viewport di atas.

## P1 — Authenticated UI/UX

- [x] Sidebar dashboard menggunakan menu rata tanpa dropdown supaya semua menu yang dibenarkan kelihatan terus.
- [x] Samakan gap, padding dan tinggi item sidebar antara Dashboard dan semua authenticated pages.
- [x] Compactkan sidebar secara konsisten selepas spacing standard didapati terlalu besar.
- [x] Kurangkan lagi whitespace sidebar tanpa menurunkan target klik menu di bawah 44px.
- [x] Redesign dashboard mengikut workspace role: administration, competition operations dan reporting.

## P2 — Cadangan tambah baik produk

- [x] Tambah calendar view dan print-friendly public schedule; export fixtures/results sudah wujud, dan `/schedule` kini ada toggle List/Calendar serta aksi print.
- [ ] Tambah bulk actions dengan preview, permission, audit trail dan undo/rollback apabila sesuai; sebahagian import/batch status sudah ada, tetapi kontrak bulk action belum seragam.
- [x] Matangkan correction workflow keputusan: state submit/approve/lock/unlock sudah wujud, dan approved correction/unlock kini memerlukan reason yang direkod dalam audit log.
- [x] Sediakan operations dashboard untuk queue, failed jobs, data freshness, active sessions dan incident signal; Reports kini memaparkan monitor operasi repo, manakala bukti runtime production kekal P0 berasingan.
- [ ] Luaskan configurability format pertandingan, scoring, ranking dan tie-break; ranking MVP sudah data-driven, tetapi format/scoring arbitrary masih terhad kepada service semasa.
- [x] Tambah data quality checks untuk duplicate peserta, missing parent relation, orphan result dan invalid timeline.
- [ ] Tambah report comparison, export governance, retention/archive policy dan data ownership yang jelas; audit log asas wujud tetapi belum cukup untuk governance produk.
- [ ] Selepas MVP stabil, nilai REST API versioning, mobile/offline workflow, realtime updates, accreditation dan analytics berdasarkan keperluan sebenar.

## Definition of Done untuk setiap item berisiko tinggi

1. Kod, migration dan authorization disemak untuk tenant isolation.
2. Policy, Form Request, Service/Action dan regression tests lengkap.
3. Semua quality gates lulus pada commit yang sama.
4. Dokumentasi, ADR atau `CHANGELOG.md` dikemas kini apabila kontrak/architecture berubah.
5. Bukti deployment sebenar tersedia; contoh konfigurasi sahaja tidak dikira sebagai selesai.
