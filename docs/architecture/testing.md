# Testing Architecture

## Quality Gates

Current focused regression evidence (21 Ogos 2026): result/scorer workflow `20/20` tests and `57` assertions; public portal workflow `19/19` tests and `355` assertions. These checks cover the current individual scoring-event and public match-display changes. The connected CI result below remains historical evidence for commit `4b04c46`.

The release baseline must be generated from the exact candidate commit. Historical connected-CI evidence is supporting evidence only and must not be presented as proof for newer code.

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

CI #112 measured 75.03% statement coverage (4,676/6,232) on `4b04c46` and passed the 74.5% ratchet. Raise that floor only after a newer connected-CI artifact proves a sustainable higher threshold. Final pass counts and dated evidence belong in `CURRENT_STATE.md` and the dated audit report, not in this architecture document.
