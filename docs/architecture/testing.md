# Testing Architecture

## Quality Gates

The release baseline must be regenerated from the current working tree. Historical connected-CI evidence is supporting evidence only and must not be presented as proof for newer code.

| Gate | Purpose |
|---|---|
| PHPUnit | Feature, policy, tenancy, request and unit regression coverage |
| Pint | PHP formatting |
| TypeScript | Static frontend checks |
| Inventory check | Documentation inventory drift |
| Tenant bypass check | Review of explicit tenant-scope bypasses |
| Vite + bundle budget | Production asset compilation and size limits |
| Composer/npm audit | Dependency vulnerabilities and abandoned packages |
| Playwright/axe | Browser journeys and accessibility smoke checks |
| PCOV ratchet | Connected-CI statement coverage must remain at or above 74.5% |

## Mandatory Access Tests

For every protected route test:

1. allowed role;
2. denied role using the URL directly;
3. same-role cross-tenant record;
4. guest;
5. mutation validation and policy;
6. index/list payload isolation.

Do not count a test as tenant isolation if it only compares two IDs. Assert response status and returned Inertia or response data.

## Commands

```bash
php artisan test
vendor/bin/pint --test
npm run typecheck
npm run check:inventory
npm run check:tenant-bypasses
npm run build
npm run build:budget
npm run test:e2e
composer audit --locked --no-interaction --abandoned=fail
npm audit --audit-level=high
php scripts/check-coverage-threshold.php coverage.xml 74.5
```

Use a mapped drive or `cmd.exe /d /c 'pushd "\\server\share\saf" && ...'` for Node commands on Windows UNC workspaces.

CI #106 established the baseline at 74.99% statement coverage (4,551/6,069). Raise the 74.5% ratchet only after a newer connected-CI artifact proves a sustainable higher floor. Final pass counts and dated evidence belong in `CURRENT_STATE.md` and the dated audit report, not in this architecture document.
