# Final Launch Checklist

Use this checklist on the real production host after deploying the release build.

## Automated Gates

```bash
vendor/bin/pint --test
php artisan test
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan factory:preflight --production --runtime
php artisan optimize:clear
```

Expected results:

- Tests pass with zero failures.
- Route, config, view, and event caches warm successfully.
- `factory:preflight --production --runtime` reports zero failures.
- No project-managed file exceeds 400 lines.

## Infrastructure

- PHP-FPM, Nginx, Supervisor, MySQL, and Redis are running on the target host.
- Nginx site config is installed from `nginx/factory.conf` and `nginx -t` passes.
- Nginx serves the Laravel `public/` directory and forwards PHP to PHP 8.3 FPM.
- MySQL 8 accepts migrations and seeders.
- Redis is used for cache, queues, and sessions.
- Queue workers process `default` and `notifications` queues.
- Scheduler lists `factory:overdue-alerts`, `factory:low-stock-check`, and `factory:backup`.
- Backup command writes a file under `storage/app/backups`.
- Storage and `bootstrap/cache` are writable by the web user.

## Security

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_URL` uses HTTPS.
- `SESSION_SECURE_COOKIE=true`.
- `SESSION_ENCRYPT=true`.
- Real SMTP is configured; production does not use `log` or `array` mailers.
- TLS certificate is valid and auto-renewal is configured.

## Browser Verification

- Login page loads over HTTPS.
- Arabic 403, 404, 500, and 503 pages render without stack traces.
- RTL layout is verified in Chrome, Firefox, Edge, and Safari.
- Responsive layout is verified at 375px, 768px, and 1280px.
- Customer portal access is isolated to the authenticated customer.
- PDF invoice, manifest, and customer statement render Arabic correctly.

## Operational Smoke Tests

- Create product and adjust stock.
- Create customer and enable portal access.
- Create order, accept it, and confirm stock deduction.
- Issue invoice and record payment.
- Dispatch shipment and mark order delivered.
- Confirm notification bell count changes after a database notification.
