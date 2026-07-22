# Monitoring

Production monitoring is **planned but not yet implemented**. The codebase includes placeholders and configuration stubs for integration with [Sentry](https://sentry.io) for error tracking and performance monitoring.

When implemented, Sentry will capture all unhandled exceptions and log channels will be configured to forward `error` and above levels to Sentry. Performance tracing will monitor slow database queries, N+1 problems, and Inertia page render times. The Sentry Laravel SDK (`sentry/sentry-laravel`) is listed as a suggested dependency in `composer.json`.

Beyond Sentry, the following monitoring pillars should be addressed as the platform matures:
- **Uptime monitoring** (e.g., Pingdom, Health checks via `spatie/laravel-health`)
- **Database performance** (slow query log, `EXPLAIN` analysis)
- **Server metrics** (CPU, memory, disk via New Relic or similar)
- **Alerting** (Slack, email notifications for critical errors)

No monitoring dashboards or uptime checks are currently deployed.
