# Release Runbook

> Pada 18 Ogos 2026 belum ada Git tag. Commit calon `4b04c46` melepasi quality gate tempatan dan connected CI #112, tetapi runtime preflight, mail, DB grants dan final deployment evidence masih belum lengkap (`NO-GO`).

## 1. Syarat Sebelum Release

- Working tree difahami dan hanya mengandungi perubahan yang diluluskan.
- Semua migration telah diuji pada salinan pangkalan data yang sesuai.
- Pint, PHPUnit, inventory, tenant-bypass, typecheck, build, bundle budget serta audit Composer/npm lulus pada commit yang sama.
- Semua endpoint authenticated mempunyai authorization capability yang sesuai, termasuk read/list.
- Production config melepasi `ProductionConfiguration` dan semakan manual untuk timezone, session, queue/cache, email verification, CSP, mail dan trusted proxies.
- Backup terenkripsi dan restore drill berjaya; rollback aplikasi disediakan tanpa menganggap migration boleh diundur secara automatik.
- `CHANGELOG.md` dan `CURRENT_STATE.md` merekodkan SHA, masa, keputusan gate dan pemilik kelulusan.

Salin dan lengkapkan [release evidence template](release-evidence-template.md). Jalankan preflight baca-sahaja sebelum kelulusan:

```bash
php artisan stms:release-preflight --json --max-backup-age-hours=24
```

Preflight tidak menggantikan reset-password delivery, external alert receipt, authenticated k6, isolated restore atau post-deploy smoke evidence.

## 2. Versioning

Gunakan Semantic Versioning selepas baseline pertama benar-benar diluluskan. Jangan mendakwa `v0.1.0` sehingga annotated tag itu wujud dan menunjuk kepada commit yang telah lulus CI.

```bash
git status --short
git rev-parse HEAD
git tag --list
```

## 3. Build dan Validation

> **Windows/IIS live-share guard:** production executes this application as `E:\others\saf`. Never run Composer against the live `vendor/` directory through another drive alias or UNC path; optimized classmaps may embed the wrong absolute path and cause HTTP 500. Build an immutable artifact, or validate `php -r "echo getcwd();"` returns exactly `E:\others\saf` before any live Composer command.

```bash
composer install --no-interaction --prefer-dist
npm ci
php vendor/bin/pint --test
php artisan test
npm run check:inventory
npm run check:tenant-bypasses
npm run typecheck
npm run build
npm run build:budget
composer audit --locked --no-interaction
npm audit --audit-level=high
php artisan route:list --except-vendor
```

## 4. Deployment

1. Deploy commit/tag yang diluluskan, bukan working tree ad hoc.
2. Pasang dependency production dan bina aset yang sepadan dengan commit.
3. Aktifkan maintenance mode jika perubahan skema memerlukannya.
4. Jalankan `php artisan migrate --force` hanya selepas backup dan semakan migration.
5. Bersihkan/rebuild cache Laravel; restart queue worker dan scheduler.
6. Keluar maintenance mode dan jalankan smoke test.

## 5. Smoke Test

- `GET /` memulangkan portal awam Inertia yang sah.
- `GET /contact-us`, `/login` dan `/up` memberi respons yang dijangka.
- `/health` diuji dengan token melalui saluran selamat; respons tanpa token sepatutnya 404.
- Laluan tidak wujud seperti `/medal-tally`, `/sports-programme` dan `/schedules` kekal 404 sehingga dilaksanakan dengan sengaja.
- Login, satu aliran read, satu mutasi terkawal, queue, cache, storage dan audit log diperiksa.
- Tiada exception baharu, CSP violation kritikal atau job gagal.

## 6. Rollback

Rollback aplikasi kepada artefak/tag terdahulu yang diketahui baik. Pulihkan pangkalan data daripada backup yang disahkan jika migration tidak backward-compatible; jangan jalankan `migrate:rollback` secara membuta tuli. Rekod insiden dan hasil rollback dalam changelog/operational record.

Rujuk [deployment architecture](../architecture/deployment.md), [backup/restore](backup-restore.md) dan [audit 17 Ogos](../audits/2026-08-17-full-project-and-production-audit.md).
