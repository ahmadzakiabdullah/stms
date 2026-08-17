# CURRENT STATE

> Snapshot jujur STMS/SAF pada **17 Ogos 2026** selepas remediation audit. Bukti asal dan addendum: [`docs/audits/2026-08-17-full-project-and-production-audit.md`](docs/audits/2026-08-17-full-project-and-production-audit.md).

## Status Keseluruhan

**Produk:** MVP web beroperasi.

**Repository:** semua quality gate tempatan hijau dan connected CI #101 lulus pada commit `35b9dac`. Kod ialah release candidate, tetapi **production deployment masih NO-GO** sehingga konfigurasi runtime, mail, backup dan pengesahan product owner diselesaikan.

**Production awam:** <https://saf.utem.edu.my/> tersedia, tetapi belum dianggap telah menerima release candidate yang telah dikomit ini.

Aliran utama tersedia: Organization/User/RBAC → Session/Tournament/Sport/Category/Event → Participant/Registration/Squad → Dean Verification → Draw/Match/Result → Rankings/Exports/Reports → Notifications/Settings/Activity Logs.

## Inventori Repository

| Item | Nilai |
|---|---:|
| Laravel routes | 126 application routes |
| Migrations | 61 migration files |
| Controllers | 39 controller files |
| Form Requests | 28 |
| Policies | 21 fail |
| Actions | 37 |
| Services | 35 |
| Models | 16 |
| Inertia `.tsx` pages | 38 |
| PHP tests | 93 PHP test files |
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

## Runtime Workspace

Semakan baca-sahaja mendapati 17 pengguna aktif, 17 role assignments, satu super-admin, tiada pengguna tanpa role, tiada orphan role assignment, dan tiada participant/sport assignment silang organisasi. Credential tidak diputar kerana tiada anomali ditemui.

Backlog 32 database-notification jobs telah diproses dengan `queue:work --stop-when-empty`. Selepas pemprosesan: **0 pending, 0 failed**; `stms:health-check` lulus.

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

| Gate | Keputusan 17 Ogos 2026 |
|---|---|
| PHPUnit | **Lulus — 430/430, 1,860 assertions** |
| Pint | Lulus |
| TypeScript | Lulus |
| Tenant bypass allowlist | Lulus |
| Vite production build | Lulus |
| Bundle budget | Lulus |
| Composer audit | Lulus — 0 advisory |
| npm audit | Lulus — 0 vulnerability |
| Playwright/axe | **Lulus — 8/8 desktop/mobile** pada SQLite terasing |
| Inventory | Menjangka matriks `126 / 61 / 39 / 38 / 93` |
| Connected CI | **Lulus — [run #101](https://github.com/ahmadzakiabdullah/stms/actions/runs/31992389901)** pada `35b9dac`; enam job berjaya |

## Production Awam Yang Disahkan Semasa Audit Asal

Portal production ialah single-page homepage berseksyen plus `/contact-us`. Snapshot asal menunjukkan SAF 2026, 1–31 Oktober 2026, 30 events, 8 faculties dan belum ada result. Nilai ini masih memerlukan pengesahan product owner dan tidak membuktikan deployment working tree semasa.

## Baki Sebelum Release Production

1. Product owner mengesahkan tarikh, satu tournament, 30 events dan information architecture single-page.
2. Operator menyediakan mail transport sebenar, menukar runtime kepada baseline production selamat dan melakukan smoke test selepas deploy.
3. DBA menghadkan principal kepada schema STMS dan merekod grants.
4. Contact email/phone/address production dilengkapkan.
5. Backup release diambil, deployment disahkan dan release tag diwujudkan; connected CI #101 sudah lulus pada `35b9dac`.
6. Evidence operasi luaran—staging k6, alert receipt, off-host restore dan reset-password mail—direkod.

**Last updated:** 17 Ogos 2026.
