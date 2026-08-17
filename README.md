# STMS — Sports Tournament Management System

STMS ialah platform pengurusan kejohanan sukan multi-tenant. Repository ini mengandungi implementasi web SAF UTeM 2026 menggunakan Laravel + React/Inertia.

## Status Semasa

MVP produk beroperasi dan quality gate repository semasa hijau. Deployment production masih menunggu tindakan operator/owner:

- 126 routes, 61 migrations, 39 controllers, 38 Inertia pages dan 94 PHP test files.
- PHPUnit 434/434 (1,937 assertions), Pint, TypeScript, Vite build, bundle budget dan tenant-bypass check lulus.
- Composer/npm audit bersih selepas Guzzle/PSR-7 security update.
- Playwright/axe lulus 8/8 pada desktop/mobile menggunakan SQLite terasing.
- Runtime workspace `production` tidak sepadan dengan baseline Redis/session/verification/CSP yang didokumenkan.
- Tiada release tag.

Rujuk [`CURRENT_STATE.md`](CURRENT_STATE.md) dan [audit penuh 17 Ogos 2026](docs/audits/2026-08-17-full-project-and-production-audit.md).

## Capability MVP

- Organization, users, RBAC, settings dan activity logs
- Session, tournament, sports, categories dan events
- Participant/faculty registration, squad quotas/import dan dean verification
- Participation confirmation serta printable team-registration forms
- Draw, pools, fixtures, results, rankings, exports dan reports
- Queued in-app notifications dan role-aware dashboards
- Standard/inverse participant logos dengan sanitized upload
- Public SAF homepage, schedules/results/medal sections dan Contact page dengan butiran rasmi tenant-editable
- Docker, GitHub Actions, health checks dan encrypted backup tooling

REST API, accreditation, live scoring, mobile app, advanced analytics dan AI kekal deferred.

## Portal Production

<https://saf.utem.edu.my/> ialah homepage single-page dengan anchor sections untuk Sports, Schedule, Results dan Medal standings. `/contact-us` ialah satu-satunya halaman maklumat awam berasingan.

Route lama `/sports-programme`, `/medal-tally` dan `/schedules` kini 404; `GET /results` bukan route public. Snapshot production pada 17 Ogos menunjukkan pertandingan 1–31 Oktober 2026, 23 sukan aktif, 30 events, 8 fakulti, 12 matches dan 0 results.

## Technology

- PHP `^8.4`, Laravel `13.23.0`
- React `18.3.1`, Inertia React `2.3.25`, TypeScript `5.9.3`
- Tailwind CSS `3.4.19`, local shadcn/Radix components, Lucide
- Vite `8.0.16`
- MySQL, Spatie Permission dan Spatie Activity Log
- Column-based multi-tenancy menggunakan `organization_id` + `TenantContext`

## Development

Workspace UNC perlu dijalankan melalui mapped drive atau `pushd`:

```powershell
cmd.exe /d /c 'pushd "\\server\share\saf" && composer install'
cmd.exe /d /c 'pushd "\\server\share\saf" && npm ci'
```

Quality gates:

```bash
php artisan test
vendor/bin/pint --test
npm run typecheck
npm run check:tenant-bypasses
npm run check:inventory
npm run build
npm run build:budget
composer audit --locked --no-interaction --abandoned=fail
npm audit --audit-level=high
```

Jangan jalankan `migrate:fresh --seed` pada production. Demo seeding memerlukan opt-in dan menggunakan account data yang tidak sesuai untuk production.

## Start Here

1. [`CLAUDE.md`](CLAUDE.md)
2. [`AGENTS.md`](AGENTS.md)
3. [`CURRENT_STATE.md`](CURRENT_STATE.md)
4. [`TODOS.md`](TODOS.md)
5. [`ROADMAP.md`](ROADMAP.md)
6. [Documentation index](docs/README.md)

## License

MIT.
