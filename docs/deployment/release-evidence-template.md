# Release Evidence Record

> Copy this template for each release candidate. Store only sanitized evidence references; never commit secrets, tokens, credentials, raw personal data or complete database dumps.

## Release Metadata

| Field | Evidence |
|---|---|
| Candidate SHA | `<full git SHA>` |
| Proposed tag | `<annotated semantic version>` |
| CI run | `<URL and conclusion>` |
| Target environment | `production` |
| Change window | `<start/end with timezone>` |
| Release owner | `<name/role>` |
| Technical approver | `<name/role>` |

## Repository Gates

- [ ] Working tree was clean before the candidate commit was created.
- [ ] Connected CI passed on the exact candidate SHA.
- [ ] PHPUnit, Pint, inventory, tenant bypass, TypeScript, build/budget, dependency audits, PCOV ratchet and browser E2E passed.
- [ ] Changelog, current state and migration review were approved.

## Runtime Preflight

Command:

```bash
php artisan stms:release-preflight --json --max-backup-age-hours=24
```

| Field | Evidence |
|---|---|
| Execution timestamp | `<ISO-8601>` |
| Overall result | `<ok/error>` |
| Sanitized output location | `<evidence-system reference>` |
| Exceptions approved | `<none or approval reference>` |

The command checks configuration, DB connectivity, Redis PING, mailer configuration, backup freshness/checksum, internal health-monitor settings and public tenant selectors. It does not send mail, prove external alert delivery, run load tests, restore a backup or deploy code.

## Mail and Identity

- [ ] Reset-password request completed using an approved test account.
- [ ] Message arrived through the production transport.
- [ ] Reset link worked once, expired according to policy and did not expose another tenant.

Record timestamp, masked recipient, provider/message reference and operator. Do not record the reset token or full message body.

## Database Least Privilege

- [ ] DBA confirmed the application principal can access only the STMS schema and required metadata.
- [ ] Approved grants are attached in sanitized form.
- [ ] Migration and rollback principals are documented separately where applicable.

| Field | Evidence |
|---|---|
| DBA/operator | `<name/role>` |
| Verification timestamp | `<ISO-8601>` |
| Grants evidence reference | `<location>` |

## Backup and Restore

| Field | Evidence |
|---|---|
| Archive name | `<stms-YYYYMMDD-HHMMSS.zip>` |
| SHA-256 | `<checksum>` |
| Off-host custody reference | `<location/reference without credentials>` |
| Backup timestamp | `<ISO-8601>` |
| Isolated restore environment | `<identifier>` |
| Achieved RPO | `<duration>` |
| Achieved RTO | `<duration>` |

- [ ] Database, uploads, login, tenant isolation and queue processing were verified after isolated restore.

## Load and Monitoring

- [ ] Authenticated multi-worker staging k6 met the approved thresholds: failure rate `<1%` and p95 `<750 ms`, unless a newer approved target is recorded.
- [ ] External uptime/log alert was triggered deliberately and received by the assigned operator.
- [ ] Alert owner and response runbook are recorded.

| Field | Evidence |
|---|---|
| k6 report | `<reference>` |
| Alert receipt | `<reference>` |
| Operator | `<name/role>` |

## Deployment and Smoke Test

- [ ] Exact approved SHA/tag deployed.
- [ ] Maintenance/session cutover plan executed.
- [ ] Migrations completed and queue workers/scheduler restarted.
- [ ] `/`, `/contact-us`, `/login`, dashboard, one authorized read and one controlled mutation passed.
- [ ] Queue, cache, storage, audit log and CSP console/network checks passed.
- [ ] Playwright/axe passed against the deployed release.
- [ ] No new exception, failed job or critical CSP violation observed.

## Approval

| Decision | Owner | Timestamp | Notes |
|---|---|---|---|
| Product approval | `<name>` | `<ISO-8601>` | `<notes>` |
| Technical approval | `<name>` | `<ISO-8601>` | `<notes>` |
| Operations approval | `<name>` | `<ISO-8601>` | `<notes>` |
| Final GO/NO-GO | `<decision>` | `<ISO-8601>` | `<reason>` |
