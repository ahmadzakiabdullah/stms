# Release Runbook

> STMS — Versioned release procedure for production deployment.

---

## Pre-Release Checklist

- [ ] Working tree clean (`git status` shows no changes)
- [ ] All CI gates pass (Pint, PHPUnit, npm build, typecheck, dependency audits)
- [ ] `php artisan route:list --except-vendor` — no unexpected routes
- [ ] `php artisan test` — all tests pass
- [ ] `php vendor/bin/pint --test` — no lint violations
- [ ] `npm run build` — production build succeeds
- [ ] `npm run typecheck` — no TypeScript errors
- [ ] `composer audit --locked --abandoned=fail` — no advisories
- [ ] `npm audit --audit-level=high` — no vulnerabilities
- [ ] `CHANGELOG.md` updated with release entry
- [ ] `CURRENT_STATE.md` updated with release SHA and evidence

---

## Versioning

STMS follows [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes to API, data model, or tenant isolation
- **MINOR**: New features, new modules, non-breaking schema changes
- **PATCH**: Bug fixes, hardening, documentation

Current baseline: `v0.1.0` (MVP complete, hardening in progress).

---

## Release Procedure

### 1. Prepare the Release Branch

```bash
git checkout master
git pull origin master
git checkout -b release/vX.Y.Z
```

### 2. Update Version References

Update `CHANGELOG.md`:
```markdown
## [X.Y.Z] — YYYY-MM-DD

### Added
- ...

### Fixed
- ...

### Security
- ...
```

### 3. Commit and Tag

```bash
git add -A
git commit -m "release: vX.Y.Z"
git tag -a vX.Y.Z -m "Release vX.Y.Z — <summary>"
git push origin release/vX.Y.Z
git push origin vX.Y.Z
```

### 4. Record Evidence

In `CURRENT_STATE.md`:
```
**Release vX.Y.Z** — SHA: `<commit-sha>`
- CI: Pint ✅, PHPUnit ✅, npm build ✅, typecheck ✅
- Tests: N tests / M assertions
- Security: dependency audits clean
```

---

## Deployment Procedure

### Pre-Deploy

```bash
# On production server
php artisan down --secret="MAINTENANCE_SECRET"
php artisan stms:backup
```

### Deploy

```bash
git fetch origin
git checkout vX.Y.Z
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

### Post-Deploy Verification

- [ ] `GET /health` returns `{"status":"ok"}`
- [ ] Public portal loads at `/portal/`
- [ ] Login works (username or email)
- [ ] Dashboard loads for each role (super-admin, faculty-rep, dean)
- [ ] Tenant isolation verified (Org A cannot see Org B data)
- [ ] Export functions work (PDF, Excel)
- [ ] Notifications deliver

---

## Rollback Procedure

### Code Rollback

```bash
php artisan down --secret="MAINTENANCE_SECRET"
git checkout vX.Y.Z-previous
composer install --no-dev --optimize-autoloader --no-interaction
php artisan optimize:clear
php artisan queue:restart
php artisan up
```

### Database Rollback

> **WARNING**: Only if migrations are backward-compatible. Do not rollback after production data has been processed.

```bash
php artisan stms:backup  # Take a fresh backup first
php artisan migrate:rollback --step=N
```

If data restoration is required, use the authorized restore procedure in `backup-restore.md`.

---

## Expand/Contract Migration Pattern

For zero-downtime deployments, use the expand/contract pattern:

1. **Expand** (deploy first): Add new columns/tables without removing old ones
2. **Dual-write**: Application writes to both old and new structures
3. **Backfill**: Migrate existing data to new structure
4. **Contract** (deploy later): Remove old columns/tables after verification

Never remove a column in the same release that adds it.

---

## Environment Configuration

Required production `.env` values:

| Variable | Value | Notes |
|----------|-------|-------|
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | Never `true` in production |
| `APP_URL` | `https://your-domain.com/portal` | |
| `SESSION_PATH` | `/portal` | |
| `SESSION_DRIVER` | `redis` | Multi-instance safe |
| `CACHE_STORE` | `redis` | |
| `QUEUE_CONNECTION` | `redis` | |
| `REDIS_HOST` | `127.0.0.1` | |
| `PUBLIC_REGISTRATION_ENABLED` | `false` | |
| `SEED_DEMO_DATA` | `false` | |
| `ALLOW_DEMO_SEEDING` | `false` | |
| `TRUSTED_PROXIES` | Comma-separated IP/CIDR | Never `*` |
| `CSP_REPORT_ONLY` | `false` | Set `true` initially if unsure |
| `BACKUP_ENABLED` | `true` | |
| `BACKUP_PATH` | Persistent path | Outside ephemeral container |
| `BACKUP_ENCRYPTION_KEY` | 32+ char secret | Outside repository |
| `MAIL_MAILER` | `smtp` | Or `ses`, `postmark` |
| `MAIL_FROM_ADDRESS` | `noreply@your-domain.com` | |
| `LOG_LEVEL` | `warning` | |
| `LOG_CHANNEL` | `daily` | Or `syslog`, `errorlog` |

---

## Post-Release Monitoring

Monitor for 24-48 hours after release:

- [ ] Error logs (`storage/logs/laravel.log`)
- [ ] Queue worker health (`php artisan queue:monitor`)
- [ ] Failed job count (`php artisan queue:failed`)
- [ ] Health endpoint (`/health`)
- [ ] Backup job success
- [ ] External uptime monitor alerts

---

## Emergency Contacts

| Role | Contact | Responsibility |
|------|---------|----------------|
| On-call | (configure) | First responder |
| DBA | (configure) | Database issues |
| Infrastructure | (configure) | Server/network |

---

**Last Updated:** 10 August 2026
