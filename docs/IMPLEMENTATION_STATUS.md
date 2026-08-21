# Implementation Status

> Current repository update: 21 Ogos 2026. Athlete directory, athlete profiles, participant-grouped scorer events and the score-editor workflow are implemented and included in commit `4c4ebf0c`; production cutover evidence remains outstanding.

> Updated 18 Ogos 2026. “Implemented” bermaksud kod/capability wujud; ia tidak menggantikan release-gate evidence.

## Implemented

| Area | Status | Notes |
|---|---|---|
| Laravel/Inertia/React foundation | Implemented | Laravel 13.23, React 18.3, TypeScript, Tailwind 3 |
| Organization + hierarchy | Implemented | Root tenant; registry management super-admin only |
| Users + RBAC | Implemented | 6 active roles, 42 permissions pada workspace |
| Tenant context/scopes | Implemented | User list scoping manual; six-role URL matrix tersedia |
| Session/Tournament/Sport/Category/Event | Implemented | 30 active categories/events pada workspace |
| Participant/Registration | Implemented | Standard + inverse logos |
| Squad/Faculty/Dean workflow | Implemented | Quota, import, approval, printable forms |
| Draw/Pools/Fixtures | Implemented | Version history/rollback; scheduled-fixture regeneration diuji |
| Results/Rankings | Implemented | Contract/registry strategies + validated session/tournament rules; scorer events for configured individual sports |
| Exports/Reports | Implemented | PDF, Excel, match sheet, reports |
| Notifications | Implemented | Queueable database notifications |
| Settings/Activity logs | Implemented | Termasuk contact rasmi tenant-editable; operational audit trail bukan immutable compliance ledger |
| Public portal | Implemented | Homepage, `/schedule`, `/athletes`, athlete profiles, info pages and participant-grouped scorer display; `/matches`, `/results`, `/live` redirect to `/schedule` |
| Docker/CI/Health/Backup | Implemented in repository | Termasuk release preflight baca-sahaja; runtime/operational evidence belum lengkap |

## Explicit Exceptions to Project Defaults

- `settings.id` menggunakan integer auto-increment, bukan UUID.
- `Setting`, `SquadMember` dan `DrawVersion` tidak menggunakan soft deletes.
- `DrawVersion` ialah immutable snapshot by design.
- `User` mempunyai UUID primary key bernama `uuid`; tenant scoping untuk user management dibuat manual.
- REST API belum wujud; `docs/api/` ialah future design notes.

## Current Release Gate

| Gate | Result |
|---|---|
| Inventory / tenant bypass / TypeScript | Pass |
| Vite build + bundle budget | Pass |
| Composer/npm audit | Pass |
| PHPUnit | Pass — 441/441, 2,040 assertions pada working tree 19 Ogos |
| Pint | Pass |
| Playwright/axe | Pass — 8/8 desktop/mobile, isolated SQLite |
| Committed remediation | Pass — logical commits created |
| Release tag | Fail — no release tag |
| Connected CI | Pass — run #112 on `4b04c46`; all six jobs including PCOV ratchet and browser E2E passed |

## Production Snapshot

Production <https://saf.utem.edu.my/> ialah runtime awam yang perlu disahkan selepas cutover. Repository semasa menyediakan `/schedule`, `/athletes`, athlete profiles, `/results/manage` dan scorer events; angka live production tidak dianggap bukti deployment release candidate.

Public registration ialah 404. Legacy `/sports-programme`, `/medal-tally` dan `/schedules` ialah 404; `GET /results` ialah 405.

> **Catatan 21 Ogos 2026:** repository working tree menambah `/athletes`, athlete profiles, scorer events, participant-grouped public scorer display dan `/results/manage` score editor. Deployment release candidate ke production masih perlu disahkan melalui release evidence (lihat `CURRENT_STATE.md`).

## Current Feature Update — 21 August 2026

- Public athlete/team directory with confirmed roster filtering and official team performance is implemented at `/athletes`.
- `match_scoring_events` supports active/confirmed roster validation, score reconciliation and public participant-grouped scorer display.
- Shared responsive public match cards and the simplified result score editor are implemented.

## Deferred

- Accreditation
- Live scoring/realtime
- Mobile app
- REST API `/api/v1`
- Advanced analytics/AI

Rujuk [audit penuh](audits/2026-08-17-full-project-and-production-audit.md) dan [`../TODOS.md`](../TODOS.md).
