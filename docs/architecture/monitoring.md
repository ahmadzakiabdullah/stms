# Monitoring

STMS provides an application health endpoint and a scheduled internal health command. `GET /health` checks database connectivity, cache read/write, queue backlog/failed jobs, and free disk space. It returns HTTP 503 when degraded and exposes status/latency without returning exception messages.

When implemented, Sentry will capture all unhandled exceptions and log channels will be configured to forward `error` and above levels to Sentry. Performance tracing will monitor slow database queries, N+1 problems, and Inertia page render times. The Sentry Laravel SDK (`sentry/sentry-laravel`) is listed as a suggested dependency in `composer.json`.

Beyond Sentry, the following monitoring pillars should be addressed as the platform matures:
- **Uptime monitoring** (e.g., Pingdom, Health checks via `spatie/laravel-health`)
- **Database performance** (slow query log, `EXPLAIN` analysis)
- **Server metrics** (CPU, memory, disk via New Relic or similar)
- **Alerting** (Slack, email notifications for critical errors)

`php artisan stms:health-check` performs the same checks, exits non-zero when degraded, and writes a critical log entry. With `HEALTH_MONITOR_ENABLED=true`, Laravel Scheduler runs it every five minutes. Production must run `schedule:work` or invoke `schedule:run` every minute; Docker runs `schedule:work` under Supervisor.

Environment thresholds are `HEALTH_MAX_PENDING_JOBS`, `HEALTH_MAX_FAILED_JOBS`, and `HEALTH_MIN_DISK_FREE_MB`.

An external uptime/alerting provider must poll `/health` and notify operators on HTTP 503. Critical logs should also be shipped to an external destination. A hosted dashboard/APM product remains deployment-specific and is not bundled.
