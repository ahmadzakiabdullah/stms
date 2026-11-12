# Concurrency and duplicate-request testing

The repository now contains deterministic race-regression coverage in
`tests/Feature/ConcurrencyRegressionTest.php` for the five P1 paths:

- draw fixture generation is idempotent after the first successful generation;
- result entry rejects a second result for the same match;
- repeated bulk registration import creates one event-participant row and reports the duplicate;
- repeated approval cannot overwrite the already-approved result state;
- a stale correction cannot change a locked result;
- schedule conflict validation is repeated inside the match write transaction.

The corresponding write paths use database row locks and existing unique indexes
as the final guard. Match writes serialize on the organization row so conflict
validation and insertion share the same critical section.

These tests are deterministic regression tests, not proof of multi-worker
production behavior. A release still needs an approved staging run with at least
two workers/processes, a real MySQL/Redis topology, concurrent requests for each
scenario, and evidence of zero duplicate rows, rejected conflicts and stable
state transitions. That run remains a deployment/release activity because the
current network-share workspace cannot reliably start the local Laravel test
runner or a multi-worker server.
