# Queue Architecture

## Current Implementation

- Notifications implement queue behavior with retry/backoff/timeout and after-commit expectations.
- Tenant-aware queue middleware binds and clears organization context.
- Driver is environment-controlled; Docker/production example use Redis.
- Supervisor config runs queue workers and Laravel Scheduler.

The runtime workspace uses the **database** queue. The audited 32 database-notification jobs were processed on 17 August 2026; the post-run state was 0 pending / 0 failed and health passed. A continuously supervised production worker is still required—one successful drain is not dead-man evidence.

## Production Requirements

- `QUEUE_CONNECTION=redis`
- supervised workers restarted after deploy
- failed-job and backlog alerting
- health/dead-man monitoring for worker and scheduler
- job payloads carry explicit tenant context
- idempotent handling where retries can duplicate side effects

PDF/Excel generation remains synchronous/query-based in current MVP; queue long-running exports before supporting larger datasets.
