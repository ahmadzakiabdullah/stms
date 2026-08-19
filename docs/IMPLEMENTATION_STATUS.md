# Implementation Status

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
| Results/Rankings | Implemented | Contract/registry strategies + validated session/tournament rules |
| Exports/Reports | Implemented | PDF, Excel, match sheet, reports |
| Notifications | Implemented | Queueable database notifications |
| Settings/Activity logs | Implemented | Termasuk contact rasmi tenant-editable; operational audit trail bukan immutable compliance ledger |
| Public portal | Implemented | Homepage berseksyen + `/matches`, directory (`/sports`, `/schedule`, `/results`, `/faculties`, `/venues`, `/live`), info (`/news`, `/downloads`, `/faq`, `/about`) dan `/contact-us`; pengurusan dalaman di `/manage/matches` dan `/manage/sports` |
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

Production <https://saf.utem.edu.my/> menyediakan `/`, `/contact-us` dan `/login`. Homepage memaparkan 1–31 Oktober 2026, 23 sports with events, 30 events, 8 faculties, 12 matches dan 0 completed results pada 17 Ogos 2026.

Public registration ialah 404. Legacy `/sports-programme`, `/medal-tally` dan `/schedules` ialah 404; `GET /results` ialah 405.

> **Catatan 19 Ogos 2026:** repository working tree kini menambah halaman awam `/matches`, `/sports`, `/schedule`, `/results`, `/faculties`, `/venues`, `/live`, `/news`, `/downloads`, `/faq` dan `/about`, serta `/manage/matches` untuk fungsi pengurusan dalaman. Deployment release candidate ke production belum dilakukan (lihat `CURRENT_STATE.md`).

## Deferred

- Accreditation
- Live scoring/realtime
- Mobile app
- REST API `/api/v1`
- Advanced analytics/AI

Rujuk [audit penuh](audits/2026-08-17-full-project-and-production-audit.md) dan [`../TODOS.md`](../TODOS.md).
