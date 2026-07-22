# Audit Logging

Audit logging in STMS is currently handled through a combination of **soft deletes** and **service-layer logging**, rather than a dedicated audit table.

Soft deletes are enabled on all core models (Organization, Tournament, Event, Fixture, Participant, User). When a record is "deleted", it is retained in the database with a `deleted_at` timestamp. This provides a basic audit trail — deleted records remain queryable and restorable. The `deleted_by` column (nullable UUID, referencing the user who performed the deletion) is included on models that require stronger accountability.

Beyond soft deletes, the Service Layer uses `Log::info()` to record all CRUD operations (see [logging.md](logging.md)). These logs capture `who`, `what`, `when`, and `in which organization`. For sensitive operations (role changes, permission grants), additional context is logged.

A formal audit log feature — with a dedicated `audit_logs` table, immutable records, and a queryable UI — is planned for a future milestone. Until then, the combination of soft deletes and structured application logging provides sufficient traceability for MVP operations.
