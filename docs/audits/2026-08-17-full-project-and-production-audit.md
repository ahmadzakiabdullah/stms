# Audit Keseluruhan Projek dan Production SAF

**Tarikh audit:** 17 Ogos 2026
**Repository:** `master` pada `e82ee951f6acdaf9c243af535072943c37ca8e26`, dengan working tree sedia ada yang belum bersih
**Production awam:** <https://saf.utem.edu.my/>
**Keputusan:** **MVP beroperasi, tetapi belum release-ready**

> **Addendum remediation, 17 Ogos 2026:** Dapatan repository P0 telah dibaiki dan dipecah kepada commit logik. PHPUnit kini 434/434 (1,937 assertions), Pint dan semua build/audit gates hijau, Playwright/axe local 8/8, read authorization matrix tersedia, ranking rules telah diasingkan, font self-hosted/sitemap telah ditambah, dan queue workspace ialah 0 pending/0 failed. Connected CI #105 pada commit `5bb86a8` lulus untuk dependency audit, lint, test, coverage, build dan browser E2E. Product owner mengesahkan 1–31 Oktober 2026, satu tournament, 30 acara, 8 kontinjen dan IA single-page; rekod pertandingan boleh dikemas kini jika maklumat rasmi berubah. Alamat, e-mel, telefon dan empat pautan media sosial rasmi Pusat Sukan kini disimpan sebagai tetapan tenant-editable dalam runtime workspace; paparan penuh pada production awam masih memerlukan deployment release candidate. Jadual/dapatan di bawah kekal sebagai bukti keadaan pada masa audit asal. Deployment production masih menunggu runtime cutover dan operational evidence yang disenaraikan dalam `TODOS.md`.

> **Addendum 18 Ogos 2026:** Refactor query orchestration Dashboard/Event/Event Participant, ratchet coverage 74.5%, release blockers tersusun serta preflight/evidence template telah dikomit sebagai `e535e4b`. Semua gate tempatan lulus: PHPUnit 439/439 (1,948 assertions), Pint, TypeScript, inventory, tenant bypass, dependency audits, build/budget dan Playwright/axe 8/8. Connected CI [#110](https://github.com/ahmadzakiabdullah/stms/actions/runs/32093252159) pada SHA yang sama turut lulus keenam-enam job dan merekod PCOV 75.03% (4,676/6,232). Production kekal NO-GO sehingga bukti operator dalam `TODOS.md` lengkap.

## 1. Skop dan Kaedah

Audit ini membezakan tiga sumber bukti yang sebelum ini bercampur dalam dokumentasi:

1. **Repository** — kod, migrasi, route, dependensi, ujian, CI, seni bina dan dokumentasi.
2. **Runtime workspace** — konfigurasi Laravel dan data pada salinan kerja semasa.
3. **Production awam** — respons HTTP, route awam, header keselamatan dan payload Inertia yang boleh dilihat tanpa log masuk.

Tiada percubaan log masuk, perubahan data production, penetration test, restore production atau load test berintensiti tinggi dilakukan. Pemeriksaan production adalah baca sahaja.

## 2. Ringkasan Eksekutif

STMS/SAF ialah modern monolith Laravel/Inertia yang mempunyai asas teknikal baik: tenant scope, Policies, Form Requests, Service/Action classes, UUID pada hampir semua jadual domain, activity logging, upload sanitization, rate limiting, CI, backup tooling dan health checks. Portal awam serta aliran pengurusan pertandingan utama memang wujud.

Namun, status semasa **tidak boleh dilabel release-ready** kerana:

- PHPUnit dan Pint gagal pada run semasa.
- Working tree tidak bersih dan tiada release tag.
- Runtime workspace berlabel `production` tidak menggunakan baseline production selamat yang didokumenkan.
- Beberapa halaman indeks sensitif tidak memanggil `viewAny`, lalu navigasi role-aware tidak disokong sepenuhnya oleh authorization backend.
- Dokumen lama menyenaraikan route awam dan data SAF yang tidak lagi sama dengan production.
- Ranking points masih hardcoded dan tiada interface strategi seperti yang didakwa dokumen lama.
- CSP production masih `Report-Only`; stylesheet Bunny Fonts juga bercanggah dengan polisi enforcing semasa.

## 3. Bukti Repository

### 3.1 Inventori terukur

| Item | Nilai semasa |
|---|---:|
| Route Laravel | 125 keseluruhan; 124 application/non-filesystem |
| Route dengan `auth` | 106 |
| Migrasi | 60; semuanya `Ran` pada database workspace |
| Controller | 39 |
| Form Request | 28 |
| Policy | 20 fail |
| Action | 37 |
| Service | 31 |
| Model domain/aplikasi | 16, tidak termasuk trait |
| Halaman Inertia `.tsx` | 38 |
| Fail ujian PHP | 92 |
| Fail E2E Playwright | 1 fail yang mengandungi 6 journeys |
| Fail Markdown projek | 91 sebelum laporan ini ditambah |

`npm run check:inventory` mengesahkan matriks `125 / 60 / 39 / 38 / 92`.

### 3.2 Versi terkunci

| Komponen | Versi |
|---|---|
| PHP requirement | `^8.4` |
| Laravel | `13.23.0` |
| inertia-laravel | `2.0.24` |
| React | `18.3.1` |
| Inertia React | `2.3.25` |
| TypeScript | `5.9.3` |
| Tailwind CSS | `3.4.19` |
| Vite | `8.0.16` |
| Spatie Permission | `6.25.0` |
| Spatie Activity Log | `5.0.0` |

### 3.3 Quality gates pada 17 Ogos 2026

| Gate | Keputusan |
|---|---|
| Inventory check | Lulus |
| Tenant-bypass allowlist | Lulus |
| TypeScript `tsc --noEmit` | Lulus |
| Vite production build | Lulus |
| Bundle budget | Lulus — JS ≤ 400 KB, CSS ≤ 100 KB |
| Composer audit | Lulus — tiada advisory |
| npm audit | Lulus — 0 vulnerability |
| PHPUnit | **Gagal** — 422 tests, 420 passed, 1 failure, 1 error, 1,702 assertions |
| Pint | **Gagal** — 6 fail memerlukan formatting |

Kegagalan PHPUnit:

- `DrawControllerTest::test_authorized_user_can_move_participant` menjangka mesej lama “fixtures regenerated”, sedangkan implementasi menyimpan group assignment tanpa menjana fixture.
- `DrawServiceTest::test_it_moves_participant_to_pool` memanggil perpindahan selepas fixture diwujudkan, sedangkan implementasi semasa mewajibkan draw di-reset dahulu.

Fail Pint: `SettingController.php`, `DrawService.php`, dua sample hockey seeder, `routes/web.php`, dan `TenantIsolationTest.php`.

## 4. Keadaan Runtime Workspace

Runtime Laravel semasa melaporkan:

| Tetapan | Nilai |
|---|---|
| Environment | `production` |
| Debug | `false` |
| Timezone | `UTC` |
| Cache | `database` |
| Queue | `database` |
| Session | `file` |
| Email verification required | `false` |
| CSP report-only | `true` |
| Production configuration enforcement | `false` |

Ini bercanggah dengan `.env.production.example`, yang menetapkan Redis untuk cache/queue/session, timezone `Asia/Kuala_Lumpur`, email verification, CSP enforcement dan `PRODUCTION_CONFIG_ENFORCE=true`.

`stms:health-check` lulus, tetapi menunjukkan **32 pending jobs** dan 0 failed jobs. Baseline queue membenarkan sehingga 100 pending jobs, maka status kekal `ok`; backlog ini tetap perlu disahkan diproses oleh worker.

Database workspace mempunyai 60 migrasi `Ran`, 0 pending. Rekod aktif:

| Entiti | Aktif |
|---|---:|
| Organization | 1 |
| Session | 1 |
| Tournament | 1 |
| Sport | 24 |
| Sport category | 30 |
| Event | 30 |
| Participant/fakulti | 8 |
| Event participant | 248 |
| Match | 24 |
| Result | 0 |
| User | 17 |

Runtime ini mempunyai 6 role, 42 permission, 17 role assignments dan 882 activity-log records. Akaun database workspace boleh melihat metadata banyak schema lain pada server yang sama; principle of least privilege untuk DB principal belum dibuktikan.

## 5. Keadaan Production Awam

### 5.1 Route dan kandungan

| URL | HTTP | Keadaan |
|---|---:|---|
| `/` | 200 | Homepage awam Inertia |
| `/contact-us` | 200 | Halaman hubungan |
| `/login` | 200 | Login |
| `/register` | 404 | Public registration ditutup |
| `/medal-tally` | 404 | Route lama tidak wujud |
| `/sports-programme` | 404 | Route lama tidak wujud |
| `/schedules` | 404 | Route lama tidak wujud |
| `/results` | 405 | URL ini ialah POST result management, bukan GET awam |
| `/health` | 404 tanpa token | Health endpoint dilindungi/menyamar sebagai 404 |
| `/up` | 200 | Liveness asas Laravel |
| `/sitemap.xml` | 404 | Sitemap tiada |

Homepage semasa menggabungkan Sports, Schedule, Results dan Medal standings sebagai anchor sections; hanya Contact ialah halaman awam berasingan.

Payload production pada masa audit:

- Competition: **Sukan Antara Fakulti 2026**
- Tarikh: **1–31 Oktober 2026**
- 23 sukan yang mempunyai event aktif
- 30 event
- 8 fakulti
- 12 match berjadual
- 0 match selesai
- 0 result dan 0 medal row

Angka ini ialah snapshot public payload, bukan inventori database penuh. Ia berbeza daripada workspace (24 sports, 24 matches) dan seeder sejarah (dua fasa September).

### 5.2 HTTP, TLS dan prestasi ringan

- HSTS, `nosniff`, `SAMEORIGIN`, referrer policy dan permissions policy dihantar.
- CSP dihantar sebagai `Content-Security-Policy-Report-Only`, bukan enforcing.
- Production masih memuat stylesheet `fonts.bunny.net`; CSP enforcing dalam `SecurityHeaders` hanya membenarkan style/font sendiri. External font perlu dibuang/self-host atau polisi diselaraskan sebelum enforcing.
- TLS certificate wildcard UTeM sah hingga 8 November 2026; sambungan audit berunding TLS 1.2.
- 8 GET berturutan ke `/`: median kira-kira 237 ms, purata 289 ms, maksimum 683 ms. Ini hanya smoke timing, bukan load-test evidence.
- HTML awal kira-kira 68 KB dan memuat senarai Ziggy semua named routes serta banyak prefetch hints kerana `Vite::prefetch(concurrency: 3)`.
- Tajuk asas ialah `SAF`; meta description dan sitemap tidak ditemui.
- Halaman Contact tidak mempunyai e-mel/telefon terus kerana setting production kosong; ia hanya memberi arahan generik dan pautan laman UTeM.

## 6. Dapatan Mengikut Keutamaan

### P0 — blok release

#### P0-01 Quality gate gagal

PHPUnit dan Pint tidak hijau. Release tag dan deploy baharu mesti diblok sehingga kedua-duanya lulus pada clean commit dan connected CI.

#### P0-02 Baseline production tidak dikuatkuasakan

Workspace `production` menggunakan database cache/queue, file session, verification off, report-only CSP, UTC dan `PRODUCTION_CONFIG_ENFORCE=false`. Validator sedia ada juga hanya menolak `sync` queue dan `file` cache; ia tidak mewajibkan Redis, Redis session, timezone atau email verification.

#### P0-03 Read authorization tidak konsisten

Beberapa halaman indeks sensitif hanya berada di bawah `auth` dan tidak memanggil `Gate::authorize('viewAny', ...)`, termasuk `OrganizationController@index`, `UserController@index` dan `ParticipantController@index`.

Kesan:

- pengguna authenticated yang mengetahui URL boleh memintas menu role-aware;
- organization index menggunakan model root tanpa tenant scope dan boleh memaparkan senarai organisasi;
- user index mengehadkan tenant secara manual tetapi tidak menguatkuasakan `UserPolicy::viewAny`;
- participant index boleh menghantar medan hubungan dan data peserta kepada role yang tidak sepatutnya mempunyai akses modul.

Ujian `OrganizationTest::test_non_super_admin_cannot_see_other_organizations_data_via_global_scope` tidak membuat HTTP request atau assertion data isolation sebenar; ia hanya membandingkan dua ID organisasi.

#### P0-04 Production, workspace dan dokumentasi tidak sepadan

Dokumen terdahulu mendakwa route awam berasingan dan kejohanan September dua fasa. Production serta data aktif workspace kini menunjukkan satu kejohanan Oktober. Owner data perlu menetapkan source of truth sebelum promosi portal atau seeding/deployment seterusnya.

### P1 — hardening seterusnya

#### P1-01 Favicon lookup tidak tenant-explicit

`resources/views/app.blade.php` membaca `Setting::where('key', 'favicon_url')->value('value')` semasa guest context dibypass. Dalam multi-tenant deployment, favicon boleh dipilih daripada tenant pertama secara tidak deterministik. Gunakan tenant public yang eksplisit atau shared Inertia setting.

#### P1-02 Ranking belum benar-benar configurable

`RankingService` menggunakan `3` mata menang dan `1` mata seri secara hardcoded. Strategi ialah private methods dalam satu class; tiada `RankingStrategy` interface atau konfigurasi per tournament bagi nilai mata/tiebreaker. Dokumen lama yang menyatakan sebaliknya telah dibetulkan.

#### P1-03 Pengecualian standard model perlu didokumenkan

`settings.id` ialah auto-increment integer, bukan UUID. `Setting`, `SquadMember` dan `DrawVersion` tidak menggunakan soft deletes; `DrawVersion` memang dimaksudkan sebagai snapshot immutable. Dakwaan “semua model UUID + soft delete” tidak tepat.

#### P1-04 Controller masih berat

`EventParticipantController` 299 baris, `DashboardController` 284, `DrawController` 258 dan `EventController` 215. Service/Action coverage telah bertambah, tetapi query assembly dan orchestration masih banyak di controller. Refactor perlu berasaskan risiko, bukan kiraan baris semata-mata.

#### P1-05 Operations evidence belum lengkap

Masih belum ada bukti semasa untuk:

- connected CI pada commit bersih selepas perubahan terkini;
- authenticated multi-worker staging load test;
- external operator menerima alert sebenar;
- actual production backup disalin off-host dan dipulihkan secara terasing;
- mail/reset-password production end-to-end;
- semakan role/activity logs selepas privilege-escalation lama.

### P2 — penambahbaikan

- Tambah SEO metadata, canonical konsisten dan sitemap untuk portal awam.
- Kecilkan public route manifest Ziggy jika route enumeration/payload size tidak diperlukan.
- Buang Bunny Fonts yang redundant kerana empat font sudah dibundle melalui Fontsource.
- Ratchet test coverage selepas baseline connected CI direkod.
- Tambah policy/read-access matrix test untuk setiap role dan setiap index route.
- Semak permission DB runtime supaya hanya schema STMS boleh dilihat/diubah.

## 7. Kawalan Positif yang Disahkan

- `.env` sebenar tidak tracked oleh Git.
- Tiada UI framework terlarang ditemui.
- Tiada `dangerouslySetInnerHTML`, `DB::unprepared`, debug terminator atau SQL interpolation pengguna ditemui dalam scan sasaran.
- Tenant bypass allowlist lulus.
- Public registration production mengembalikan 404.
- Upload logo/SVG menggunakan service sanitization dan UUID filenames.
- Public portal query di-scope dengan organization/session eksplisit, dipisahkan upcoming/completed dan dicache dua minit.
- Semua locked dependency audit bersih pada tarikh audit.
- Build dan budget frontend lulus.
- Health check memeriksa DB, cache, queue dan disk tanpa mendedahkan exception details.

## 8. Keputusan Release

**NO-GO untuk tag/release baharu pada 17 Ogos 2026.**

Minimum untuk bertukar kepada GO:

1. Baiki dua kegagalan PHPUnit dan enam isu Pint.
2. Tambah/baiki authorization `viewAny` serta regression tests untuk URL manual dan cross-tenant reads.
3. Selaraskan konfigurasi runtime production dengan baseline selamat dan hidupkan enforcement.
4. Sahkan data/tarikh/route public bersama product owner.
5. Selesaikan semakan role/activity logs yang tertangguh.
6. Commit bersih, connected CI hijau, backup sebelum deploy, smoke test selepas deploy.
7. Cipta tag versi pertama hanya selepas semua syarat di atas dibuktikan.

## 9. Dokumen Kanonik Selepas Audit

- `CURRENT_STATE.md` — snapshot ringkas semasa.
- `TODOS.md` — backlog aktif berdasarkan P0/P1/P2 di atas.
- `ROADMAP.md` — milestone produk dan status release.
- Dokumen ini — bukti audit bertarikh 17 Ogos 2026.
- Audit terdahulu kekal sebagai rekod sejarah dan tidak boleh digunakan sebagai bukti status semasa tanpa semakan semula.
