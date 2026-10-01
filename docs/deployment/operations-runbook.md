# Production Operations Runbook

> This runbook is a repository procedure, not proof that production monitoring or an on-call roster is active. Fill in named owners, alert destinations and ticket references before release approval.

## Ownership

| Responsibility | Named owner | Backup owner | Alert destination |
|---|---|---|---|
| Application/IIS and deployment | `<name/role>` | `<name/role>` | `<destination>` |
| Database and grants | `<DBA name/role>` | `<name/role>` | `<destination>` |
| Redis/queue/scheduler | `<name/role>` | `<name/role>` | `<destination>` |
| Backup and restore | `<name/role>` | `<name/role>` | `<destination>` |
| Security/CSP/certificate | `<name/role>` | `<name/role>` | `<destination>` |

Do not put passwords, tokens, reset links or raw personal data in an incident ticket, log export or this document.

## Incident Triage

1. Record the alert, timestamp, affected URL/component, observed value and deployment/change currently in progress.
2. Check `/up` from an external probe and `/health` through the approved token channel. The unauthenticated `/health` response must remain 404.
3. Run `php artisan stms:health-check` on the application host and retain sanitized output. Check database, cache, queue, failed jobs and disk components.
4. Compare the first failure with the deployment, migration, configuration, certificate and infrastructure timelines.
5. Announce impact and assign an owner. Do not make unrelated configuration or database privilege changes during the incident.
6. If data integrity, cross-tenant isolation, credentials or unauthorized access may be involved, escalate immediately to the security owner and preserve evidence.
7. After recovery, record the recovery timestamp, user impact, root cause hypothesis and follow-up action.

### Minimum evidence commands

Run these commands from the deployed application directory (`E:\others\saf` on the current Windows/IIS host, or the immutable release directory on Linux). Store sanitized output with the incident or release record.

```bash
php artisan about
php artisan migrate:status
php artisan stms:health-check
php artisan stms:release-preflight --json --max-backup-age-hours=24
php artisan stms:production-smoke --url=https://saf.utem.edu.my --json
php artisan queue:failed
php artisan schedule:list
```

For `stms:production-smoke`, pass a plain URL, not a Markdown link. If PHP cURL reports `error setting certificate file`, install or point PHP to a valid CA bundle through `curl.cainfo` and `openssl.cafile`; do not disable TLS verification for release evidence.

For PowerShell evidence files:

```powershell
$stamp = Get-Date -Format "yyyy-MM-dd-HHmmss"
php artisan stms:health-check | Out-File -Encoding utf8 "storage/app/health-$stamp.txt"
php artisan stms:release-preflight --json | Out-File -Encoding utf8 "storage/app/preflight-$stamp.json"
php artisan stms:production-smoke --url="https://saf.utem.edu.my" --json | Out-File -Encoding utf8 "storage/app/production-smoke-$stamp.json"
```

Do not attach raw `.env`, database dumps, queue payloads, reset links or personally identifiable participant data to tickets. Redact tokens, email addresses and phone numbers before sharing output outside the approved operator group.

### Quick decision points

| Observation | Immediate action |
|---|---|
| `/up` fails but host is reachable | Check IIS/site binding, PHP worker, application logs and recent deployment; use rollback procedure if deployment-correlated. |
| `/up` is healthy but `/health` is degraded | Identify the failed component; for queue/disk/backup alerts, follow the component procedure and do not report the service as fully healthy. |
| Failed jobs are non-zero | Inspect failed-job metadata without copying sensitive payloads; retry only after identifying whether the job is safe and idempotent. |
| Queue age increases | Check worker processes, scheduler, Redis/database connectivity and deployment restart state; recover workers before increasing concurrency. |
| Database or cache errors | Contact DBA/infrastructure owner; avoid ad-hoc grants, destructive cleanup and manual edits to production data. |
| CSP violation appears after a release | Capture sanitized route/directive evidence, compare the built asset origin and fix the policy/source; do not disable CSP as a first response. |

## Rollback Procedure

Rollback is an approved change, not an emergency shortcut. Confirm the incident owner and rollback authority first.

1. Declare the incident and freeze unrelated deployments/migrations.
2. Capture current SHA, configuration version, recent logs, queue depth and database migration state.
3. Enable maintenance mode only if required to prevent writes during the rollback; communicate the expected interruption.
4. Deploy the last known-good immutable artifact/tag. Do not run Composer against a live UNC path through a drive alias; follow the Windows/IIS guard in the [release runbook](release-runbook.md).
5. Rebuild application caches, then restart PHP/IIS workers and queue workers as appropriate.
6. Run `php artisan stms:health-check`, `/up`, authenticated smoke tests and a tenant-isolation check.
7. If a migration is not backward-compatible, stop and use the approved database recovery plan. Do not run `migrate:rollback` blindly.
8. Exit maintenance mode only after health and smoke checks pass. Keep the incident open until monitoring shows stable recovery.

### Rollback go/no-go checks

Before rollback:

- Confirm the last known-good artifact/tag and its matching `public/build` assets.
- Confirm whether any migration in the failed release wrote irreversible data or changed column meaning.
- Confirm queue workers are paused or gracefully restarted so old workers do not process jobs against mixed code.
- Confirm a fresh backup or infrastructure snapshot exists before any destructive database action.

After rollback:

- `GET /`, `/login` and `/up` must return expected responses.
- `/health` must pass through the token-protected channel and remain 404 without token.
- One authenticated read, one controlled mutation, one queued transfer status check and one cross-tenant negative check must be captured.
- Failed jobs and recent application errors must be reviewed before the incident is closed.

## Worker and Scheduler Recovery

1. Verify the scheduler is running and that `routes/console.php` is registering the health check and backup schedules as expected by configuration.
2. Check worker process status and the queue connection; confirm Redis/database availability before restarting processes.
3. Restart workers using the host's approved process supervisor. Use Laravel's graceful restart mechanism where supported; do not kill processes while they are writing non-idempotent work unless the incident owner approves it.
4. Run `php artisan queue:failed` and inspect counts/ages without exposing payloads. Retry only approved, idempotent jobs.
5. Run `php artisan stms:health-check` and confirm queue depth/failed jobs return within threshold.
6. Confirm the next scheduled health check and backup execution in scheduler logs. A single successful command is not evidence of continuous supervision.

### Expected process supervision

Production workers and scheduler must be supervised by the host, not by an interactive terminal.

Minimum worker command:

```bash
php artisan queue:work --queue=transfers,default --tries=3 --backoff=30 --timeout=120
```

Minimum scheduler command:

```bash
php artisan schedule:run
```

Linux hosts should use systemd/Supervisor plus cron or a systemd timer. Windows/IIS hosts should use Task Scheduler or an approved service wrapper such as NSSM/WinSW. The process definition must record:

- working directory;
- PHP binary path;
- queue connection and queue names;
- restart policy;
- stdout/stderr log path;
- operator responsible for restart and alert response.

After changing worker configuration, run one queued Reports export or import in the UI and confirm `DataTransfer` status reaches `completed` or an expected, user-visible `failed` state with a failure report.

## Backup Alert Response

1. Confirm the latest archive timestamp, checksum and off-host custody reference.
2. If freshness or checksum is invalid, notify the backup owner and stop any retention cleanup that could remove the last known-good copy.
3. Run `php artisan stms:backup` only through the approved maintenance/secret environment and verify the resulting archive and off-host copy.
4. For suspected data loss, use the isolated restore procedure in [backup-restore.md](backup-restore.md); never restore directly over production during initial diagnosis.

### Restore Drill Record

Every restore drill must record:

- source backup filename, timestamp, size and SHA-256;
- environment restored into, application SHA and database engine/version;
- start/end time, achieved RPO and achieved RTO;
- `php artisan migrate:status` and `php artisan stms:health-check` output;
- smoke results for login, tenant isolation, recent participants, registrations, results, uploaded files and queued jobs;
- decision: usable backup, unusable backup or follow-up required.

Use [backup-restore.md](backup-restore.md) for the actual restore commands. Never use production as the first place to test a backup archive.

## Release Evidence Pack

For every production release or emergency corrective change, retain one folder or ticket attachment set containing:

- approved commit SHA/tag and release owner;
- quality-gate evidence from the same commit;
- `stms:release-preflight --json` output;
- migration status before and after deploy;
- backup freshness and restore-drill reference;
- worker/scheduler restart evidence;
- `/up`, tokened `/health`, authenticated smoke, cross-tenant negative and queue/import-export evidence;
- rollback decision, whether used or not;
- changelog/current-state update reference.

## Closure Checklist

- [ ] Alert acknowledged and owner recorded.
- [ ] Impact, timestamps and deployment/change correlation recorded.
- [ ] Health, queue, backup and smoke evidence attached in sanitized form.
- [ ] Recovery verified by external probe where applicable.
- [ ] Rollback or corrective change approved and recorded.
- [ ] Follow-up owner and due date assigned for recurring alerts.
