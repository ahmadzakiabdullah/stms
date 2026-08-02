# Deployment Runbook

> STMS — Production deployment steps.
> Server: SAF Portal (IIS/Windows or Linux — adjust paths accordingly)

---

## Prerequisites

- [ ] PHP 8.4+ with extensions: `pdo_mysql`, `mbstring`, `xml`, `curl`, `gd`, `zip`, `bcmath`, `redis`
- [ ] MySQL 8.0+
- [ ] Redis 7+ (optional but recommended)
- [ ] Composer 2.x
- [ ] Node.js 20+ / npm 10+
- [ ] Web server (Nginx / IIS with URL Rewrite)

---

## Step 1: Initial Commit (from dev machine)

```bash
# From a local drive (NOT UNC path), clone/copy project
git init
git add .
git commit -m "Initial commit — STMS MVP complete (M1-M6, Fasa 0-3)"

# Push to your git server
git remote add origin <your-git-url>
git push -u origin master
```

---

## Step 2: Pull on Production Server

```bash
cd /path/to/production
git clone <your-git-url> .
# or if already cloned:
git pull origin master
```

---

## Step 3: Environment Setup

```bash
cp .env.production.example .env
```

Edit `.env`:
| Variable | Value |
|----------|-------|
| `APP_KEY` | Run `php artisan key:generate` |
| `APP_URL` | `https://your-domain.com/portal` |
| `SESSION_PATH` | `/portal` |
| `DB_HOST` | `127.0.0.1` (or your MySQL host) |
| `DB_DATABASE` | `db4safportal` |
| `DB_USERNAME` | (your DB user) |
| `DB_PASSWORD` | (your DB password) |
| `CACHE_STORE` | `redis` (or `database` if no Redis) |
| `QUEUE_CONNECTION` | `redis` (or `database` if no Redis) |
| `REDIS_HOST` | `127.0.0.1` (if using Redis) |
| `PUBLIC_REGISTRATION_ENABLED` | `false` unless public onboarding is explicitly approved |
| `DEFAULT_ORG_SLUG` | Required only when public registration is enabled |
| `TRUSTED_PROXIES` | Comma-separated IP/CIDR allowlist for the actual reverse proxy; never `*` |
| `SEED_DEMO_DATA` | `false` in production |
| `ALLOW_DEMO_SEEDING` | `false`; temporary explicit override only for an approved SAF data load |
| `BACKUP_ENABLED` | `true` after configuring and testing encrypted backup storage |
| `BACKUP_PATH` | Persistent path outside an ephemeral container layer |
| `BACKUP_ENCRYPTION_KEY` | Unique 32+ character secret stored outside the repository |
| `HEALTH_MONITOR_ENABLED` | `true` when Laravel Scheduler is running |

---

## Step 4: Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

---

## Step 5: Database

```bash
php artisan migrate --force
php artisan db:seed --force          # Production default: organization + roles/permissions only; creates no users
php artisan stms:create-super-admin admin@example.com utem  # Password is requested securely and is not stored in shell history
php artisan event-participants:backfill-org  # Backfill org_id for existing records
```

---

## Step 6: Cache & Permissions

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Storage permissions (if Linux)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

---

## Step 7: Verify

- [ ] Visit `https://your-domain.com/portal` — should show welcome page
- [ ] Visit `https://your-domain.com/portal/health` — should return JSON `{"status":"ok"}`
- [ ] Provision the initial super-admin with `stms:create-super-admin`; production seeding intentionally creates no default account
- [ ] Test basic CRUD (Organizations, Sports, Sessions, Tournaments, Events)

---

## Post-Deployment Checklist

- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Set `LOG_LEVEL=warning` (already default in `.env.production.example`)
- [ ] Configure SSL/HTTPS
- [ ] Schedule `php artisan schedule:run` in cron (if using scheduled tasks)
- [ ] Set up log rotation for `storage/logs/`
- [ ] Run `php artisan stms:backup`, copy the archive off-host, and complete the isolated restore drill in [backup-restore.md](../deployment/backup-restore.md)
- [ ] Configure an external uptime monitor for `/health` and alert on HTTP 503
- [ ] Confirm `queue:work` and `schedule:work` are supervised and restart after failure/deploy

---

## Rollback

```bash
# Before deployment
php artisan stms:backup
php artisan down

# Deploy a versioned release directory, migrate, then switch the current symlink.
# To roll back application code, switch the symlink to the previous tested release.
php artisan optimize:clear
php artisan queue:restart
php artisan up
```

Do not automatically roll back database migrations after they may have processed production data. Prefer backward-compatible expand/contract migrations. If data restoration is required, follow the authorized restore runbook and preserve the pre-restore snapshot.
