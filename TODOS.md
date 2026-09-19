# TODOS

## UI/UX Refactor (Fasa 1 — selesai 19 September 2026)

- [x] Buang CSS mati `.public-portal`/`.public-campaign` dan import font Manrope/Noto Sans yang tidak digunakan (`resources/css/app.css`).
- [x] Ganti kelas `slate`/`white` mentah dengan token shadcn pada `Dashboard.tsx` dan `DrawResult/Index.tsx` (`bg-card`, `border-border`, `text-foreground`, `text-muted-foreground`, `bg-primary`).
- [x] Kukuhkan a11y `PublicMobileMenu`: Escape, klik/luar, focus trap + pulih fokus, `aria-haspopup`, import di atas, saiz sentuh 44px.

## UI/UX Refactor (Fasa 2 — selesai 19 September 2026)

- [x] Saiz sentuh lalai 44px pada mobile untuk `Button` shadcn (`min-h-11 min-w-11`, reset di `sm`), nav/logout sidebar app, `LocaleSwitcher`, butang log masuk awam dan tab `/schedule` + `/athletes`.
- [x] Tambah outline `:focus-visible` asas untuk pautan/butang/summary natif yang tiada ring sendiri.

## UI/UX Refactor (Fasa 3 — sebahagian, 19 September 2026)

- [x] Skala tipografi minimum 12px: 84 kejadian `text-[9px]`/`[10px]`/`[11px]` diganti `text-xs` merentas 27 fail.
- [x] `Dashboard.tsx` guna komponen `EmptyState` kongsi (buang definisi tempatan) dan radius kad diselaraskan ke `rounded-xl` (hero `rounded-2xl`).
- [x] Bersihkan semua baki kelas `slate`/`white` mentah ke token tema pada permukaan awam (`Schedule`, `Athletes`, `Athlete`, `Contact`, `Index`, `PublicFixtureCard`, `PublicMatchStatus`, `ParticipantLogo`) dan skrin cetak/app (`Participants`, `ParticipationConfirmations`, `TeamRegistrationForms`); sapuan `slate-` pada `resources/js/**/*.tsx` kini kosong.
- [x] Adopsi `PageHeader` pada `Settings`, `Tournaments`, `Users`, `SportCategories`; baseline e2e/axe 10/10 lulus (desktop + mobile, `APP_LOCALE=en`).
- [x] `PageHeader` ditambah slot `leading`; digunakan pada `Faculty/Dashboard` (logo), `Profile/Edit`, `TeamRegistrationForms/Show`.
- [x] `ConfirmDialog` destrukif digunakan pada `SportCategories` & `Tournaments` (ganti dialog buatan).
- [x] `window.confirm` pada `Sessions` & `Sports` (padam dokumen) diganti `ConfirmDialog` destrukif.
- [ ] Belum: adopsi `PageHeader` pada `Dashboard` (hero tersendiri) dinilai berasingan.

## Public directory UX (selesai 19 September 2026)

- [x] Kad sukan `/sports` kini deep-link ke `/schedule?sport=...`; halaman Schedule membaca penapis `sport`/`category` dari URL semasa muat.
- [x] Tambah filter kategori (chip) dan kiraan hasil yang sentiasa kelihatan pada direktori sukan.
- [x] Dedahkan dokumen peraturan per-sukan (published, scoped kepada session/organization aktif) dalam `sports_catalog` dan papar sebagai pautan PDF/Markdown dalam kad; cache dinaikkan ke `public-portal:v10` dan upload/delete dokumen kini invalidasi cache portal.
- [x] Betulkan ralat TypeScript pre-existing `resources/js/Pages/Events/Index.tsx` yang timbul selepas merge `origin/master`; `PublicPortalTest` 22/22 hijau (492 assertions).
- [x] Selaraskan seeder demo `SAF2026DataSeeder` dengan tarikh rasmi: satu tournament `Sukan Antara Fakulti Ke-20 2026` (13–25 Oktober 2026), Fasa 1/Fasa 2 lama dinyahaktifkan; seeder dummy futsal/bola sepak dikemas kini; `ProductionSeedingTest` 5/5 hijau.
- [x] Betulkan empat kegagalan pre-existing: `RegisterParticipantToEvent` kini hantar `NewEventRegistration` (single + batch), `EventParticipantNotificationService` guna `whereHas('roles')` (tahan role belum wujud), `NotificationController` kira `counts.action_required` sebenar, dan `DashboardTest` guna prop `systemOverview`. Suite PHPUnit kini **511/511** (2,491 assertions).

## Export — Medal Tally (selesai 8 September 2026)

- [x] Export medal tally per-session ke PDF dan XLSX (`exports.medals.pdf`/`exports.medals.excel`, `MedalTallyExport`) dengan tenant scoping (session mesti milik organisasi pemanggil) dan authorization `export-data`; butang ditambah pada halaman admin Rankings.
- [x] Kiraan inventori dikemas kini (`149 routes`); tambah tiga ujian export; suite penuh kini **490/490** hijau.
- [x] Betulkan kegagalan pre-existing `ExampleTest::test_public_shell_is_self_hosted_and_has_basic_search_metadata`: assertion lapuk `assertDontSee('activity-logs.index')` pada guest shell dibuang kerana full Ziggy route map sengaja di-embed (didokumenkan dalam `app.blade.php`) untuk menyokong redirect login Inertia, dengan authorization dikuatkuasakan di server-side.

> **Repository update — 8 September 2026:** Fasa A penambahbaikan aliran kerja Event Participant telah disiapkan (state machine status, batch approve/reject, import CSV/XLSX, conflict validation, withdraw dan restore soft-deleted). Semua 11 test `EventParticipantBatchTest` hijau, suite penuh PHPUnit 486/487 lulus (satu kegagalan adalah `ExampleTest` pre-existing di luar skop), dan CI gates tempatan (inventory, tenant-bypass, typecheck, build/budget) hijau.

## Fasa A — Event Participant workflows (selesai)

- [x] Implement state machine status pendaftaran (`pending → confirmed/rejected/withdrawn/disqualified`, `confirmed → withdrawn/disqualified`, `rejected → confirmed/withdrawn`); setiap peralihan melalui `EventParticipant::canTransitionTo()` yang validated, dengan `notes` wajib untuk penolakan.
- [x] Batch approve/reject pendaftaran (pending/rejected) melalui `event-participants.batch-status` dengan Form Request authorization, tenant scoping dan dialog reject-notes dalam UI workshop.
- [x] Import pukal CSV/XLSX pendaftaran peserta (Maatwebsite) dengan template boleh dimuat turun, laporan validation per-baris serta skip duplicate/unknown-event (import melapor `errors()`/`createdCount()` dan tidak melempar exception merosotkan transaksi `Sheet::import`).
- [x] Conflict detection per peserta (`ParticipantScheduleConflictService`) dipaparkan dalam Index workshop sebagai badge amber + tooltip.
- [x] Withdraw pendaftaran dan restore registration yang di-soft-delete apabila mendaftar semula ke event yang sama.
- [x] Harden `EventParticipantPolicy` dengan helper `hasPermission` defensif yang mengendalikan permohonan tanpa permission row (selesaikan 500 untuk same-org non-admin).
- [x] Ubah suai halaman workshop Event Participants: bulk-select toolbar, butang withdraw, badge konflik, butang/dialog import; `Migrations 66 / routes 147 / testFiles 97` direkod semula dalam `CURRENT_STATE.md`.
- [x] Quality gate tempatan 8 September lulus: 486/487 PHPUnit (satu failure pre-existing ialah `ExampleTest::test_public_shell_is_self_hosted_and_has_basic_search_metadata` daripada commit `eff40b7c2` full-Ziggy-map), Pint, TypeScript, inventory, tenant guard, Vite build/budget.

> Backlog aktif STMS/SAF dikemas kini 21 Ogos 2026 selepas athlete/scorer workflows. Kotak hanya ditanda apabila ada bukti; tindakan production/owner tidak dianggap selesai oleh perubahan kod semata-mata.

## Current Focus (MVP): Release Handoff

### Bukti repository/runtime workspace yang selesai

- [x] Tutup remediation P0 bagi draw, authorization read/index, tenant HTTP/Inertia assertions, ranking strategy, production guard, CSP/font, dependency advisory dan accessibility smoke coverage.
- [x] Audit 17 users/17 role assignments dan tenant relations; proses 32 queued database notifications kepada 0 pending/0 failed.
- [x] Quality gate tempatan 18 Ogos lulus: PHPUnit 439/439 (1,948 assertions), Pint, TypeScript, inventory, tenant guard, build/budget, dependency audits dan Playwright/axe 8/8.
- [x] Connected CI [#112](https://github.com/ahmadzakiabdullah/stms/actions/runs/32097257726) lulus pada `4b04c46`: dependency audit, lint, PHPUnit/inventory, 75.03% PCOV coverage, build dan browser E2E.
- [x] Product owner mengesahkan SAF 2026 ialah 13–25 Oktober 2026, satu tournament, 30 acara, 8 kontinjen dan navigation single-page; rekod pertandingan boleh dikemas kini kemudian jika maklumat rasmi berubah.
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

## Pelan Penambahbaikan Berkeutamaan

Pelan ini bermula selepas release blockers di atas diselesaikan. Keutamaan diberikan kepada kebolehoperasian, keselamatan data dan aliran kerja pertandingan sebelum ciri pasca-MVP.

### Fasa 1 — Operasi dan kebolehpercayaan production

Dokumen repository untuk monitoring matrix, ownership, threshold, escalation, incident response, rollback dan worker/scheduler recovery telah disediakan. Item di bawah kekal terbuka sehingga monitoring luar, named owners dan alert delivery benar-benar diaktifkan serta dibuktikan.

- [ ] Jadualkan backup production terenkripsi dengan retention, pemantauan freshness dan bukti salinan off-host.
- [ ] Automasi isolated restore drill berkala dan rekod RPO/RTO dalam release evidence.
- [ ] Tambah monitoring untuk availability, error rate, request/DB latency, queue depth/oldest job, failed jobs, disk, backup freshness dan sijil.
- [ ] Sediakan owner, threshold, alert delivery dan runbook untuk setiap alert production.
- [ ] Sediakan runbook incident, rollback dan pemulihan worker/scheduler.

### Fasa 2 — Keselamatan dan audit trail

- [x] Standardkan activity audit metadata dengan `actor_id`, `organization_id`, action, subject ID/type dan request/correlation ID apabila berkaitan; metadata disimpan di bawah `properties.audit` dan diuji.
- [x] Redact e-mel, telefon, password, token dan PII context daripada application logs melalui global Monolog processor; recursive redaction diuji.
- [x] Dokumentasikan retention dan kawalan akses application logs; operator control record, access roles, export/deletion procedure and review cadence are defined in `docs/architecture/logging.md`.
- [x] Audit dan tambah rate limiting untuk login, reset-password, email verification serta mutation/export endpoints yang sesuai; password-reset throttling diuji dalam `RateLimitingTest`.
- [x] Jalankan dependency, secret dan security scan secara berkala dalam CI; dependency audit sedia ada dan Gitleaks secret scan kini berada dalam workflow CI.
- [x] Tambah regression tests untuk authorization, tenant isolation, perubahan score dan akses selepas keputusan dikunci; ResultTest meliputi submit, approve, lock, unlock serta edit/delete denial.

### Fasa 3 — Kesediaan operasi pertandingan dan UX admin

- [x] Tambah bulk import peserta, kontinjen dan roster melalui CSV/XLSX dengan preview, validation report dan rollback; dua langkah `participants.import.preview`→`participants.import.confirm`, baris sah dikachekan (token UUID, TTL 30 min), baris dicipta dalam satu DB transaction (all-or-nothing), template di `participants.import.template`, dialog Import di halaman Participants dan 8 ujian feature dalam `ParticipantImportTest`.
- [x] Tambah export jadual, keputusan, ranking dan medal tally ke PDF/XLSX (fixtures/results/rankings + medal tally per-session, semua tenant-scoped dan bergate `export-data`).
- [x] Sediakan print-friendly match sheet dan result sheet; PDF resmi dikeluarkan melalui `exports.matchSheet`/`exports.resultSheet` dengan tenant scoping, authorization `export-data` dan butang print pada baris Result.
- [x] Tambah search, filter, pagination dan empty/error states yang konsisten pada halaman admin utama. Participants, Events, Matches, Results, Sports, Sessions, Tournaments, Users dan Activity Logs kini mempunyai carian/filter server-side, pagination dan empty states.
- [x] Tambah validasi konflik venue, masa, participant dan fixture sebelum jadual diterbitkan; `MatchScheduleConflictValidator` menyekat penciptaan/kemaskini match yang bertindih (venue sama dalam tetingkap 120 minit, atau participant bermain dalam dua match serentak) di `MatchController::store`/`update`, diliputi 6 ujian feature.
- [x] Sokong lock keputusan selepas pengesahan serta approval workflow: `submitted → approved → locked`, policy role-aware, unlock terkawal dan status dipaparkan pada Results workspace.
- [x] Simpan sejarah perubahan score, participant, draw dan status approval yang boleh diaudit; model activity log merekod perubahan fields, draw versions menyimpan snapshot draw, dan Activity Logs memaparkan event/changed fields.

### Fasa 4 — Selepas MVP stabil

- [ ] Nilai live scoring/realtime berdasarkan keperluan operator dan kapasiti production.
- [ ] Rancang REST API `/api/v1` dengan API Resources, authorization, rate limiting dan dokumentasi OpenAPI.
- [ ] Nilai scorer mode PWA/mobile untuk penggunaan di venue dengan rangkaian tidak stabil.
- [ ] Nilai dashboard analytics dan laporan prestasi selepas definisi metrik disahkan.
- [ ] Nilai modul accreditation hanya selepas release MVP stabil.

## Hardening Seterusnya

- [x] Jadikan favicon lookup tenant-explicit untuk guest.
- [x] Pisahkan ranking melalui contract/registry/strategy classes dan simpan validated rule values pada session/tournament.
- [x] Dokumentasikan pengecualian UUID/soft-delete untuk `settings`, `squad_members` dan `draw_versions`.
- [x] Audit Form Request authorization dan GET/read controllers; policy enforcement berada pada controller/gate dengan request-level defense-in-depth bagi target sensitif.
- [x] Tambah meta description, canonical dan sitemap public.
- [x] Hadkan guest Ziggy manifest dan buang global initial prefetch.
- [x] Lengkapkan public refresh success/error status.
- [x] Tambah desktop/mobile keyboard + axe smoke coverage untuk `/`, `/contact-us`, login dan dashboard; feature tests meliputi empty/public data states.
- [x] Kurangkan kerja first paint homepage public dengan `content-visibility` untuk seksyen bawah fold dan lazy/deferred participant logos; Lighthouse selepas deployment masih perlu mengesahkan perubahan LCP.
- [x] Ekstrak query orchestration daripada `DashboardController`, `EventParticipantController` dan `EventController` kepada service khusus tanpa mengubah behavior; 27 targeted tests dan suite penuh hijau.
- [x] Rekod PCOV connected-CI semasa 75.03% statement coverage (4,676/6,232) dan kuatkuasakan ratchet minimum 74.5%.
- [x] Harden Docker/Redis release path melalui Predis, production-like staging Compose, authenticated k6 check ratchet dan validated container health startup.
- [x] Tambah venue di aras Event (boleh disunting di `/events`); perlawanan awam mewarisi venue event apabila tiada venue per-match, dan senarai venue awam menggabungkan sumber event + match.
- [x] Sokong pelbagai venue bagi satu event (`events.venues` JSON, migration `2026_08_20_000002`): dialog Event boleh tambah/buang senarai venue, dialog match menawarkan dropdown venue event (default venue pertama) atau teks bebas, dan portal awam mewarisi venue pertama serta menggabungkan semua venue event dalam senarai awam.

## Product/UX Decisions

- [x] Tambah public Athletes & Teams directory dan profile performance rasmi.
- [x] Tambah scorer events untuk sport `scoring_mode=individual`: roster confirmed, score reconciliation, participant-grouped public display dan score editor UX.
- [x] `/athletes` guna carian/filter server-side + pagination (24 atlet/12 pasukan) dan A–Z rail supaya senarai tidak memanjang apabila peserta bertambah; state kekal dalam URL (`view`, `q`, `sport`, `category`, `letter`, `page`).
- [x] `/athletes` P0 UX: segmented Teams/Athletes dengan kiraan, chip sukan berikon, penapis fakulti + susunan, skeleton/loading & `aria-busy`, empty state boleh tindak ("Clear filters").

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
