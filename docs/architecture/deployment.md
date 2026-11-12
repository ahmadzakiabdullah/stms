# Deployment Architecture

> **Repository note — 12 November 2026:** Commit hashes in this document are historical evidence only; take the current release candidate SHA from `CURRENT_STATE.md` and the release evidence record. The documented production NO-GO conditions and operator cutover requirements remain authoritative until new release evidence is recorded.

## Production Shape

Production semasa berada di `https://saf.utem.edu.my/` di belakang IIS 10 dengan Laravel/Inertia sebagai aplikasi root. Route lama `/portal` kini redirect 301 ke root. Aset frontend dibina oleh Vite ke `public/build`.

Keperluan minimum repositori ialah PHP 8.4, Composer 2, Node/npm yang serasi dengan lockfile, MySQL dan web server yang menunjuk document root kepada `public/`. Redis ialah sasaran production untuk cache/session/queue, tetapi runtime yang diaudit masih menggunakan database cache, database queue dan file session.

## Configuration

Mulakan daripada `.env.production.example`, kemudian simpan secret di luar Git. Nilai kritikal termasuk:

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://saf.utem.edu.my`;
- timezone aplikasi yang diluluskan (`Asia/Kuala_Lumpur` untuk operasi SAF);
- DB account least-privilege khusus kepada skema STMS;
- secure cookie/session, trusted proxy yang eksplisit dan HTTPS;
- `PUBLIC_ORG_SLUG` untuk portal awam; session aktif terkini dalam organisasi tersebut dipilih secara automatik;
- Redis bagi cache/session/queue apabila tersedia;
- mail provider sebenar, backup, scheduler, queue worker dan token health;
- `CSP_REPORT_ONLY=false` hanya selepas policy/enforcement telah diuji.

`ProductionConfiguration` mengesahkan nilai konfigurasi minimum bagi timezone, Redis, email verification, secure session, CSP dan mailer bukan `log`. Ia tidak membuktikan Redis/mail benar-benar boleh dicapai, trusted proxy tepat, DB least privilege atau delivery e-mel end-to-end; semua itu memerlukan semakan operasi.

`php artisan stms:release-preflight --json` menambah pemeriksaan baca-sahaja bagi DB, Redis PING, mailer, backup off-repository terkini, internal monitoring dan public tenant selectors. Outputnya perlu dilampirkan pada [release evidence record](../deployment/release-evidence-template.md), tetapi tidak menggantikan bukti operasi luaran.

## Deployment Sequence

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Restart queue workers selepas deploy. Pastikan scheduler Laravel berjalan. Gunakan maintenance mode dan backup apabila perubahan skema/kontrak memerlukannya.

## Current Release Status

Rekod sejarah (18 Ogos 2026): tiada Git tag; release hardening ketika itu dikomit sebagai `4b04c46` dan CI #112 lulus pada SHA itu. Rekod ini bukan bukti bagi kod semasa—CI dan preflight mesti dijalankan semula pada SHA release candidate. Menurut bukti terakhir yang direkodkan, runtime cutover/preflight dan authenticated post-deploy evidence belum lengkap.

Prosedur kelulusan penuh berada dalam [release runbook](../deployment/release-runbook.md).

Prosedur respons operasi, rollback, pemulihan worker/scheduler dan ownership alert berada dalam [operations runbook](../deployment/operations-runbook.md). Threshold dan signal minimum berada dalam [monitoring matrix](monitoring.md); kedua-duanya memerlukan pengaktifan serta bukti operator sebelum release dianggap lulus.
