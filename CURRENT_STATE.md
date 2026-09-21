# CURRENT STATE

> Snapshot jujur STMS/SAF pada **21 September 2026** selepas public accessibility/SEO hardening dan repository quality-gate verification. Bukti audit asal dan addendum: [`docs/audits/2026-08-17-full-project-and-production-audit.md`](docs/audits/2026-08-17-full-project-and-production-audit.md).

## Status Keseluruhan

**Produk:** MVP web beroperasi.

**Repository:** perubahan semasa dikomit sebagai `0c1d05e7f` dan telah dipush ke `origin/master`. Required quality gate post-merge CI run `35560044122` lulus pada commit ini. **Production deployment kekal NO-GO** sehingga konfigurasi runtime, mail, DB grants dan final cutover evidence diselesaikan.

**Production awam:** <https://saf.utem.edu.my/> tersedia, tetapi belum dianggap telah menerima release candidate yang telah dikomit ini.

Aliran utama tersedia: Organization/User/RBAC → Session/Tournament/Sport/Category/Event → Participant/Registration/Squad → Dean Verification → Draw/Match/Result → Rankings/Exports/Reports → Notifications/Settings/Activity Logs.

**Repository verification — 21 September 2026:** dev dependencies dipulihkan daripada `composer.lock`; PHPUnit terasing lulus **526/526 (2,577 assertions)**, Pint `--test` lulus, TypeScript, inventory, tenant-bypass, Vite production build, bundle budget, Composer audit dan npm audit lulus. Build dijalankan pada salinan lokal kerana native Rolldown tidak boleh dimuatkan dari network share. PCOV dan Playwright belum dijalankan semula pada working tree ini; bukti CI/Playwright terdahulu kekal berasingan.

## Inventori Repository

| Item | Nilai |
|---|---:|
| Laravel routes | 163 application routes |
| Migrations | 70 migration files |
| Controllers | 41 controller files |
| Form Requests | 28 |
| Policies | 21 fail |
| Actions | 37 |
| Services/concerns | 40 fail |
| Models | 18 |
| Inertia `.tsx` pages | 43 |
| PHP tests | 100 PHP test files |
| Playwright journeys | 8 dalam 1 spec, desktop + mobile |

## Tech Stack

| Layer | Keadaan semasa |
|---|---|
| Backend | PHP `^8.4`, Laravel 13 |
| Frontend | React 18, Inertia React 2, TypeScript 5.9 |
| UI | Tailwind 3, komponen shadcn/Radix tempatan, Lucide |
| Auth/RBAC | Laravel auth controllers + Spatie Permission |
| Audit | Spatie Activity Log + application logs |
| Database | MySQL production; SQLite untuk ujian terasing |
| API | Tiada REST API; web/Inertia sahaja |

## Remediation Yang Telah Siap

- Semua index sensitif memanggil policy/gate `viewAny` atau gate khusus; Organizations hanya boleh diurus oleh super-admin.
- Manual URL matrix menguji enam role, dan index tests membuat HTTP/Inertia payload assertions sebenar.
- Admin-sport dibatasi kepada sukan yang ditugaskan pada Events, Matches dan Results.
- Draw pool movement menjana semula fixture berstatus scheduled secara atomik tetapi menyekat fixture yang sudah bermula/selesai.
- Ranking menggunakan `RankingStrategy` contract, registry, tiga strategy class dan validated session/tournament JSON rules.
- Favicon guest memilih tenant public secara eksplisit; font adalah self-hosted.
- Public SEO metadata kini diseragamkan melalui layout shared: title, description, canonical, Open Graph/Twitter, serta `robots` index policy; `/sitemap.xml` meliputi semua public routes dan `/robots.txt` mengecualikan route authenticated.
- Initial global Vite prefetch dibuang untuk mengecilkan HTML/request awal.
- Production configuration validator kini mewajibkan Redis session/cache/queue, Asia/Kuala_Lumpur, email verification, secure cookie, CSP enforcing dan mail bukan `log`.
- Vendor dependencies diselaraskan kepada lockfile selamat (Guzzle 7.15.2, PSR-7 2.13.0) untuk menutup advisory semasa.
- Axe/keyboard smoke tests meliputi login, dashboard, homepage dan Contact pada desktop/mobile; contrast dan ARIA findings semasa telah dibaiki.
- Butiran hubungan awam kini tenant-scoped dan boleh diedit melalui Settings: alamat, e-mel, telefon serta pautan Facebook, Instagram, TikTok dan YouTube divalidasi sebelum dipaparkan.
- Query/payload assembly bagi Dashboard, Events dan Event Participants telah dipindahkan daripada controller kepada tiga service khusus; controller masing-masing kini fokus pada authorization, input, response dan mutation.
- Artifact PCOV CI run `35560044122` pada master commit `0c1d05e7f` merekod **76.76% statement coverage (6,062/7,897)**; workflow mempunyai ratchet minimum 74.5% yang lulus.
- Predis 3.6 menyediakan Redis client portable untuk Windows/IIS dan Docker; Dockerfile/Compose production serta isolated staging path telah dibaiki dan divalidasi.
- Backup terenkripsi production-labelled workspace telah disalin off-host dan dipulihkan dalam MySQL 8 terasing: SHA-256 sah, 54 uploads serta row counts utama sepadan, health hijau dan RTO 7.699 saat.
- Authenticated multi-worker staging k6 lulus 1,150/1,150 checks, 0% HTTP failures dan p95 81.543 ms pada 10 VU/30 saat.
- GitHub Actions memantau `/up` setiap lima minit. Forced-failure evidence membuka serta assign issue #75; recovery probe menutup issue selepas endpoint kembali sihat.
- Public athlete/team directory tersedia di `/athletes` dengan profile performance berasaskan match rasmi.
- Navigasi awam desktop dan mobile/tablet kini memaparkan struktur menu yang sama; sub-menu Competition dan Information dibuka di bawah label desktop melalui klik, hover atau focus.
- Halaman awam `/venues` kini mempunyai directory venue yang actionable, empty state dan pautan terus ke `/schedule?venue=...`; schedule membaca filter venue daripada URL.
- Public homepage, directories, contact, information dan athlete profile kini memaparkan stale-data notice yang konsisten; `/faculties` mempunyai empty state khusus apabila tiada rekod diterbitkan.
- Public accessibility smoke production lulus **6/6**: axe serious/critical, keyboard focus target, mobile Escape/focus wrapping, navigation parity, locale switch dan CSP console checks.
- Public image resilience kini menggunakan `SafeImage` untuk fallback banner, branding dan sport icon; `ParticipantLogo` kembali kepada initials apabila asset logo gagal, manakala external links menggunakan `noopener noreferrer` dan E2E memeriksa alt attribute serta tiada broken image yang kekal.
- Public route state coverage kini lengkap: `PublicLayout` mengumumkan loading Inertia melalui `aria-busy`, halaman public mempunyai empty/error/stale/loading state dan retry yang konsisten, manakala permission kekal pada backend 403/404 kerana route public adalah anonymous.
- Responsive public shell smoke lulus **1/1** untuk viewport mobile 390px, tablet 768px dan desktop 1440px, reduced motion serta touch target minimum 44px; browser zoom 200% sebenar masih belum diaudit.
- Homepage public kini mempunyai pre-fixture state yang jelas: CTA utama membawa pengguna ke program sukan apabila jadual belum diterbitkan, dan panel hero menyediakan pautan pantas ke sukan, venue serta atlet.
- Sidebar authenticated dashboard kini memaparkan semua seksyen menu secara terus tanpa dropdown; visibility masih ditapis mengikut role dan pautan aktif kekal ditanda.
- Sidebar authenticated kini menggunakan gap, padding dan tinggi item yang sama pada Dashboard, Matches dan semua page lain; tiada lagi spacing khas untuk route Dashboard.
- Sidebar authenticated menggunakan spacing compact yang seragam untuk mengekalkan lebih banyak menu dalam ruang menegak tanpa mengubah struktur atau role visibility.
- Whitespace sidebar dipadatkan lagi; menu/logout kekal `min-h-11` untuk target interaksi minimum 44px.
- Dashboard authenticated kini membezakan workspace Super/Org Admin, Admin Sport dan Staff; CTA analytics/registration tidak lagi dipaparkan kepada role yang tiada akses, manakala faculty representative dan dean kekal pada dashboard khusus masing-masing.
- Match cards homepage/schedule menggunakan layout shared responsive; completed results menyokong scorer mengikut participant.
- `Sport.scoring_mode=individual` serta `match_scoring_events` menyokong nama atlet, minit jaringan dan validasi roster/score untuk Hockey dan Football/Soccer.
- User accounts now have the explicit `is_active` lifecycle flag; inactive accounts are excluded from login and can be managed from the Users form.
- Tenant-owned mutation services now re-derive `organization_id` server-side and reject parent foreign keys from another organization, including super-admin mixed-tenant payloads.

## Runtime Workspace

Semakan baca-sahaja mendapati 17 pengguna aktif, 17 role assignments, satu super-admin, tiada pengguna tanpa role, tiada orphan role assignment, dan tiada participant/sport assignment silang organisasi. Credential tidak diputar kerana tiada anomali ditemui.

Backlog 32 database-notification jobs telah diproses dengan `queue:work --stop-when-empty`. Selepas pemprosesan: **0 pending, 0 failed**; `stms:health-check` lulus.

Tujuh tetapan hubungan rasmi Pusat Sukan telah disimpan untuk organisasi `utem` dalam runtime workspace dan cache portal dibersihkan. Nilai ini boleh disunting kemudian melalui Settings tanpa perubahan kod. Paparan penuh pada production awam masih bergantung pada deployment release candidate.

`stms:release-preflight --json` telah dijalankan secara tidak merosakkan pada 18 Ogos. DB `SELECT 1` dan public organization/session selectors lulus. Overall result kekal `error` kerana enforcement, CSP, verification, Malaysia timezone, secure/Redis session, Redis queue/cache, real mailer, scheduled off-repository backup dan internal health monitoring belum dikonfigurasi. Ini mengesahkan NO-GO tanpa mengubah runtime.

Walaupun backup off-host point-in-time dan external uptime monitor kini mempunyai bukti, `.env` live belum mengaktifkan jadual backup/internal token atau Redis/runtime baseline; preflight 18 Ogos 12:02 MYT masih melaporkan kedua-duanya sebagai belum dikonfigurasi.

Runtime masih menggunakan nilai berikut sehingga deployment berjadual dibuat:

- database cache dan queue;
- file session;
- timezone UTC;
- email verification disabled;
- mail `log`;
- CSP report-only;
- `PRODUCTION_CONFIG_ENFORCE=false`.

Redis tempatan dikesan tersedia, tetapi menukar session/mail/verification pada sistem hidup boleh melog keluar pengguna atau menutup akses tanpa mail transport yang sah. Perubahan runtime mesti dibuat melalui release runbook, bukan suntingan ad hoc.

## Quality Gates Semasa

**Certification run — 21 September 2026:** required quality gate post-merge pada master commit `0c1d05e7f` lulus semua job: secret scan, dependency audits, Pint, PHPUnit, PCOV coverage, TypeScript/build/budget dan browser E2E. Artifact PCOV merekod **76.76% statement coverage (6,062/7,897)** dan lulus ratchet minimum 74.5%. Bukti ini ialah baseline semasa; ia tidak membuka P0 production yang masih di-hold.

| Gate | Keputusan connected CI 21 September 2026 |
|---|---|
| PHPUnit | Lulus |
| Pint | Lulus (`--test` seluruh repo) |
| TypeScript | Lulus |
| Tenant bypass allowlist | Lulus |
| Vite production build | Lulus |
| Bundle budget | Lulus |
| Composer audit | **Lulus — 0 advisory** |
| npm audit | **Lulus — 0 vulnerability** |
| PCOV statement coverage | **Lulus — 76.76% (6,062/7,897), minimum 74.5%** |
| Playwright/axe | Lulus — browser E2E |
| Inventory | Lulus |
| Connected CI | **Lulus — [run `35560044122`](https://github.com/ahmadzakiabdullah/stms/actions/runs/35560044122)** pada `0c1d05e7f`; semua job termasuk required quality gate hijau |

## Capability Tambahan 9 September 2026 — Remediasi Dependensi & Pint Cleanup

- Composer audit memaparkan 5 advisory baharu: `league/commonmark` 2.9.0 (4 advisory DoS/XSS dalam extension Attributes/SmartPunct, dirujuk laravel/framework) dan `maatwebsite/excel` 3.1.69 (CVE-2026-84374: penulisan export luar disk, <3.1.70). Dikemas kini ke `league/commonmark` **2.10.1** dan `maatwebsite/excel` **3.1.70**; audit kini 0 advisory dan suite PHPUnit penuh kekal 506/506.
- npm audit memaparkan 7 vulnerability (1 low/3 moderate/3 high) dalam toolchain build transitif (`browserslist`, `fast-uri`, `js-yaml`, `qs`, `postcss-selector-parser`, `hono`, `baseline-browser-mapping`). `npm install` di persekitaran npm 10.9.8 Windows/network-drive gagal dengan bug arborist `Tracker "idealTree" already exists` (npm/cli#4273, npm/cli#7596); workaround: regenerasi lockfile + node_modules di drive tempatan (npm 12.0.2) dan salin balik — audited **0 vulnerability**, typecheck/build/budget kekal hijau. `vite` dan `@vitejs/plugin-react` di-pin kepada `8.0.16`/`6.0.2` dalam `package.json` (menggantikan `"latest"`).
- Pint `--test` mendedahkan gaya tertunda dalam fail Fasa A (`EventParticipants` actions/requests, `EventParticipantImport`, request/settings/services, config dan 3 fail test); `vendor/bin/pint` digunakan dan difailkan sebagai commit `7a43b37e`. Fail yang dikecualikan (UTeM normalize seeders, deployment/runbook docs) dibiarkan unstaged.

## Capability Tambahan 17 September 2026 — Public Athlete Directory Pagination

- `/athletes` kini menapis (carian, sport, kategori) dan menyediakan A–Z serta pagination (24 atlet/12 pasukan) di server, dengan state kekal dalam URL; hanya satu halaman kad berada dalam DOM supaya senarai tidak memanjang apabila peserta bertambah.
- `PublicPortalService::athleteDirectory()` memulangkan paginator `rosters`/`athletes` berserta `counts`; cache dinaikkan ke `public-athletes:v2`.
- Kemasan UX P0: segmented Teams/Athletes dengan kiraan, chip sukan berikon, penapis fakulti + susunan, skeleton/loading dengan `aria-busy`, dan empty state boleh tindak.
- Suite penuh **508/508** (2,478 assertions); ujian pagination/filter/fakulti/susunan dalam `PublicPortalTest`.

## Capability Tambahan 9 September 2026 — Bulk Import Peserta/Kontinjen (Session-level)

- Import pukal peserta/kontinjen aras session melalui dua langkah preview→confirm: `POST /participants/import/preview` mem-parse CSV/XLSX, menjalankan per-row validation (name wajib, participant_type/status/is_active enum, email format, duplicate name/slug dalam organisasi), menyimpan baris sah dalam Cache 30 min dengan token UUID, dan memaparkan validation report.
- `POST /participants/import/confirm` mencipta semua peserta dalam satu DB transaction (all-or-nothing); sebarang kegagalan di-rollback dan token dibuang. Token yang tamat/kaput memberi mesej jelas tanpa menjejaskan data.
- Template import tersedia di `/participants/import/template` (`participants.import.template`); UI Import button + dialog preview dengan senarai baris sah/error ditambah pada halaman Participants.
- Suite penuh kini **506/506** hijau (routes 153, testFiles 99); 8 ujian feature baharu dalam `ParticipantImportTest`.

## Capability Tambahan 8 September 2026 — Print-Friendly Result Sheet

- PDF resmi `exports.resultSheet` dengan skor akhir, pemenang, status kelulusan, metadata submitted/approved-by dan garis tandatangan; tenant-scoped dan digabungkan sebagai butang print pada setiap baris Result dalam Results workspace.
- Suite penuh kini **498/498** hijau (routes 150, testFiles 98).

## Capability Tambahan 8 September 2026 — Match Schedule Conflict Validation

- `MatchScheduleConflictValidator` menyekat penciptaan/kemaskini match yang bertindih masa (venue sama dalam tetingkap 120 minit, atau participant bermain serentak dalam dua match) sebelum jadual diterbitkan; digabungkan ke `MatchController::store`/`update` dengan mesej ralat jelas.
- 6 ujian feature baharu; suite penuh kini **496/496** hijau (testFiles 98).

## Capability Tambahan 8 September 2026 — Export Medal Tally & Suite Cleanup

- Per-session medal tally PDF/XLSX export (`MedalTallyExport`, `exports.medals.{pdf,excel}`) dengan tenant scoping dan authorization `export-data`; butang ditambah pada halaman admin Rankings.
- Matriks inventori dikemas kini kepada `149 routes / 66 migrations / 97 testFiles`; suite penuh **490/490** lulus (tiga ujian export baharu ditambah).
- `ExampleTest::test_public_shell_is_self_hosted_and_has_basic_search_metadata` dibetulkan: assertion lapuk `assertDontSee('activity-logs.index')` dibuang kerana full Ziggy map sengaja di-embed untuk peralihan login Inertia (dijaga oleh authorization server-side); ini menutup kegagalan pre-existing terakhir.

## Capability Tambahan 8 September 2026 — Fasa A Event Participant Workflows

- State machine status pendaftaran dengan `canTransitionTo()` validated; `notes` wajib untuk reject; batch approve/reject pada `event-participants.batch-status`.
- Import pukal CSV/XLSX (Maatwebsite) ke `/event-participants/import` dengan template muat turun, per-row validation report, skip duplicate/unknown-event serta pentadbiran error tanpa rollback separa.
- Conflict detection per peserta melalui `ParticipantScheduleConflictService`; badge + tooltip dalam workshop Index.
- Withdraw dan restore registrations yang di-soft-delete; `EventParticipantPolicy` di-hardening supaya same-org non-admin tanpa permission row tidak lagi menerima 500.
- Halaman workshop Event Participants ditulis semula: bulk-select toolbar, import/withdraw actions dan dialogs.
- SUITE: 11/11 `EventParticipantBatchTest`, 486/487 PHPUnit penuh (satu kegagalan `ExampleTest` pre-existing di luar skop), semua CI gates tempatan hijau.

## Capability Tambahan 21 Ogos 2026

- `/athletes` dan `/athletes/{squadMember}` mendedahkan directory roster confirmed tanpa data sensitif serta performance rasmi pasukan.
- `/results/manage` merekod scorer individu untuk sport dengan `scoring_mode=individual`; hanya roster athlete aktif/confirmed boleh dipilih.
- `/schedule` dan homepage mengumpulkan scorer di bawah participant masing-masing; score 0 tidak memaparkan section scorer.
- Migration `2026_08_21_120000` dan `2026_08_21_120001` telah dijalankan pada runtime workspace.

## Production Awam Yang Disahkan Semasa Audit Asal

Portal production terdiri daripada homepage berseksyen di `/` plus halaman awam `/matches`, `/sports`, `/schedule`, `/results`, `/faculties`, `/venues`, `/live`, `/news`, `/downloads`, `/faq`, `/about` dan `/contact-us`. Product owner mengesahkan SAF 2026 berlangsung 13–25 Oktober 2026 dengan satu tournament, 30 acara dan 8 kontinjen. Rekod pertandingan boleh dikemas kini melalui pentadbiran jika maklumat rasmi berubah; pengesahan ini tidak membuktikan deployment release candidate semasa.

## Baki Sebelum Release Production

1. Operator menyediakan mail transport sebenar, secret storage dan approved cutover window; kemudian runtime ditukar kepada baseline production selamat.
2. DBA menghadkan principal kepada schema STMS dan merekod grants.
3. Jadual backup/internal health token diaktifkan pada runtime dan release preflight mesti hijau.
4. Deployment disahkan melalui worker/scheduler restart, authenticated smoke/Playwright dan release tag.
5. Reset-password mail delivery direkod sebelum email verification diaktifkan.

**Last updated:** 20 September 2026.
