# CURRENT STATE

> Snapshot jujur STMS/SAF pada **21 Ogos 2026** selepas public athlete/scorer workflows dan match-card UX refactor. Bukti asal dan addendum: [`docs/audits/2026-08-17-full-project-and-production-audit.md`](docs/audits/2026-08-17-full-project-and-production-audit.md).

## Status Keseluruhan

**Produk:** MVP web beroperasi.

**Repository:** perubahan semasa dikomit sebagai `4c4ebf0c` dan telah dipush ke `origin/master`. Quality gate tempatan untuk scorer/public portal lulus; connected CI #112 merujuk commit terdahulu. **Production deployment kekal NO-GO** sehingga konfigurasi runtime, mail, DB grants dan final cutover evidence diselesaikan.

**Production awam:** <https://saf.utem.edu.my/> tersedia, tetapi belum dianggap telah menerima release candidate yang telah dikomit ini.

Aliran utama tersedia: Organization/User/RBAC → Session/Tournament/Sport/Category/Event → Participant/Registration/Squad → Dean Verification → Draw/Match/Result → Rankings/Exports/Reports → Notifications/Settings/Activity Logs.

## Inventori Repository

| Item | Nilai |
|---|---:|
| Laravel routes | 148 application routes |
| Migrations | 65 migration files |
| Controllers | 39 controller files |
| Form Requests | 28 |
| Policies | 21 fail |
| Actions | 37 |
| Services/concerns | 40 fail |
| Models | 18 |
| Inertia `.tsx` pages | 43 |
| PHP tests | 94 PHP test files |
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
- Meta description, canonical, `/sitemap.xml`, public refresh status dan guest Ziggy filtering telah ditambah.
- Initial global Vite prefetch dibuang untuk mengecilkan HTML/request awal.
- Production configuration validator kini mewajibkan Redis session/cache/queue, Asia/Kuala_Lumpur, email verification, secure cookie, CSP enforcing dan mail bukan `log`.
- Vendor dependencies diselaraskan kepada lockfile selamat (Guzzle 7.15.2, PSR-7 2.13.0) untuk menutup advisory semasa.
- Axe/keyboard smoke tests meliputi login, dashboard, homepage dan Contact pada desktop/mobile; contrast dan ARIA findings semasa telah dibaiki.
- Butiran hubungan awam kini tenant-scoped dan boleh diedit melalui Settings: alamat, e-mel, telefon serta pautan Facebook, Instagram, TikTok dan YouTube divalidasi sebelum dipaparkan.
- Query/payload assembly bagi Dashboard, Events dan Event Participants telah dipindahkan daripada controller kepada tiga service khusus; controller masing-masing kini fokus pada authorization, input, response dan mutation.
- Artifact PCOV CI #112 merekod 75.03% statement coverage (4,676/6,232); workflow mempunyai ratchet minimum 74.5% yang lulus pada commit `4b04c46`.
- Predis 3.6 menyediakan Redis client portable untuk Windows/IIS dan Docker; Dockerfile/Compose production serta isolated staging path telah dibaiki dan divalidasi.
- Backup terenkripsi production-labelled workspace telah disalin off-host dan dipulihkan dalam MySQL 8 terasing: SHA-256 sah, 54 uploads serta row counts utama sepadan, health hijau dan RTO 7.699 saat.
- Authenticated multi-worker staging k6 lulus 1,150/1,150 checks, 0% HTTP failures dan p95 81.543 ms pada 10 VU/30 saat.
- GitHub Actions memantau `/up` setiap lima minit. Forced-failure evidence membuka serta assign issue #75; recovery probe menutup issue selepas endpoint kembali sihat.
- Public athlete/team directory tersedia di `/athletes` dengan profile performance berasaskan match rasmi.
- Match cards homepage/schedule menggunakan layout shared responsive; completed results menyokong scorer mengikut participant.
- `Sport.scoring_mode=individual` serta `match_scoring_events` menyokong nama atlet, minit jaringan dan validasi roster/score untuk Hockey dan Football/Soccer.

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

**Latest focused validation (21 Ogos 2026):** scorer/result workflow `20/20` tests dengan `57` assertions; public portal workflow `19/19` tests dengan `355` assertions. Baris baseline penuh dan connected CI di bawah dikekalkan sebagai evidence sejarah.

| Gate | Keputusan working tree 21 Ogos 2026 |
|---|---|
| PHPUnit | **Lulus — 441/441, 2,040 assertions (working tree 19 Ogos)** |
| Pint | Lulus |
| TypeScript | Lulus |
| Tenant bypass allowlist | Lulus |
| Vite production build | Lulus |
| Bundle budget | Lulus |
| Composer audit | Lulus — 0 advisory |
| npm audit | Lulus — 0 vulnerability |
| Playwright/axe | **Lulus — 8/8 desktop/mobile** pada SQLite terasing |
| Inventory | Menjangka matriks `126 / 61 / 39 / 38 / 94` |
| Connected CI | **Lulus — [run #112](https://github.com/ahmadzakiabdullah/stms/actions/runs/32097257726)** pada `4b04c46`; keenam-enam job hijau termasuk browser E2E dan ratchet PCOV |

## Capability Tambahan 21 Ogos 2026

- `/athletes` dan `/athletes/{squadMember}` mendedahkan directory roster confirmed tanpa data sensitif serta performance rasmi pasukan.
- `/results/manage` merekod scorer individu untuk sport dengan `scoring_mode=individual`; hanya roster athlete aktif/confirmed boleh dipilih.
- `/schedule` dan homepage mengumpulkan scorer di bawah participant masing-masing; score 0 tidak memaparkan section scorer.
- Migration `2026_08_21_120000` dan `2026_08_21_120001` telah dijalankan pada runtime workspace.

## Production Awam Yang Disahkan Semasa Audit Asal

Portal production terdiri daripada homepage berseksyen di `/` plus halaman awam `/matches`, `/sports`, `/schedule`, `/results`, `/faculties`, `/venues`, `/live`, `/news`, `/downloads`, `/faq`, `/about` dan `/contact-us`. Product owner mengesahkan SAF 2026 berlangsung 1–31 Oktober 2026 dengan satu tournament, 30 acara dan 8 kontinjen. Rekod pertandingan boleh dikemas kini melalui pentadbiran jika maklumat rasmi berubah; pengesahan ini tidak membuktikan deployment release candidate semasa.

## Baki Sebelum Release Production

1. Operator menyediakan mail transport sebenar, secret storage dan approved cutover window; kemudian runtime ditukar kepada baseline production selamat.
2. DBA menghadkan principal kepada schema STMS dan merekod grants.
3. Jadual backup/internal health token diaktifkan pada runtime dan release preflight mesti hijau.
4. Deployment disahkan melalui worker/scheduler restart, authenticated smoke/Playwright dan release tag.
5. Reset-password mail delivery direkod sebelum email verification diaktifkan.

**Last updated:** 21 Ogos 2026.
