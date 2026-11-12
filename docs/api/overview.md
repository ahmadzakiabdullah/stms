# REST API Overview (Future)

> **Status: Not implemented.** The production system is a Laravel/Inertia web application and currently has no `/api/v1` routes.

The files in this directory are placeholders for a deferred, versioned REST API. They are not endpoint documentation and must not be used as evidence that an integration is available.

When the API milestone is approved, follow ADR-007, use URL versioning under `/api/v1`, API Resources, tenant-scoped authorization, consistent error envelopes, OpenAPI documentation, and contract tests. Web behavior and the current domain implementation are documented in `CURRENT_STATE.md` and `docs/architecture/system-overview.md`.

## JSON endpoints yang wujud sekarang

These are web routes in `routes/web.php` (session auth + CSRF, not `/api/v1`) that return JSON today. They are internal to the web UI, not a stable integration contract.

| Method | Path | Route name | Notes |
|--------|------|------------|-------|
| GET | `/data-transfers/{id}` | `data-transfers.show` | Auth. Returns `{data: {id, type, status, progress, processed, total, failure_report, download_url}}`; the failure report is part of this payload. Requester-only (`DataTransferPolicy`). |
| GET | `/data-transfers/{id}/download` | `data-transfers.download` | Auth. File download (not JSON); 409 until the transfer is completed. |
| POST | `/exports/queue` | `exports.queue` | Auth, `throttle:10,1`. Returns transfer status JSON, 202 (new) / 200 (idempotent reuse). |
| POST | `/participants/import/queue`, `/event-participants/import/queue` | `participants.import.queue`, `event-participants.import.queue` | Auth. JSON (202/200) when the request expects JSON, otherwise a redirect. |
| GET | `/up` | — | Laravel framework health route (`bootstrap/app.php`). |
| GET | `/health` | — | `HealthCheckController`; in production requires a matching `X-Health-Token` header, otherwise 404. |