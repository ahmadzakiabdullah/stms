# Operational Assurance Drill — 5 August 2026

> **Historical evidence.** These results apply to commit `ae42a50` and an isolated drill environment. They do not prove the 17 August working tree or current production release-ready; see the [current audit](../audits/2026-08-17-full-project-and-production-audit.md).

This record captures the browser, restore, load, and alert-delivery checks executed against commit `ae42a50`. No production data was modified or copied out of the production database.

## Outcome Summary

| Drill | Outcome | Evidence / limitation |
|---|---|---|
| Connected-CI Playwright/axe | Passed | GitHub Actions run `30880392776`; `browser-e2e` completed successfully with all six desktop/mobile journeys. The overall workflow failed only because the separate npm audit job found dependency advisories. |
| Windows Playwright repeat | Partial: 4/6 passed | The faculty/dean journey failed on desktop/mobile after authenticated navigation requested chunks under `/portal/build/...` and received 404 in the temporary root-hosted Windows environment. The dedicated connected-CI result remains authoritative. |
| MySQL encrypted restore | Passed | A sanitized 3.47 MB SAF dataset, larger than the measured 2.66 MB production schema, was backed up with AES-256 and restored into a second isolated MySQL 8 database in 2.977 seconds. All 56 migrations were `Ran`; health was `ok`; counts matched: 19 users, 30 events, 240 event participants, and 2,864 squad members. |
| Authenticated k6 | Functional checks passed; latency threshold failed | Corrected in-memory CSRF/session handling produced 190/190 successful checks and 0% HTTP errors at 10 VUs for 30 seconds. p95 was 4.24 seconds against the 750 ms target on the single-process Laravel development server. A multi-worker staging run is still required. |
| Critical alert transport | Local transport passed; external delivery pending | A controlled disk-health degradation exited `1` and delivered a 928-byte critical Slack-format POST containing `STMS system health is degraded` to a localhost receiver. No real Slack/Papertrail endpoint or secret was configured, so operator receipt remains unverified. |

## Findings

1. `tests/performance/smoke.js` does not currently complete an authenticated login. It searches for a missing CSRF meta element and hardcodes `laravel_session`; Laravel exposes `XSRF-TOKEN` and the configured session cookie was `laravel-session`. The corrected behavior was validated in memory but still needs a committed code fix and regression execution.
2. `stms:restore --force` still presents an interactive confirmation. Automation must explicitly supply confirmation; the runbook must not imply that `--force` alone is non-interactive.
3. The Windows root-hosted browser repeat is not equivalent to the `/portal` deployment and cannot replace connected-CI evidence.
4. Production data was measured only through `information_schema` (2.66 MB, 32 tables). A full production dump was deliberately not copied to local temporary storage.

## Remaining Release Evidence

- Commit the authenticated k6 CSRF/session-cookie correction.
- Run the corrected authenticated scenario against an approved multi-worker staging deployment and meet or consciously revise the p95 threshold with evidence.
- Configure a real external alert destination and confirm receipt by an operator.
- Complete an approved off-host backup copy and isolated restore using an actual production backup. The sanitized MySQL drill validates mechanics and RTO, not production-backup custody or off-site recovery.

All temporary databases, archives, containers, and test servers created for this drill were removed afterward. Grafana k6 2.1.0 was installed on the validation workstation.
