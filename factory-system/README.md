# Factory Distribution & Shipping Management System

Enterprise Laravel 11 system for Arabic RTL factory operations: inventory, customers, orders, distribution, invoices, payments, expenses, reports, administration, PDFs, notifications, and deployment readiness.

نظام احترافي لإدارة المعامل والتوزيع والشحن باللغة العربية واتجاه RTL، مبني على Laravel 11 مع فصل واضح بين الخدمات والمستودعات والسياسات والواجهات.

## Current Status

- Phase 00 through Phase 12 repository-side launch verification completed.
- Test suite: 171 tests, 466 assertions.
- Application routes: 99.
- Scheduled commands: `factory:overdue-alerts`, `factory:low-stock-check`, `factory:backup`.
- PHP target: 8.3 on the production host.

## Core Modules

- Inventory: products, categories, stock movement, stock adjustment, low-stock alerts.
- Customers: CRM, portal access, credit limits, balances, account statements.
- Orders: state-machine lifecycle, stock validation, credit validation, customer portal ordering.
- Distribution: trucks, drivers, shipments, dispatch, delivery, manifests.
- Invoicing: invoice issuing, voiding, payments, private PDF generation.
- ERP: dashboard KPIs, expenses, sales, receivables, stock, profit/loss reports.
- Admin: users, roles, settings, audit log, temporary password notifications.
- Notifications: queued database/email notifications and Livewire notification bell.

## Architecture Standards

- Controllers stay thin and authorize every action.
- Business logic lives in services; services use repositories for data access.
- Money is stored as integer/BIGINT and handled through value objects.
- Critical writes run inside transactions.
- Arabic application strings live in `lang/ar`.
- Project-managed files are kept under 400 lines.

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Testing uses SQLite in-memory through `phpunit.xml`:

```bash
php artisan test
vendor/bin/pint --test
```

## Production Deployment

Deployment assets are included:

- `nginx/factory.conf`
- `supervisor/factory.conf`
- `deploy.sh`
- `.env.production.example`
- `DEPLOYMENT.md`
- `LAUNCH_CHECKLIST.md`

Use `DEPLOYMENT.md` as the operational runbook. The VPS path expected by Supervisor and the deploy script is `/var/www/factory-system` unless overridden by `APP_DIR`.

## Production Checklist

```bash
php artisan factory:preflight --production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan schedule:list
php artisan test
```

Required production environment values:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` set to a generated key
- `APP_URL` set to the HTTPS domain
- `DB_CONNECTION=mysql`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `SESSION_DRIVER=redis`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`

## Arabic Summary

للتشغيل المحلي: ثبّت Composer و npm، انسخ ملف البيئة، أنشئ مفتاح التطبيق، شغّل الهجرات والبذور، ثم ابنِ الواجهات.

للإنتاج: استخدم `.env.production.example`، فعّل `APP_DEBUG=false`، استخدم MySQL و Redis، شغّل العامل والجدولة عبر Supervisor، ونفّذ أوامر الكاش قبل الإطلاق.

## License

Proprietary software. All rights reserved.
