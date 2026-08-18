# Testing Strategy

> Baseline parent terakhir disahkan oleh connected CI pada 17 Ogos 2026; working tree 18 Ogos memerlukan CI baharu. Lihat [audit penuh](audits/2026-08-17-full-project-and-production-audit.md).

## Keadaan Semasa

- Repositori mengandungi **94 fail ujian PHP**.
- PHPUnit menjalankan **439 ujian / 1,948 assertions** pada working tree 18 Ogos dan semuanya lulus.
- Pint, TypeScript, Vite build, bundle budget, inventory/tenant guards dan dependency audits lulus.
- Playwright/axe menjalankan **8/8 journeys** pada desktop/mobile menggunakan SQLite terasing.
- Connected [CI #106](https://github.com/ahmadzakiabdullah/stms/actions/runs/32012582828) pada commit `a8df4dd` mengesahkan dependency audit, lint, test, 74.99% PCOV statement coverage, build dan browser E2E semuanya lulus.
- Evidence ini ialah baseline parent; working tree 18 Ogos perlu connected CI pada commit baharu dan smoke test deployment sebenar masih wajib.

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
