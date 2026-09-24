# ADR-011: Data transfer authorization and queue isolation

**Status:** Accepted  
**Date:** 2026-09-22

## Decision

Data transfer status, failure reports and downloads are private to the requesting
user within the recorded organization. `DataTransferPolicy` also checks the user's
active flag and current permission for the underlying operation. Faculty imports
must still reference the faculty representative's linked participant. The worker
rechecks these permissions before processing, so revocation applies to queued work.

Jobs serialize the transfer ID and organization ID and implement `TenantAwareJob`.
The existing queue middleware binds and clears the tenant; all transfer lookups,
including the failed callback, explicitly scope by organization. No additional
tenant-bypass allowlist entry is needed. Inputs and outputs use tenant partitions;
downloads additionally require the transfer's own output directory.

An organization row lock serializes idempotency-key creation. Reuse is allowed
only for the same requester, type and payload. A per-transfer queue overlap lock
prevents simultaneous processing; redelivery can recover an abandoned running
transfer after that lock expires. Successful states are terminal, including
imports completed with row errors.

The dedicated `data-transfers` connection uses the configured database/Redis
backend (sync in isolated tests), a 900-second job timeout, a 960-second overlap
lock and a 1020-second retry interval. Operators must supervise a worker for this
connection and queue before enabling the asynchronous UI.

## Limits

The synchronous UI remains available. This decision does not certify production
workers, Redis, distributed concurrency, output retention or browser polling.
These remain open in `TODOS.md`.
