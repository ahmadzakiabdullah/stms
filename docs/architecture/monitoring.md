# Monitoring

> Repository monitoring contract prepared on 21 August 2026. The matrix below defines the minimum production signal, threshold and response. It is not evidence that an external dashboard, alert route or on-call roster is active.

## Endpoint dan Command

- `GET /up` ialah liveness Laravel asas.
- `GET /health` memeriksa database, cache, queue dan ruang disk. Endpoint ini dilindungi token dan sengaja memberi 404 tanpa token.
- `php artisan stms:health-check` menyediakan semakan operasi yang sama untuk scheduler/CLI.
- `php artisan stms:release-preflight --json` memeriksa prerequisite release secara tidak merosakkan, termasuk DB/Redis connectivity, backup freshness dan konfigurasi monitoring.

Semasa audit asal 17 Ogos, health command berstatus `ok` walaupun mempunyai 32 queued jobs. Backlog itu kemudian diproses kepada 0 pending/0 failed dan health kekal lulus. Satu drain berjaya masih bukan bukti worker/scheduler diselia secara berterusan.

## Belum Dibuktikan

Sentry/APM, alert routing, external uptime monitor, dashboard latency, slow-query alert, queue-age alert dan on-call response tidak dibuktikan aktif. Dokumen atau dependency cadangan bukan bukti operasi.

Minimum production monitoring hendaklah merangkumi availability, error rate, request latency, DB/cache latency, queue depth dan oldest-job age, failed jobs, disk, backup freshness, certificate expiry dan CSP reports. Setiap alert mesti mempunyai pemilik dan runbook.

## Production Monitoring Matrix

| Signal | Measurement and threshold | Severity | Owner / first response | Evidence required |
|---|---|---:|---|---|
| Availability | `/up` is not HTTP 200 for two consecutive five-minute probes | P1 | Application operator; verify IIS/app host and open incident | Probe history and alert receipt |
| Application errors | HTTP 5xx rate at or above 5% for five minutes, or any sustained 5xx on `/up` or `/health` | P1 | Application operator; inspect correlated request IDs and recent deployment | Error-rate chart/log query and incident reference |
| Request latency | Production p95 above 750 ms for ten minutes on authenticated critical routes, or above the approved journey threshold | P2 | Application operator; check DB, Redis and worker saturation | APM/access-log percentile and k6 or smoke result |
| Database/cache latency | p95 above 250 ms for ten minutes, or health check cannot complete | P1 | DBA plus application operator; do not change grants during incident | DB/cache latency evidence and DBA acknowledgement |
| Queue backlog | Pending jobs above `HEALTH_MAX_PENDING_JOBS` (currently 100), oldest job above 10 minutes, or any failed job | P1 | Queue operator; inspect worker, scheduler and failed job payload metadata | `stms:health-check`, queue metrics and worker logs |
| Disk capacity | Free space below `HEALTH_MIN_DISK_FREE_MB` (currently 1024 MB) | P1 | Infrastructure operator; protect logs/backups before cleanup | Host disk metric and cleanup/change record |
| Backup freshness | Latest verified backup older than 24 hours, checksum failure, or off-host copy unavailable | P1 | Backup operator; stop any destructive cleanup and verify custody | Backup manifest, checksum and off-host reference |
| TLS certificate | Less than 14 days to expiry, or certificate/chain probe failure | P1 | Infrastructure operator; renew through approved certificate process | Certificate check and renewal ticket |
| CSP reports | Repeated new violation from a production route, or five equivalent violations in 15 minutes | P2 | Application/security owner; triage before changing policy | Sanitized CSP report sample and disposition |
| Scheduled health | `stms:health-check` has no successful execution for 15 minutes while monitoring is enabled | P1 | Queue/scheduler operator; verify scheduler and task output | Scheduler log and health command output |

Thresholds are starting points and require owner approval before production activation. A threshold change must record the reason, effective time and new rollback/escalation rule.

## Required Alert Routing

Before release approval, assign named owners (not only team names) for application, database, infrastructure, backup and security alerts. The route must create an operator-visible notification, retain the original event, and record acknowledgement and recovery timestamps. The existing GitHub Actions uptime workflow covers only `/up` liveness; it does not replace application, queue, backup or certificate monitoring.

For every alert, record:

1. alert name, environment and first-seen timestamp;
2. observed value, threshold and affected route/component;
3. owner acknowledgement timestamp;
4. incident/change reference and actions taken;
5. recovery timestamp and a short follow-up if the threshold was breached repeatedly.

See [operations runbook](../deployment/operations-runbook.md) for triage, escalation, rollback and worker/scheduler recovery.
