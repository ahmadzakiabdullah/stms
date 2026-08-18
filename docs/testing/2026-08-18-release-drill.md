# Release Drill Evidence — 18 August 2026

This record contains sanitized evidence only. Encryption keys, database passwords, Redis passwords, health tokens and staging credentials are intentionally excluded.

## Off-host Backup and Isolated Restore

- Source: production-labelled workspace database `db4safportal` plus `storage/app/public`.
- Archive host: workstation `ZakiAbdullah` (`10.115.10.248`), separate from application share `10.1.2.22` and database host `mysql04`.
- Archive: `C:\STMS\Backups\2026-08-18-a2db0a9\stms-20260818-032034.zip`.
- Archive size: 881,283 bytes.
- SHA-256: `4C2E3B8919B68E5281BF33C28BDF69491050D73B5F9BEC8D7463108294BCA074`.
- Encryption key custody: Windows Credential Manager target `STMS_Backup_20260818_a2db0a9`; the secret is not stored in Git.
- Restore target: disposable MySQL 8 Docker container and an isolated upload directory on the workstation.
- Integrity: encrypted archive extraction and manifest checks passed.
- Row evidence after restore: 17 users, 60 events, 8 participants and 24 matches.
- Upload evidence after restore: 54 files.
- Restored environment health check: database, cache, queue and disk all `ok`.
- Measured restore time: 7.699 seconds, below the two-hour RTO target.
- Cleanup: the isolated MySQL container was stopped and auto-removed. The disposable restored upload copy is not release evidence and should be removed separately; the encrypted archive is retained.

This is a point-in-time off-host backup. A recurring production schedule and approved retention owner remain operational responsibilities.

## Multi-worker Staging Load Test

- Stack: isolated Docker Nginx + PHP-FPM/Supervisor app, MySQL 8 and Redis 7.
- Isolation: localhost port `18080`, dedicated Compose project, disposable database/Redis and no production storage mount.
- Scenario: authenticated health/login/session/dashboard workload with 10 dedicated staging users, 10 VUs for 30 seconds.
- Final checks: 1,150 passed, 0 failed (`rate=1.0`).
- HTTP request failure rate: `0` against threshold `<0.01`.
- HTTP request duration p95: `81.543 ms` against threshold `<750 ms`.
- Summary artifact: ignored local artifact `test-results/k6-staging-a2db0a9-rerun.json`.
- Cleanup: all three staging containers, network and disposable volumes were removed after the run.

The first diagnostic run exposed cookie reset between k6 iterations and a missing checks threshold. The scenario now sets `noCookiesReset: true` and enforces `checks: rate==1`, preventing false-green runs.

## Deployment Hardening Discovered by the Drill

- Added portable Predis support because the Windows PHP runtime has no `redis` extension while the configured Redis server is reachable.
- Removed the broken PECL Redis compilation path from the Dockerfile.
- Created the Supervisor log directory required at container startup.
- Added an isolated staging Compose definition and excluded public uploads/backups from Docker build context.
- Production Compose now requires secure runtime, off-host backup, Redis, SMTP and health-token settings.

Repository CI and actual production cutover evidence are recorded separately. This drill does not prove a production deployment or real mail delivery.
