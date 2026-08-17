# Deployment Architecture

## Production Shape

Production semasa berada di `https://saf.utem.edu.my/` di belakang IIS 10 dengan Laravel/Inertia sebagai aplikasi root. `/portal` masih alias kepada portal yang sama; ia bukan base path wajib. Aset frontend dibina oleh Vite ke `public/build`.

Keperluan minimum repositori ialah PHP 8.4, Composer 2, Node/npm yang serasi dengan lockfile, MySQL dan web server yang menunjuk document root kepada `public/`. Redis ialah sasaran production untuk cache/session/queue, tetapi runtime yang diaudit masih menggunakan database cache, database queue dan file session.

## Configuration

Mulakan daripada `.env.production.example`, kemudian simpan secret di luar Git. Nilai kritikal termasuk:

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://saf.utem.edu.my`;
- timezone aplikasi yang diluluskan (`Asia/Kuala_Lumpur` untuk operasi SAF);
- DB account least-privilege khusus kepada skema STMS;
- secure cookie/session, trusted proxy yang eksplisit dan HTTPS;
- `PUBLIC_ORG_SLUG` dan `PUBLIC_SESSION_SLUG` untuk portal awam;
- Redis bagi cache/session/queue apabila tersedia;
- mail provider sebenar, backup, scheduler, queue worker dan token health;
- `CSP_REPORT_ONLY=false` hanya selepas policy/enforcement telah diuji.

`ProductionConfiguration` ialah guard automatik minimum, bukan pengganti semakan manual. Ia tidak membuktikan timezone, Redis, email verification atau least privilege.

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

Tiada Git tag pada 17 Ogos 2026. Remediation telah dipecah kepada commit logik dan quality gate tempatan lulus, tetapi HEAD belum menjadi connected-CI artifact. Hash aset production juga tidak sama dengan build release candidate, maka production dan repository semasa tidak boleh dianggap identik.

Prosedur kelulusan penuh berada dalam [release runbook](../deployment/release-runbook.md).
