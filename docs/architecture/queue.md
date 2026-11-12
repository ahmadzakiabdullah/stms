# Queue Architecture

## Current Implementation

- Notifications implement queue behavior with retry/backoff/timeout and after-commit expectations.
- Tenant-aware queue middleware binds and clears organization context.
- Driver is environment-controlled; Docker/production example use Redis.
- Supervisor config (`docker/supervisord.conf`) runs `[program:queue-worker]` (`queue:work redis --timeout=90`), a dedicated `[program:data-transfer-worker]` (`queue:work data-transfers --queue=data-transfers --timeout=900`, `stopwaitsecs=960` so in-flight transfers can finish on shutdown) and `[program:scheduler]` (`schedule:work`).

## Scheduled Tasks

Defined in `routes/console.php`:

| Command | Schedule | Condition |
|---|---|---|
| `stms:dashboard-snapshot` | daily 23:55, `withoutOverlapping`, `onOneServer` | always |
| `stms:backup` | daily 02:00, `withoutOverlapping`, `onOneServer` | `config('app.backup.enabled')` |
| `stms:health-check` | every five minutes, `withoutOverlapping` | `config('app.health.monitor_enabled')` (`HEALTH_MONITOR_ENABLED`) |

Scheduler liveness still requires external dead-man monitoring; a defined schedule is not evidence that it runs in production.

The runtime workspace uses the **database** queue. The audited 32 database-notification jobs were processed on 17 August 2026; the post-run state was 0 pending / 0 failed and health passed. A continuously supervised production worker is still required—one successful drain is not dead-man evidence.

## Production Requirements

- `QUEUE_CONNECTION=redis`
- supervised workers restarted after deploy
- failed-job and backlog alerting
- health/dead-man monitoring for worker and scheduler
- job payloads carry explicit tenant context
- idempotent handling where retries can duplicate side effects

PDF generation remains synchronous. Excel exports and participant imports also
have an asynchronous `DataTransfer` backend; UI migration and production worker
activation remain open. Jobs use the tenant middleware, requester authorization
and a dedicated connection whose retry interval exceeds the job timeout.
See [data transfers](data-transfers.md) for worker setup and remaining limits.
