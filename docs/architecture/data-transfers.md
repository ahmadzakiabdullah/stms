# Asynchronous data transfers

Large Excel exports and imports use the tenant-scoped `data_transfers` record
and `ProcessDataTransfer` queue job. The record is the client contract for
progress and failure reporting:

- `pending` → `running` → `completed`;
- `completed_with_errors` keeps a row-level `failure_report` for import work;
- `failed` is reserved for an infrastructure or queue failure;
- `progress`, `processed` and `total` are updated during import processing;
- an optional organization-scoped `idempotency_key` returns the original
  transfer instead of dispatching a duplicate job.

Available endpoints are:

- `POST /exports/queue` for fixtures, results, rankings and medal Excel files;
- `POST /participants/import/queue` after a participant preview token exists;
- `POST /event-participants/import/queue` for event registration files;
- `GET /data-transfers/{id}` for status/progress/failure report;
- `GET /data-transfers/{id}/download` after a successful export.

The existing synchronous routes remain available for small transfers during the
UI migration. Production must run a persistent queue worker with the configured
retry policy, private storage for source/output files, and cleanup monitoring.
Transfer IDs and status responses are tenant-scoped; a user from another
organization receives a not-found response.

## Authorization and processing

Status and downloads require `DataTransferPolicy`: the active requesting user,
the same organization and current export/import permission. Another organization
gets 404; another user in the same organization gets 403. Export queue/status
errors use JSON (401/403/404/422), while existing Inertia import forms keep redirect
validation. Worker execution rechecks the requester's permission.

`ProcessDataTransfer` implements `TenantAwareJob` and uses `SetTenantContext` plus
an overlap lock. The failed callback is explicitly tenant-scoped and cannot change
a successful transfer. Idempotency-key creation is serialized on the organization
row and rejects reuse with another requester, type or payload.

Private paths are `transfers/{organization}/input/...` and
`transfers/{organization}/output/{transfer}/...`; source/download access rejects
foreign partitions and traversal. Participant imports re-derive tenant/session
fields and validate the session within that tenant.

Worker command after approved operational activation:

```sh
php artisan queue:work data-transfers --queue=data-transfers --timeout=900 --tries=3
```

The dedicated connection defaults to `QUEUE_CONNECTION` (database/Redis; sync for
tests). Its retry interval is 1020 seconds, above the 900-second timeout and
960-second overlap lock. Configure `DATA_TRANSFER_QUEUE_DRIVER` and
`DATA_TRANSFER_QUEUE_CONNECTION` only when a separate backend is required.
Production activation remains held. See [ADR-011](../adr/ADR-011-data-transfer-authorization-and-queue.md).
