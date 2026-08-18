# Backup and Restore Runbook

> Mekanik restore pernah diuji pada dataset sanitized pada 5 Ogos 2026. Audit 17 Ogos tidak membuktikan jadual backup production, off-site custody atau restore drill production aktif; sahkan perkara tersebut sebelum release.

## Objectives

- **RPO:** 24 hours with the default daily schedule. Reduce the interval if operations require lower data loss.
- **RTO target:** 2 hours for a single-node restore, subject to database/storage size and infrastructure availability.
- **Scope:** configured database plus `storage/app/public` uploads.
- **Retention:** 14 days by default, controlled by `BACKUP_RETENTION_DAYS`.

## Configuration

Set `BACKUP_ENABLED=true`, an off-repository `BACKUP_PATH`, and a unique `BACKUP_ENCRYPTION_KEY` of at least 32 characters. Store the key in the deployment secret manager. Losing the key makes backups unrecoverable.

For MySQL, ensure `mysqldump` and `mysql` are installed or configure `MYSQLDUMP_BINARY` and `MYSQL_BINARY`. The Docker image includes compatible client tools.

The application creates AES-256 encrypted ZIP archives with a manifest and SHA-256 checksum for every database/storage entry.

## Create and Retain a Backup

```bash
php artisan stms:backup
```

Copy completed archives to storage outside the application host/container. Local retention is not a substitute for off-site storage.

Sebelum release, jalankan `php artisan stms:release-preflight --json`. Preflight mengesahkan archive `stms-*.zip` terkini berada di path yang resolve di luar repository, tidak kosong, belum melepasi umur diluluskan dan merekod SHA-256. Ia tidak membuka archive atau menggantikan isolated restore drill.

## Restore Drill

1. Select a known backup and record its timestamp.
2. Restore first into an isolated environment running the same application release.
3. Configure the same `BACKUP_ENCRYPTION_KEY`.
4. Put the isolated application in maintenance mode.
5. Run:

```bash
php artisan down
php artisan stms:restore /absolute/path/to/stms-YYYYMMDD-HHMMSS.zip --force
php artisan optimize:clear
php artisan migrate:status
php artisan stms:health-check
php artisan up
```

6. Verify login, tenant isolation, recent registrations/results, uploaded logos, exports, and queue processing. Record achieved RPO/RTO.

## Production Restore

A restore replaces the current database and public upload directory. Obtain incident authorization, stop incoming writes and queue workers, take a pre-restore snapshot, and confirm the target archive in an isolated drill before restoring production.

After restoration, restart workers, run the health check, perform smoke tests, and retain incident/audit evidence.

## Automated Evidence

`tests/Feature/BackupServiceTest.php` creates an encrypted backup, changes a temporary SQLite database and upload file, restores the archive, and verifies both return to their original state. On 5 August 2026, a sanitized 3.47 MB dataset was encrypted and restored into a second isolated MySQL 8 database in 2.977 seconds; all migrations, health checks, and key row counts matched. This validates MySQL mechanics and RTO but does not replace an approved restore of an actual production backup or an off-site custody drill. Note that `stms:restore --force` still asks for interactive confirmation.
