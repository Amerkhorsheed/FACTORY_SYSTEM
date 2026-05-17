# Deployment Runbook

This runbook covers production deployment for the Factory Distribution & Shipping Management System.

## Targets

- Runtime: PHP 8.3 FPM.
- Web server: Nginx.
- Database: MySQL 8.0 with `utf8mb4_unicode_ci`.
- Cache, session, queue: Redis.
- Queue names: `default`, `notifications`.
- Scheduler: Laravel scheduler running continuously.

## Environment

Start from the production template:

```bash
cp .env.production.example .env
php artisan key:generate --show
```

Set the generated value in `APP_KEY`, then configure domain, database credentials, mail credentials, and secure cookie settings.

Required production values:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
APP_LOCALE=ar
APP_FALLBACK_LOCALE=ar
DB_CONNECTION=mysql
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
```

## VPS Deployment

The included `deploy.sh` expects `/var/www/factory-system` by default. Override with `APP_DIR` when needed.

```bash
chmod +x deploy.sh
APP_DIR=/var/www/factory-system ./deploy.sh main
```

The script performs these operations:

- Preflight checks for `.env`, PHP, Composer, and npm.
- Maintenance mode with Arabic 503 page.
- Fast-forward code update.
- Composer install without dev dependencies.
- Frontend build.
- `factory:backup` before migrations unless `RUN_BACKUP=false`.
- Cache clearing, migrations, storage link.
- Config, route, view, and event cache warm-up.
- Production preflight checks through `factory:preflight --production`.
- Queue restart and optional Supervisor restart.
- Permission fix and maintenance mode exit.

## Supervisor

Copy the process config:

```bash
sudo cp supervisor/factory.conf /etc/supervisor/conf.d/factory.conf
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status factory:*
```

The config runs two queue workers and one scheduler process. Horizon is intentionally not referenced because it is not part of the installed application dependencies.

## Nginx And TLS

Start from the included site template, then replace `factory.example.com` and certificate paths with the real production domain:

```bash
sudo cp nginx/factory.conf /etc/nginx/sites-available/factory-system.conf
sudo ln -s /etc/nginx/sites-available/factory-system.conf /etc/nginx/sites-enabled/factory-system.conf
sudo nginx -t
sudo systemctl reload nginx
```

The template serves `public/`, redirects HTTP to HTTPS, forwards PHP requests to PHP 8.3 FPM, and blocks hidden files except ACME challenge files.

Terminate TLS with Certbot or a managed load balancer.

Minimum TLS checklist:

- Issue a valid certificate for `APP_URL`.
- Redirect HTTP to HTTPS at the edge.
- Confirm the PHP-FPM socket path matches the host.
- Keep `SESSION_SECURE_COOKIE=true`.
- Verify the login page loads over HTTPS.

## Verification Gate

Run before marking a deployment successful:

```bash
vendor/bin/pint --test
php artisan test
npm run build
php artisan factory:preflight --production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan schedule:list
php artisan optimize:clear
```

Expected application checks:

- Login page returns 200 over HTTPS.
- `APP_DEBUG=false` in production.
- Arabic 403, 404, 500, and 503 pages render without stack traces.
- Queue worker processes notifications.
- Scheduler lists `factory:overdue-alerts`, `factory:low-stock-check`, and `factory:backup`.
- `factory:backup` writes a backup file under `storage/app/backups`.

For final launch on the real host, run live connectivity checks:

```bash
php artisan factory:preflight --production --runtime
```

## Arabic Operations Summary

قبل الإطلاق تأكد من تعطيل وضع التصحيح، ضبط رابط HTTPS، استخدام MySQL و Redis، تشغيل العمال والجدولة، تنفيذ الهجرات والبذور، وبناء الواجهات. بعد الإطلاق راقب السجلات، الطوابير الفاشلة، النسخ الاحتياطي، وصفحات الأخطاء العربية.
