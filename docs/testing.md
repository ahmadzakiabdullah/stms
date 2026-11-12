# Testing Strategy

> Current focused regression evidence (21 Ogos 2026): scorer/result `20/20` tests with `57` assertions and public portal `19/19` tests with `355` assertions. The full baseline and CI entries below are historical until a newer release-candidate run is recorded.

> Status release/CI semasa direkodkan dalam [`CURRENT_STATE.md`](../CURRENT_STATE.md). Audit 17 Ogos 2026 dan CI #112 ialah bukti sejarah.

## Inventori Semasa (12 November 2026)

- Repositori mengandungi **104 fail ujian PHP**.
- `tests/e2e/critical-journeys.spec.ts` mengandungi **12 ujian Playwright/axe**, dijalankan pada projek desktop dan mobile (**24 kes**) menggunakan SQLite terasing.
- Bilangan ujian/assertion PHPUnit dan keputusan gate terkini mesti diambil daripada run terbaru yang direkodkan dalam `CURRENT_STATE.md`, bukan daripada fail ini.

## Bukti Sejarah

- 17 September 2026: PHPUnit **508 ujian / 2,478 assertions** lulus pada working tree ketika itu; Pint, TypeScript, Vite build, bundle budget, inventory/tenant guards dan dependency audits lulus.
- 18 Ogos 2026: Playwright/axe **8/8 journeys** (suite lama) lulus; connected [CI #112](https://github.com/ahmadzakiabdullah/stms/actions/runs/32097257726) pada commit `4b04c46` mengesahkan dependency audit, lint, test, 75.03% PCOV statement coverage, build dan browser E2E. CI kini menguatkuasakan ratchet coverage 74.5%.
- Smoke test deployment sebenar kekal wajib untuk setiap release.

## Strategi

Gunakan Feature Tests untuk aliran HTTP, Inertia, pengesahan, policy dan pengasingan tenant; gunakan Unit Tests untuk Action, Service dan pengiraan domain. Ujian cross-tenant perlu membuktikan respons/payload sebenar—sekadar membandingkan dua ID organisasi bukan bukti pengasingan.

Kawasan wajib:

1. akses positif dan negatif untuk setiap capability;
2. `viewAny`, `view`, `create`, `update`, `delete` dan tindakan khusus;
3. data Organisasi A tidak muncul kepada pengguna Organisasi B;
4. lifecycle `TenantContext` bagi HTTP dan queue;
5. ranking, draw, fixture dan result termasuk keadaan konflik;
6. portal awam tidak mendedahkan medan dalaman atau PII;
7. accessibility dan smoke test untuk route awam yang benar-benar wujud.

## Arahan Quality Gate

```bash
php vendor/bin/pint --test
php artisan test
npm run check:inventory
npm run check:tenant-bypasses
npm run typecheck
npm run build
npm run build:budget
npm run test:e2e
composer audit --locked --no-interaction
npm audit --audit-level=high
```

Pada Windows UNC, gunakan `cmd.exe /d /c "pushd \\server\share\project && ..."`. Pastikan E2E menggunakan `ASSET_URL` yang sama dengan test `APP_URL` supaya browser tidak mengambil asset production.

## Release Rule

Semua gate mesti lulus pada commit yang sama dalam CI. Bilangan ujian, assertion, SHA commit dan artefak build hendaklah direkodkan dalam `CURRENT_STATE.md`; bukti daripada commit lama tidak boleh dipindahkan kepada working tree semasa.
