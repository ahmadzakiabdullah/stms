# STMS — Sports Tournament Management System

STMS ialah platform pengurusan kejohanan sukan multi-tenant. Repository ini mengandungi implementasi web SAF UTeM 2026 menggunakan Laravel + React/Inertia.

## Status Semasa

MVP produk beroperasi; verifikasi lokal working tree dipisahkan daripada connected CI baseline `dd559c56e`. Deployment production masih menunggu tindakan operator/owner:

- 173 application routes, 71 migration files, 42 controller files, 47 Inertia pages dan 103 PHP test files.
- Full native PHPUnit terasing lulus 559/559 (2,738 assertions) dan production build/budget lulus pada runner local disk; network share masih tidak sesuai untuk native Rolldown.
- Composer/npm audit 0 advisory/vulnerability selepas remediasi dependensi (league/commonmark 2.10.1, maatwebsite/excel 3.1.70, regenerasi lockfile npm).
- Playwright/axe mempunyai 12 journeys pada desktop/mobile (24 cases); bukti browser semasa direkod dalam `CURRENT_STATE.md`.
- Runtime workspace `production` tidak sepadan dengan baseline Redis/session/verification yang didokumenkan.
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
- Public Athletes & Teams directory dengan profile performance berasaskan match rasmi
- Athlete-level scoring events untuk Hockey dan Football/Soccer, termasuk roster validation dan public scorer display
- Docker, GitHub Actions, health checks dan encrypted backup tooling
- Non-destructive `stms:release-preflight` dan release evidence template untuk handoff operator

REST API, accreditation, live scoring, mobile app, advanced analytics dan AI kekal deferred.

## Portal Production

<https://saf.utem.edu.my/> ialah homepage single-page dengan anchor sections untuk Sports, Schedule, Results dan Medal standings. `/news`, `/downloads`, `/faq`, `/about`, `/general-information`, `/jawatankuasa-induk`, `/jawatankuasa-pelaksana`, `/pengerusi-permainan`, `/tarikh-penting` dan `/contact-us` ialah halaman maklumat awam berasingan.

Route lama `/sports-programme`, `/medal-tally` dan `/schedules` kini 404; `/matches`, `/results` dan `/live` redirect ke `/schedule`. Pengurusan match berada di `/manage/matches`, keputusan di `/results/manage`, dan roster awam di `/athletes`.

## Current Public and Management Routes

- `/athletes` dan `/athletes/{id}` — public roster directory dan athlete performance.
- `/schedule` — satu-satunya public schedule/results page; `/matches`, `/results` dan `/live` redirect ke sini.
- `/manage/matches` — match management; `/results/manage` — score dan scorer management.

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
