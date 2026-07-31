# Testing Strategy

STMS uses PHPUnit for both Feature and Unit tests. The testing philosophy is to cover every feature with at least one Feature test that exercises the full request cycle (route → controller → service → database), and supplement with Unit tests for isolated business logic, Services, and Actions.

## Test Setup

- **RefreshDatabase** trait is used on every test class to ensure a clean database state between tests.
- **Factories** are defined for all domain models with realistic default states and relationships.
- **Test traits** provide reusable setup — `CreateApplication` (bootstraps the app), `ActAsSuperAdmin` (bypasses tenancy), `ActAsOrgUser` (creates authenticated tenant user), and `RefreshTenantDatabase` (ensures tenant-scoped data is recreated).
- **Database Seeds** — a common `TestSeeder` creates the minimal required reference data (default permissions, roles, a base organization).

## Feature Tests

Feature tests simulate HTTP requests using Laravel's `get`, `post`, `put`, `delete` helpers and assert on response status, redirects, session errors, and database state. They test:

- Authentication flows (login, registration, verification, password reset)
- Authorization (permission denied responses, policy enforcement)
- CRUD operations (create, read, update, soft-delete, restore)
- Tenant isolation (cross-organization access returns 403)

## Unit Tests

Unit tests cover Service classes and Action classes in isolation. Domain models are mocked or constructed via factories. These tests focus on business logic — ranking calculations, schedule conflict detection, status transitions — without HTTP or database overhead where possible.

## Running Tests

```bash
php artisan test              # all tests
php artisan test --filter=Tournament  # specific feature group
```

## Coverage and Browser Assurance

CI installs PCOV and publishes `coverage.xml` in Clover format. The first connected CI run establishes the measured baseline; only then should a minimum threshold be added and ratcheted upward.

Playwright runs critical super-admin, faculty, and dean journeys on desktop and mobile Chromium. Axe scans login and dashboard against WCAG A/AA tags and fails on serious or critical violations. Failure traces, screenshots, video, and the HTML report are retained as CI artifacts.

```bash
npm run test:e2e
npm run test:e2e:ui
```
