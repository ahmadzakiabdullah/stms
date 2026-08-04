# Testing Strategy

## Current baseline

As of 4 August 2026, the repository contains 78 PHP test files. The local suite covers 334 tests and 1,319 assertions across Feature and Unit layers. The final release gate also includes Pint, `tsc --noEmit`, the Vite production build, dependency audits, bundle budgets, Playwright journeys, axe checks, and performance thresholds.

## Test layers

- **Feature tests** exercise authentication, policies, tenant isolation, requests, controllers, Inertia props, redirects, notifications, exports, commands, backups, health checks, and role-specific workflows.
- **Unit tests** cover service logic such as rankings, draws, league/knockout progression, quota rules, assets, and domain CRUD services.
- **Browser tests** cover super-admin, faculty representative, and dean critical journeys on desktop/mobile Chromium with axe checks.
- **Performance tests** enforce a dashboard query budget and provide k6 health/authenticated scenarios.

`RefreshDatabase`, model factories, and `Tests\Traits\CreatesTenantUsers` are the primary reusable test infrastructure. Cross-tenant tests generally expect scoped resources to return 404 and explicit authorization failures to return 403.

## Commands

```bash
php artisan test
vendor/bin/pint --test
npm run typecheck
npm run build
npm run build:budget
npm run test:e2e
```

Use a mapped local drive on Windows because PHP subprocesses cannot reliably execute from a UNC working directory.

## CI and evidence

CI publishes PCOV Clover artifacts and Playwright failure artifacts. The first connected coverage result is still required before setting a ratcheting percentage threshold. Production-sized restore, authenticated load, external alert, and connected-browser drills remain environment-dependent tasks in `TODOS.md`.