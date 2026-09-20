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

### Runtime, configuration dan deployment

- [ ] Tetapkan `PRODUCTION_CONFIG_ENFORCE=true` dan luluskan `php artisan stms:release-preflight --json` dalam environment sebenar.
- [x] Tetapkan timezone production kepada `Asia/Kuala_Lumpur` dan sahkan portal live masih HTTP 200 dengan timestamp +08:00.
- [ ] Gunakan Redis untuk session, cache dan queue; aktifkan secure session cookie.
- [ ] Sediakan worker queue yang diselia process manager serta scheduler yang dipantau.
- [ ] Tukar mailer daripada `log` kepada provider SMTP/API sebenar dan uji penghantaran email.
- [ ] Aktifkan email verification dan lengkapkan end-to-end flow reset password.
- [ ] Tukar CSP daripada Report-Only kepada enforcing selepas semua violation diperiksa; kurangkan `unsafe-inline` secara berperingkat. Nonce untuk inline Ziggy dan polisi font self-hosted kini disediakan; `style-src unsafe-inline` masih menunggu refactor style runtime.
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
- [ ] Jalankan fresh migration dan semak schema production supaya model tidak merujuk kolum yang tiada.

## P1 — Quality gates dan dokumentasi

- [x] Reconcile inventory sebenar: 162 routes, 70 migrations, 41 controllers, 43 Inertia pages dan 100 test files; `npm run check:inventory` lulus.
- [x] Kemas kini `CURRENT_STATE.md`, `README.md`, `docs/architecture/system-overview.md` dan inventory checks supaya tidak lagi menunjukkan angka lama; `docs/database/schema.md` tiada matriks inventori untuk dikemas kini.
- [x] Selaraskan arahan Composer production mengikut subcommand (`install --optimize-autoloader`, `dump-autoload --optimize`) dan tambah recovery langkah untuk stale package metadata/provider.
- [ ] Review working tree dan asingkan/commit perubahan UI atau package yang tidak berkaitan dengan release ini.
- [ ] Pulihkan dependency development lengkap supaya PHPUnit dan build native boleh dijalankan dalam environment CI yang konsisten.
- [ ] Jadikan gate release wajib: `php artisan test`, Pint, typecheck, inventory, tenant-bypass check, production build, budget, E2E, `composer audit` dan `npm audit`.
- [ ] Ukur coverage daripada commit yang sama dan kekalkan sekurang-kurangnya baseline 74.5% sebelum menambah feature baharu.
- [ ] Tulis Feature/Unit tests untuk setiap item P0 sebelum menandakan item selesai.

## P1 — Reliability, performance dan operability

- [ ] Tetapkan metrics, threshold, owner dan escalation untuk error rate, latency, queue lag, failed jobs, DB saturation, Redis dan disk.
- [ ] Tetapkan query budget untuk public pages dan admin tables; selesaikan N+1 serta index yang hilang berdasarkan profiling sebenar.
- [ ] Ukur dan tetapkan sasaran LCP, INP dan CLS untuk public portal pada mobile.
- [ ] Dokumentasikan cache invalidation untuk session, schedule, results, ranking, documents dan localization.
- [ ] Pindahkan export/import besar kepada queue dengan progress, retry, idempotency dan failure report.
- [ ] Uji concurrency untuk draw, result entry, bulk import, correction dan conflict resolution.

## P1 — Public UI/UX dan accessibility

- [ ] Lengkapkan navigasi mobile/tablet supaya Competition dan Information tidak hilang berbanding desktop.
- [ ] Sediakan state loading, empty, stale, error dan permission untuk semua public routes.
- [ ] Gunakan komponen shadcn untuk filter, input, select, tabs, pagination, alert dan accordion secara konsisten.
- [ ] Jalankan keyboard navigation, focus management, screen-reader labels dan axe pada semua public routes.
- [ ] Uji viewport mobile/tablet/desktop, zoom 200%, contrast, reduced motion dan touch target minimum 44px.
- [ ] Tambah metadata SEO yang konsisten: title, description, canonical, Open Graph dan sitemap/robots policy.
- [ ] Semak alt text, external links, image loading dan fallback apabila asset atau public data gagal.

## P2 — Cadangan tambah baik produk

- [ ] Tambah calendar view, print-friendly schedule dan export schedule/results yang mesra operasi.
- [ ] Tambah bulk actions dengan preview, permission, audit trail dan undo/rollback apabila sesuai.
- [ ] Wujudkan correction workflow untuk keputusan: draft → review → publish, termasuk reason dan audit log.
- [ ] Sediakan operations dashboard untuk queue, failed jobs, data freshness, active sessions dan incident signal.
- [ ] Jadikan format pertandingan, scoring, ranking dan tie-break configurable; jangan hardcode peraturan sukan.
- [ ] Tambah data quality checks untuk duplicate peserta, missing parent relation, orphan result dan invalid timeline.
- [ ] Tambah report comparison, export governance, retention/archive policy dan data ownership yang jelas.
- [ ] Selepas MVP stabil, nilai REST API versioning, mobile/offline workflow, realtime updates, accreditation dan analytics berdasarkan keperluan sebenar.

## Definition of Done untuk setiap item berisiko tinggi

1. Kod, migration dan authorization disemak untuk tenant isolation.
2. Policy, Form Request, Service/Action dan regression tests lengkap.
3. Semua quality gates lulus pada commit yang sama.
4. Dokumentasi, ADR atau `CHANGELOG.md` dikemas kini apabila kontrak/architecture berubah.
5. Bukti deployment sebenar tersedia; contoh konfigurasi sahaja tidak dikira sebagai selesai.
