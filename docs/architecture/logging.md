# Logging

STMS uses Laravel's built-in logging system, configured via `config/logging.php`. The default channel is `stack`, which writes to daily log files in `storage/logs/`. The `LOG_LEVEL` environment variable controls verbosity; `debug` is used in local development and `error` in production.

All CRUD operations in the Service Layer are recorded with `Log::info()` calls. Each log entry includes the authenticated user's ID, the organization context, the affected model type and ID, and a brief description of the action (e.g., `User {id} created tournament {id} in organization {org_id}`). This provides an audit trail for operational troubleshooting without requiring a dedicated audit table.

Exceptions are logged using `Log::error()` within `try/catch` blocks, capturing the exception message, stack trace, and relevant request context. The log channel is production-ready and can be swapped to external services (Papertrail, Logtail, etc.) by changing the `LOG_CHANNEL` environment variable. Logs are rotated daily to prevent disk exhaustion.
