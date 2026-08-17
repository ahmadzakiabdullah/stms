# Security Architecture

## Layers

1. Laravel session authentication and login throttling.
2. Conditional verified-email middleware.
3. Request-scoped TenantContext + organization global scopes.
4. Policies/Gates and tenant-aware validation.
5. Rate limits on sensitive mutations/exports.
6. Upload validation/SVG sanitization.
7. Security response headers.
8. Activity/application logs, backups and health monitoring.

## Confirmed Controls

- Public registration is disabled on production (404).
- `.env` is not tracked.
- Tenant-bypass allowlist, dependency audits and TypeScript/build checks pass.
- Production sends HSTS, nosniff, SAMEORIGIN, referrer and permissions policies.
- Public portal uses explicit organization/session selection.
- Sensitive index routes enforce policies/gates and have a six-role direct-URL matrix.
- Guest favicon lookup is tenant-explicit; public fonts are self-hosted.
- Locked Composer/npm audits are clean after the current Guzzle/PSR-7 update.

## Current Release Risks

- Runtime workspace has email verification off, CSP report-only, database cache/queue, file session and configuration enforcement off.
- DB principal least privilege is not evidenced.
- Security-reporting contact and real external alert delivery are not configured/evidenced.

## CSP

Production currently sends `Content-Security-Policy-Report-Only`. Repository assets are now self-hosted and browser/axe smoke tests pass. Enable enforcing mode only through the release runbook with post-deploy CSP console/network verification.

## Release Rule

Navigation visibility never replaces backend authorization. A release requires direct-URL role tests, cross-tenant list tests, green PHPUnit/Pint and secure runtime validation.
