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

- Per last recorded runtime evidence, the workspace has email verification off, database cache/queue and file session, and configuration enforcement remains off. CSP status is described once below.
- DB principal least privilege is not evidenced.
- Security-reporting contact and real external alert delivery are not configured/evidenced.

## CSP

The code default (`config/app.php`) is report-only (`CSP_REPORT_ONLY=true`, header `Content-Security-Policy-Report-Only`); `.env.production.example` sets `CSP_REPORT_ONLY=false` (enforcing `Content-Security-Policy`). Per the last recorded evidence (`TODOS.md`, 20 September 2026), production was switched to enforcing; re-verify the header after each deploy. The inline Ziggy route bootstrap and HTML style elements receive a per-response nonce, and the policy no longer allows Bunny Fonts because assets are self-hosted. Inertia NProgress CSS is bundled in `app.css` with runtime CSS injection disabled. Dynamic React `style` attributes are isolated under `style-src-attr 'unsafe-inline'`. Any future policy changes must go through the release runbook with post-deploy CSP console/network verification.

## Release Rule

Navigation visibility never replaces backend authorization. A release requires direct-URL role tests, cross-tenant list tests, green PHPUnit/Pint and secure runtime validation.
