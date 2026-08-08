# Audit Logging

Audit logging uses `spatie/laravel-activitylog` with the `activity_log` table, an authenticated activity-log UI, and explicit activity records for selected operations such as draws. Service-layer application logs and soft deletes provide additional operational history.

Soft deletes are enabled on all core models (Organization, Tournament, Event, Match, Participant, User). When a record is "deleted", it is retained in the database with a `deleted_at` timestamp. This provides a basic audit trail — deleted records remain queryable and restorable. The `deleted_by` column (nullable UUID, referencing the user who performed the deletion) is included on models that require stronger accountability.

Beyond soft deletes, the Service Layer uses `Log::info()` to record all CRUD operations (see [logging.md](logging.md)). These logs capture `who`, `what`, `when`, and `in which organization`. For sensitive operations (role changes, permission grants), additional context is logged.

Coverage is not yet uniform across every mutation, and database immutability/retention controls are not enforced. Treat the current facility as an operational audit trail, not a compliance-grade immutable ledger.
