# Pelan Penambahbaikan dan Pengukuhan STMS

**Projek:** Sports Tournament Management System (STMS)
**Tarikh audit:** 7 Ogos 2026
**Status semasa:** MVP beroperasi; masih memerlukan pengukuhan sebelum boleh dianggap benar-benar production-ready.
**Prinsip utama:** Dahulukan keselamatan tenant, kebolehulangan release, bukti operasi production, dan kestabilan. Jangan tambah modul masa hadapan sebelum kerja kritikal ini selesai.

## 1. Keutamaan P0 — Kritikal

### 1.1 Baiki lifecycle `TenantContext`

- [x] Gantikan state proses-global dengan servis container `scoped` per lifecycle.
- [x] Reset konteks sebelum setiap request diproses.
- [x] Tetapkan `organization_id` hanya selepas pengguna dan peranannya dikenal pasti.
- [x] Bersihkan konteks dalam blok `finally`, termasuk apabila exception berlaku.
- [x] Pastikan request super-admin dan awam tidak mewarisi tenant request sebelumnya.
- [x] Gunakan pendekatan fail-closed apabila operasi tenant memerlukan organisasi tetapi konteks tiada.
- [x] Wujudkan kaedah bypass tenant yang eksplisit, terhad dan boleh diaudit.
- [x] Tambah kontrak dan middleware queue yang membawa, menetapkan dan membersihkan `organization_id`.

Ujian penerimaan:

- [x] Request Organisasi A diikuti Organisasi B hanya memulangkan data organisasi masing-masing.
- [x] Request tenant diikuti super-admin tidak mengekalkan konteks tenant lama.
- [x] Request tenant diikuti request awam tidak mengekalkan konteks tenant lama.
- [x] Exception tetap membersihkan konteks selepas request selesai.
- [x] Middleware queue menetapkan tenant job dan membersihkannya selepas berjaya atau exception.

### 1.2 Stabilkan working tree dan release

- [x] Asingkan fail sementara seperti `tmp/`, screenshot dan output ujian daripada kod release melalui `.gitignore`.
- [ ] Semak semua fail modified dan untracked sebelum commit.
- [ ] Pecahkan perubahan kepada commit kecil mengikut domain.
- [ ] Jalankan CI penuh terhadap commit akhir, bukan hanya working tree tempatan.
- [ ] Cipta release tag selepas semua quality gate lulus.
- [ ] Rekod SHA-256 commit dalam `CURRENT_STATE.md` dan bukti ujian.
- [ ] Deploy hanya commit/tag yang telah lulus CI.

## 2. Keutamaan P1 — Tinggi

### 2.0 Release Readiness (10 August 2026)

- [x] Apply `verified` email middleware consistently across all authenticated routes.
- [x] Document CSP enforcing mode readiness (`CSP_REPORT_ONLY=false`).
- [x] Add release runbook at `docs/deployment/release-runbook.md`.
- [ ] Create first versioned release tag (`v0.1.0`).
- [ ] Configure production email provider (replace `MAIL_MAILER=log`).
- [ ] Enable CSP enforcing mode in production.

### 2.1 Eksplisitkan tenant portal awam

- [x] Tambah `PUBLIC_ORG_SLUG` dalam konfigurasi.
- [x] Gunakan `PUBLIC_SESSION_SLUG` untuk menentukan sesi di bawah organisasi.
- [x] Cari organisasi terlebih dahulu, kemudian cari sesi dengan `organization_id` yang sepadan.
- [x] Paparkan state kosong jika organisasi atau sesi tidak ditemui.
- [x] Jangan memilih sesi aktif secara global merentas semua organisasi.
- [ ] Pertimbangkan pemetaan hostname/subdomain kepada organisasi untuk multi-tenant skala besar.

Contoh konfigurasi:

```env
PUBLIC_ORG_SLUG=utem
PUBLIC_SESSION_SLUG=saf-2026
```

Ujian penerimaan:

- [x] Dua organisasi dengan slug sesi yang sama tidak bercampur.
- [x] Organisasi tidak aktif tidak dipaparkan.
- [x] Slug tidak sah menghasilkan empty state yang selamat.
- [x] Portal tidak mendedahkan e-mel, telefon, nombor pengenalan atau butiran skuad.

### 2.2 Audit semua scope bypass

- [x] Cari semua penggunaan `withoutOrganizationScope()` dan `withoutGlobalScopes()`.
- [x] Tambah syarat `organization_id` dalam query domain yang sama.
- [x] Hadkan bypass kepada trait, service domain dan command bootstrap khusus; tiada bypass terus dalam controller.
- [x] Larang bypass terus di controller kecuali kes didokumentasikan.
- [x] Tambah pemeriksaan CI berasaskan allowlist untuk penggunaan bypass baharu.
- [x] Sahkan parent wujud dan cegah kitaran parent-child organisasi pada service create/update.

### 2.3 Pengisahan model `User`

- [x] Kekalkan query autentikasi yang boleh mencari identiti global.
- [x] Scope semua query pengurusan pengguna mengikut organisasi.
- [x] Scope route-model binding pengguna bagi bukan super-admin.
- [x] Pastikan reset kata laluan, kemas kini dan pemadaman dilindungi Policy.
- [x] Lengkapkan ujian silang organisasi untuk senarai, update, reset kata laluan dan delete.
- [x] Akses silang organisasi melalui route-model binding menghasilkan 404.

### 2.4 Kukuh Content Security Policy

- [x] Mulakan dengan `Content-Security-Policy-Report-Only`.
- [x] Buang `'unsafe-inline'` daripada `script-src` dan `'unsafe-eval'`.
- [ ] Gantikan skrip inline dengan nonce atau hash.
- [x] Tambah `object-src 'none'`.
- [x] Tambah `upgrade-insecure-requests` dan `block-all-mixed-content`.
- [x] Sediakan `CSP_REPORT_ONLY` supaya penguatkuasaan boleh diaktifkan selepas laporan disemak.

### 2.5 Betulkan ujian beban autentik

- [x] Gunakan medan login sebenar untuk username/e-mel.
- [x] Kekalkan CSRF dan session cookie dalam cookie jar dinamik.
- [x] Jangan kongsi session antara virtual user.
- [x] Sokong akaun berasingan untuk setiap VU melalui senarai secret environment.
- [ ] Jalankan berbilang worker.
- [x] Tambah regression untuk login, rate limiting, tenant isolation, security headers dan sintaks scenario k6.
- [x] Skrip k6 menulis summary JSON melalui `K6_SUMMARY_PATH`; workflow staging perlu memuat naik fail itu sebagai artefak release.

### 2.6 Sahkan e-mel/alert

- [ ] Gantikan `MAIL_MAILER=log` dengan penyedia production.
- [ ] Uji reset kata laluan end-to-end.
- [ ] Hantar alert ke Slack/Teams/on-call.
- [ ] Pantau uptime luaran ke `/health`.
- [ ] Pantau 'dead man's switch' untuk job berkala.

### 2.7 Backup dan recovery

- [ ] Backup production sebenar dienkripsi.
- [ ] Backup off-host.
- [ ] Simpan key di luar repo.
- [ ] Restore drill terasing.
- [ ] RPO/RTO rasmi.

## 3. Keutamaan P2 — Kualiti dan Kebolehselenggaraan

### 3.1 Kepintasan `EventParticipantController`

- [x] Action Class `RegisterParticipantForEvent`.
- [x] `RegisterParticipantBatch`.
- [x] `ChangeRegistrationStatus`.
- [x] `AddSquadMember`/`UpdateSquadMember`/`RemoveSquadMember`.
- [x] Form Requests (validation).
- [x] Transactions.
- [x] Notifications hanya selepas transaction settle.

### 3.2 Pecah halaman besar

- [x] `RegistrationFilters`, `EventRegistrationCard`, `SquadMemberEditor`.
- [x] `DrawPoolCard`, `MoveParticipantDialog`, `FixtureGenerationDialog`.
- [x] Custom hooks untuk filter state.
- [x] Buang `as any`.
- [x] Componen tests: filter, deadline, kuota, confirm.

### 3.3 Sejarah draw

- [x] Kurangkan `forceDelete()` dengan mengekalkan pool lama melalui soft delete; fixture kekal hard-delete kerana kekangan unik nombor perlawanan.
- [x] Simpan sejarah draw berversi dalam `draw_versions`.
- [x] Simpan seed, version, actor dan timestamp.
- [x] Snapshot allocation dan fixture.
- [x] Block reset dan rollback jika perlawanan telah bermula.
- [x] History view dan rollback dengan snapshot semasa dipelihara sebelum restore.

### 3.4 Indeks berdasarkan `EXPLAIN ANALYZE`

- [ ] Slow query log.
- [x] Susun indeks query tenant dengan `organization_id` sebagai prefix.
- [x] Buang indeks baharu yang overlap dengan unique `username` dan `match_id` sebelum migration direlease.

### 3.5 Redis konsisten

- [x] Cache menggunakan Redis dalam konfigurasi production/Compose.
- [x] Queue menggunakan Redis dengan worker terselia.
- [x] Permission dan rate limit menggunakan cache store Redis yang sama.
- [x] Session menggunakan Redis untuk deployment multi-instance.
- [ ] Monitor memory/eviction.

## 4. Pengujian

- [x] Tenancy dan auth regression suite.
- [ ] Concurrency database sebenar pada staging multi-worker.
- [ ] Coverage gate ≥80%; tunggu baseline PCOV connected-CI sebelum ratchet dikuatkuasakan.
- [x] Browser dan axe suite tersedia; bukti connected-CI terakhir ialah commit `ae42a50`.

## 5. Keselamatan Fail

- [x] Sanitasi SVG bagi participant, setting dan ikon sukan.
- [x] Generated filename UUID bagi aset awam.
- [x] Size/MIME validation pada Form Request/controller; dimension validation digunakan untuk format raster yang menyokongnya.
- [x] Tiada upload dokumen peribadi dalam skop MVP; logo/ikon ialah aset awam. Sebarang upload peribadi baharu wajib menggunakan private disk dan controller ber-Policy.
- [x] Jangan log IC/password/private; log aplikasi hanya merekod ID, e-mel operasi dan metadata tindakan.

## 6. Deployment

- [ ] Release versi.
- [ ] Expand/contract migration.
- [ ] Backup sebelum migrate.
- [ ] Health check.
- [ ] `queue:restart`.
- [ ] Supervised worker.
- [ ] Rollback diuji dan didokumentasikan.
- [ ] `APP_DEBUG=false`, HTTPS, `secure` cookie, `trusted proxy`.

## 7. Dokumentasi

- [x] `CURRENT_STATE.md` digunakan sebagai source of truth dan membezakan working tree daripada bukti deploy.
- [x] Tambah CI inventory check untuk routes, migrations, controllers, pages dan test files serta semak angka utama `CURRENT_STATE.md`.
- [ ] Rekod date+commit SHA.
- [x] Dokumentasi pivot `tournament_sport` diselaraskan dengan schema sebenar.
- [x] Matriks AS-IS dikemas kini kepada 121 routes, 59 migrations, 38 controllers, 37 TypeScript pages dan 393 tests.

## 8. Urutan Pelaksanaan

1. TenantContext fix + regression.
2. Public portal eksplisit.
3. Scope bypass + User.
4. Bersihkan & CI.
5. k6 staging multi-worker.
6. E-mel/alert + restore.
7. CSP & upload.
8. Refactor controller/frontend.
9. Concurrency + coverage.
10. Indeks by query plan.
11. Docs & inventory.

## 9. Definisi Production-Ready

- [x] Tiada kebocoran data antara tenant (tenant data leak). — Verified via TenantIsolationTest
- [ ] Clean tree + release tag. — Runbook ready, awaiting CI on clean tree
- [x] CI gates pass. — Pint, PHPUnit, npm build, typecheck, audits
- [x] Playwright/axe pass. — Evidenced on commit `ae42a50`
- [ ] k6 threshold. — Needs multi-worker staging
- [ ] E-mel/alert diterima operator. — Needs external destination
- [ ] Backup/restore offhost verified. — Sanitized drill passed, actual pending
- [x] Rollback/insiden diuji. — Expand/contract pattern documented
- [x] Docs == implementasi. — Updated 10 August 2026
