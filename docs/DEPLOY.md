# Deployment

Production target: one Ubuntu 24.04 server (or Laravel Forge) running Nginx, PHP-FPM 8.4, MySQL 8, Redis and Meilisearch, with Horizon for queues and the Laravel scheduler. The steps below assume Forge. Without Forge, they map to plain systemd units.

## 1. Server packages

- PHP 8.4 with `bcmath, ctype, curl, dom, fileinfo, gd, intl, mbstring, pcntl, pdo_mysql, posix, redis, xml, zip` (plus `exif` and `imagick` if available).
- MySQL 8 (spatial functions are used: `ST_Distance_Sphere`, `POINT` column with a spatial index).
- Redis 7 for cache, sessions, queues and locks.
- Meilisearch 1.x, bound to `127.0.0.1` only, with a master key.
- Node 22 is only needed to build the assets, or build them in CI.

## 2. Environment

Copy `.env.example` and set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.ma
SESSION_SECURE_COOKIE=true
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
TRUSTED_PROXIES=            # load balancer / Cloudflare IPs if any
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=...         # master key, never exposed to browsers
OTP_DRIVER=sms              # or whatsapp, with the provider credentials
MAIL_MAILER=smtp            # transactional provider
MAP_TILE_URL=...            # commercial tile provider (public OSM tiles are not allowed)
CNDP_NUMBER=...
PAYMENT_*, INVOICE_*        # see README "Billing"
```

Then run:

```bash
php artisan key:generate --force   # first deploy only
php artisan storage:link
```

The `local` disk (`storage/app/private`) holds claim documents, receipts, invoices and data exports. It must never be web-accessible, and it must be included in the backups.

## 3. Deploy script (Forge "Deploy Script")

```bash
cd /home/forge/example.ma
git pull origin $FORGE_SITE_BRANCH
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci && npm run build
$FORGE_PHP artisan down --retry=15 || true
$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan optimize
$FORGE_PHP artisan filament:optimize
$FORGE_PHP artisan scout:sync-index-settings
$FORGE_PHP artisan horizon:terminate
$FORGE_PHP artisan up
```

First deploy only: run `php artisan db:seed --force`. In production the seeders only create roles, the city, categories, plans, rates and pages. Then create the first admin:

```bash
php artisan app:make-admin 06XXXXXXXX --email=admin@example.ma
```

## 4. Nginx

Use the Forge default PHP site and add:

```nginx
client_max_body_size 12M;           # photos and receipts (app limits are lower)

location = /sw.js {                 # service worker: always revalidated
    add_header Cache-Control "no-cache";
    try_files $uri =404;
}

location /build/assets/ {           # fingerprinted by Vite
    add_header Cache-Control "public, max-age=31536000, immutable";
    try_files $uri =404;
}

location ~ /\.(?!well-known) { deny all; }
```

`robots.txt`, `manifest.webmanifest` and `/health` are Laravel routes, so do not create static files with those names. The security headers (CSP with nonces, HSTS, frame denial) are sent by the application.

HTTPS: use a Let's Encrypt certificate from Forge, redirect HTTP to HTTPS, and enable HTTP/2.

## 5. Processes

- **Horizon:** add a Forge daemon `php artisan horizon` (or the systemd unit below). Deploys restart it with `horizon:terminate`.
- **Scheduler:** add a Forge scheduler entry `php artisan schedule:run` every minute. Scheduled tasks:
  - stats aggregation, 00:20;
  - ranking recalculation, 02:30;
  - pruning of OTP codes and raw events, 03:10;
  - pruning of data exports, 03:30;
  - sitemaps, 04:00;
  - billing maintenance, hourly;
  - Horizon snapshot, every 5 minutes.
- **Meilisearch:** systemd service, `--env production --master-key ... --http-addr 127.0.0.1:7700`. After the first deploy, run `php artisan scout:import "App\Models\Business"`.

```ini
# /etc/systemd/system/horizon.service (without Forge)
[Service]
User=forge
WorkingDirectory=/home/forge/example.ma
ExecStart=/usr/bin/php artisan horizon
Restart=always
```

## 6. Backups (offsite, daily)

- **MySQL:** a nightly `mysqldump --single-transaction` or Forge database backups, sent to an S3-compatible bucket in another region, with 30 daily and 12 monthly copies kept.
- **Files:** sync `storage/app/private` and `storage/app/public` nightly (`rclone sync` or `restic`) to the same offsite bucket, encrypted.
- **Restore test:** restore to a staging server once per quarter. A backup that was never restored is not a backup.
- **Invoices:** they must be kept for 10 years, so the backup retention covers them, or export them yearly.

## 7. Logs

- Set `LOG_CHANNEL=daily` and `LOG_DAILY_DAYS=14`, so Laravel rotates its own files.
- Rotate Nginx and PHP-FPM logs with the default `logrotate` (Forge configures it).
- Logs never contain OTP codes in production. `LogOtpSender` is for local use only, so make sure `OTP_DRIVER` is not `log`.

## 8. Error tracking (Sentry)

Sentry is optional and not installed by default. To enable it:

```bash
composer require sentry/sentry-laravel
php artisan sentry:publish --dsn=https://...@sentry.io/...
```

Then:

- Set `SENTRY_TRACES_SAMPLE_RATE=0.1` and `SENTRY_SEND_DEFAULT_PII=false`. Phone numbers and e-mails must not leave the server.
- Add the Sentry ingest host to `connect-src` only if the browser SDK is used. It is not needed for backend reporting.

## 9. Monitoring

- **Uptime probe:** `GET /health` returns `200 {"status":"ok"}` or `503` with the failing component names (database, cache, search, queue, workers). Error details are only written to the logs.
- **Queues:** `/admin` → Horizon is restricted to admins. Set an alert on failed jobs.

## 10. Launch checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, `SESSION_SECURE_COOKIE=true`
- [ ] Legal pages reviewed by a lawyer, every `[...]` filled in, CNDP number in `.env`
- [ ] Invoice legal details (`INVOICE_*`) and bank details (`PAYMENT_*`) filled in
- [ ] Commercial map tiles configured, attribution kept
- [ ] SMS/WhatsApp OTP provider live, and `OTP_DRIVER` is not `log`
- [ ] Meilisearch bound to localhost with a master key
- [ ] First sitemap generated (`php artisan sitemap:generate`) and submitted in Google Search Console
- [ ] Backups running, and one restore tested
- [ ] `/health` monitored
- [ ] Lighthouse (mobile) run on the city home, a category page and a business page
