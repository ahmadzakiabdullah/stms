# Security Documentation

> Last updated: 6 August 2026

## Overview

STMS implements multiple layers of security to protect tenant data and prevent unauthorized access. This document describes the security measures in place and configurations required for production deployments.

---

## Security Headers

All HTTP responses include the following security headers via the `SecurityHeaders` middleware:

| Header | Value | Purpose |
|--------|-------|---------|
| `X-Content-Type-Options` | `nosniff` | Prevents MIME type sniffing |
| `X-Frame-Options` | `SAMEORIGIN` | Prevents clickjacking |
| `X-XSS-Protection` | `1; mode=block` | Enables browser XSS filter |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Controls referrer information |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` | Restricts browser features |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains; preload` | Forces HTTPS (production only) |
| `Content-Security-Policy` | See below | Controls resource loading (production only) |

### Content Security Policy (Production)

```
default-src 'self';
script-src 'self' 'unsafe-inline' 'unsafe-eval';
style-src 'self' 'unsafe-inline';
img-src 'self' data: blob:;
font-src 'self' data:;
connect-src 'self';
frame-ancestors 'self';
form-action 'self';
base-uri 'self';
```

---

## Email Verification

All authenticated routes (except profile management) require a verified email address. The `verified` middleware is applied to:

- Dashboard and all admin pages
- Competition Setup (sessions, sports, categories, tournaments, events)
- Registration management
- Competition operations (matches, results, rankings)
- Reports and exports
- Administration (organizations, users, roles)
- Settings and activity logs

**Exception:** Profile management routes allow unverified access so users can update their profile.

---

## Rate Limiting

The following operations are rate-limited to prevent abuse:

| Route Group | Limit | Window |
|-------------|-------|--------|
| Match mutations (POST/PUT/DELETE/generate) | 30 requests | per minute |
| Result mutations (POST/PUT/DELETE) | 30 requests | per minute |
| Ranking strategy updates | 10 requests | per minute |
| Draw operations | 10 requests | per minute |
| Event participant mutations | 30 requests | per minute |
| Dean verification (approve/reject) | 30 requests | per minute |
| Exports (PDF/Excel) | 10 requests | per minute |

Read operations (GET) are not rate-limited to ensure normal usage is unaffected.

---

## Multi-Tenancy Isolation

### TenantContext Service

The `TenantContext` service provides consistent tenant resolution across HTTP requests, jobs, and commands:

```php
use App\Services\TenantContext;

// Get current tenant organization ID
$orgId = TenantContext::getOrganizationId();

// Check if current user is super-admin
$isSuper = TenantContext::isSuperAdmin();

// Manually set tenant (for jobs/commands)
TenantContext::setOrganizationId($orgId);

// Set context type
TenantContext::setContext('queue'); // 'http', 'console', or 'queue'
```

### Global Scope

All tenant-aware models use the `BelongsToOrganization` trait which automatically filters queries by `organization_id`. Super-admins bypass this scope and can see all organizations' data.

### Trusted Proxies

Proxy trust is configured via the `TRUSTED_PROXIES` environment variable. Never use `*` in production — always specify explicit IPs/CIDRs.

```env
TRUSTED_PROXIES=10.0.0.0/8,172.16.0.0/12
```

---

## Authentication

- Login via username or email
- Password hashing via bcrypt
- Session regeneration on login
- Login throttling (Laravel default)

### Production Recommendations

1. **Enable MFA** — Not yet implemented; consider adding two-factor authentication
2. **Password policy** — Enforce minimum complexity requirements
3. **Session timeout** — Configure appropriate session lifetime

---

## Authorization

### Roles

| Role | Description |
|------|-------------|
| `super-admin` | Full system access across all organizations |
| `org-admin` | Organization-level administration |
| `admin-sport` | Manages matches/results for assigned sports |
| `staff` | General staff access |
| `faculty-representative` | Manages faculty registrations |
| `dean` | Verifies faculty registrations |

### Policies

19 policies enforce authorization at the model level. All controller actions check policies via `Gate::authorize()`.

---

## Database Security

### Encryption

- App key: `APP_KEY` (AES-256-CBC)
- Backup encryption: `BACKUP_ENCRYPTION_KEY` (AES-256)

### Indexes

Security-related indexes are in place for:
- User lookups (username, organization)
- Activity log queries
- Event participant filtering
- Match and result lookups

---

## Security Testing

Run security tests:

```bash
php artisan test --filter=SecurityHeadersTest
php artisan test --filter=VerifiedMiddlewareTest
php artisan test --filter=RateLimitingTest
php artisan test --filter=TenantIsolationTest
```

---

## Security Checklist for Production

- [ ] `APP_KEY` set to random 32-character string
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] `TRUSTED_PROXIES` set to explicit IPs/CIDRs
- [ ] `PUBLIC_REGISTRATION_ENABLED=false`
- [ ] `SEED_DEMO_DATA=false`
- [ ] `ALLOW_DEMO_SEEDING=false`
- [ ] `BACKUP_ENABLED=true` with `BACKUP_ENCRYPTION_KEY` set
- [ ] HTTPS enforced
- [ ] Database credentials are strong and unique
- [ ] Redis password set
- [ ] Regular dependency audits (`composer audit`, `npm audit`)

---

## Reporting Security Issues

If you discover a security vulnerability, please report it responsibly. Do not create public GitHub issues for security problems.
