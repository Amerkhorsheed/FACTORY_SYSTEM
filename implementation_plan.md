# Factory System Implementation And Launch Plan

This plan is the current implementation and launch baseline for the Factory Distribution & Shipping Management System. It replaces the older Part 7 scaffolding plan, because the application is now implemented through repository-side Phase 12 and only target-infrastructure launch validation remains.

## 1. Executive Summary

- Product: Arabic RTL factory operations system built on Laravel 11.
- Scope: inventory, customers, orders, distribution, invoices, payments, expenses, reports, administration, PDFs, notifications, deployment assets, and preflight tooling.
- Repository status: Phases 00 through 12 repository-side work are complete.
- Current launch state: codebase is locally verified; final production host validation remains.
- Local test baseline: 171 tests, 466 assertions.
- Application routes: 99 application routes.
- Scheduled commands: `factory:overdue-alerts`, `factory:low-stock-check`, `factory:backup`.
- Production target: PHP 8.3 FPM, Nginx, MySQL 8, Redis, and Supervisor.

## 2. Non-Negotiable Engineering Rules

- Project-managed files stay under 400 lines.
- Money is stored as BIGINT/integer values, never float.
- DB writes are wrapped in `DB::transaction()`.
- Every controller action authorizes access.
- Controllers remain thin and delegate business/query logic.
- Business logic belongs in services.
- Services use repositories for data access.
- Arabic strings belong in `lang/ar/`.
- Lists are paginated; controllers must not perform unbounded `get()` calls.
- Production deployment is VPS/PHP-FPM/Nginx/Supervisor oriented.

## 3. Delivered Scope

| Phase | Status | Delivered Outcome |
| --- | --- | --- |
| 00 Bootstrap | Complete | Laravel 11 app, packages, config, key/storage verification. |
| 01 Database | Complete | 17 migrations covering users, products, customers, distribution, orders, invoices, payments, expenses, settings, permissions, activity log, and notifications. |
| 02 Domain Primitives | Complete | `Money` value object, order state machine, shipment state machine, invalid transition exception. |
| 03 Base Architecture | Complete | Base repository/service primitives, contracts, export strategy interface, DI bindings. |
| 04 Models | Complete | Domain models, money formatting, code generation, soft-delete guards, observers, activity translations. |
| 05 Seeders/RBAC | Complete | Roles, permissions, system settings, default users, product categories. |
| 06 Auth | Complete | Login/logout, active user checks, locale middleware, portal middleware, last activity middleware. |
| 07.01 Inventory | Complete | Products, categories, stock adjustments, stock movements, low-stock alerts. |
| 07.02 Customers | Complete | Customer CRUD, portal access, credit calculations, statements. |
| 07.03 Orders | Complete | Creation pipeline, stock validation, credit validation, lifecycle transitions, financials. |
| 07.04 Distribution | Complete | Trucks, drivers, shipments, order assignment, dispatch/cancel/deliver flows, manifests. |
| 07.05 Invoicing | Complete | Invoice issue/void/download, payment recording/deletion, recalculation logic. |
| 07.06 Payments/ERP | Complete | Payments, expenses, dashboard KPIs, sales/receivables/stock/profit-loss reports. |
| 07.07 Admin | Complete | User management, settings management, audit log, policies, temporary password notifications. |
| 08 Frontend | Complete | Shared RTL layout, module views, public auth/welcome polish, customer portal. |
| 09 PDFs | Complete | DomPDF service, private PDF storage, Arabic invoice/manifest/statement templates. |
| 10 Notifications | Complete | Queued database/mail notifications, staff digests, Livewire notification bell, schedules. |
| 11 Deployment | Complete | Production env template, deploy script, Supervisor config, runbook, launch checklist, Arabic error pages. |
| 12 Launch Tooling | Complete | `factory:preflight` command, runtime checks, deployment tests, final local verification gates. |

## 4. Architecture Baseline

- HTTP controllers authorize, validate through form requests, and delegate work to services.
- Services orchestrate business rules, transactions, status transitions, notifications, and generated documents.
- Repositories own query composition, filtered lists, eager loading, and report datasets.
- Policies protect domain actions for products, customers, orders, shipments, invoices, payments, expenses, users, settings, and activity logs.
- Value objects and casts keep money handling integer-safe across application boundaries.
- Events/listeners are explicitly registered; automatic event discovery is disabled.
- PDFs are generated through a dedicated service and stored under private storage paths.
- Notifications use queued database/mail delivery where customer or staff communication is required.
- Frontend uses shared Arabic RTL layouts and reusable Blade components with responsive module pages.

## 5. Module Map

| Module | Primary Responsibilities | Key Quality Points |
| --- | --- | --- |
| Inventory | Product catalog, categories, stock movements, low-stock detection. | Stock writes are service-owned and guarded by policy/request validation. |
| Customers | Customer records, balances, credit limits, statements, portal access. | Portal data is scoped server-side to the authenticated customer. |
| Orders | Order creation, stock/credit validation, status lifecycle. | Pipeline validation prevents invalid financial or stock states. |
| Distribution | Shipment planning, trucks, drivers, order assignment, dispatch/delivery. | State machine blocks invalid shipment transitions. |
| Invoicing | Invoice issuance, voiding, payment application, PDFs. | Payment and invoice totals are recalculated in transactional services. |
| ERP | Dashboard KPIs, expense management, operational reports. | Report datasets are produced outside controllers. |
| Admin | Users, settings, audit trail, role-protected administration. | Self-delete and authorization edge cases are tested. |
| Portal | Customer dashboard, order/invoice visibility, profile update. | Staff access is blocked from portal-only paths. |
| Notifications | Customer events, staff digests, Livewire bell. | `default` and `notifications` queues are expected in production. |

## 6. Deployment Model

- Runtime: PHP 8.3 FPM with required extensions including `bcmath`, `gd`, `intl`, `pdo_mysql`, `redis`, and `pcntl`.
- Web server: Nginx serving the Laravel `public/` directory.
- Database: MySQL 8 with `utf8mb4_unicode_ci`.
- Cache, session, and queue: Redis.
- Process control: Supervisor for queue workers and the Laravel scheduler.
- Default app directory: `/var/www/factory-system`, overridable with `APP_DIR`.
- Queue names: `default` and `notifications`.
- Included deployment assets: `.env.production.example`, `deploy.sh`, `nginx/factory.conf`, `supervisor/factory.conf`, `DEPLOYMENT.md`, and `LAUNCH_CHECKLIST.md`.

## 7. Production Environment Requirements

The production `.env` must be based on `.env.production.example` and must satisfy these settings before launch:

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
MAIL_MAILER=smtp
APP_MAINTENANCE_DRIVER=cache
APP_MAINTENANCE_STORE=redis
```

The production host must also have a valid `APP_KEY`, real database credentials, real SMTP credentials, writable `storage`, writable `bootstrap/cache`, and a built Vite manifest under `public/build/manifest.json`.

## 8. Deployment Runbook

1. Provision the VPS with PHP 8.3 FPM, Nginx, MySQL 8, Redis, Supervisor, Composer, Node, and npm.
2. Clone the release branch into `/var/www/factory-system` or set `APP_DIR` to the real path.
3. Copy `.env.production.example` to `.env` and set production secrets.
4. Install `nginx/factory.conf`, then configure Nginx to serve `public/` and enforce HTTPS.
5. Install Supervisor config from `supervisor/factory.conf` and confirm `factory:*` processes are running.
6. Run the deployment script with `APP_DIR=/var/www/factory-system ./deploy.sh main`.
7. For full live validation, deploy with `PREFLIGHT_RUNTIME=true` or run `php artisan factory:preflight --production --runtime` after deployment.
8. Keep maintenance mode disabled only after migrations, cache warm-up, preflight checks, worker restart, and permission fixes pass.

## 9. Automated Verification Gates

Run these before marking a release ready:

```bash
vendor/bin/pint --test
php artisan test
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan factory:preflight --production
php artisan schedule:list
```

Run this on the real host after production services are configured:

```bash
php artisan factory:preflight --production --runtime
```

The runtime preflight must return zero failures before launch sign-off.

## 10. Manual Launch Validation

- Login page returns 200 over HTTPS.
- TLS certificate is valid and HTTP redirects to HTTPS.
- Arabic 403, 404, 500, and 503 pages render without stack traces.
- RTL layout works in Chrome, Firefox, Edge, and Safari.
- Responsive layout works at 375px, 768px, and 1280px.
- Customer portal records are scoped to the authenticated customer.
- Product creation and stock adjustment work.
- Customer creation and portal enablement work.
- Order creation, acceptance, and stock deduction work.
- Invoice issuance and payment recording work.
- Shipment dispatch and delivery transitions work.
- Invoice, manifest, and customer statement PDFs render Arabic correctly.
- Notification bell count changes after a database notification.
- Email delivery works through real SMTP.
- `factory:backup` writes a backup under `storage/app/backups`.
- Queue workers process `default` and `notifications` jobs.
- Scheduler runs `factory:overdue-alerts`, `factory:low-stock-check`, and `factory:backup`.

## 11. Acceptance Criteria

- All automated gates pass on the release build.
- `php artisan factory:preflight --production --runtime` reports zero failures on the production host.
- Production `.env` uses secure, non-local settings.
- PHP runtime is 8.3 or newer on the production host.
- MySQL, Redis, mail, queues, scheduler, and backups are verified live.
- Route/config/view/event caches warm successfully.
- No closure route prevents route caching.
- No project-managed file exceeds 400 lines.
- No debug output or stack trace is exposed to end users.
- Critical business flows pass manual smoke testing with realistic data.

## 12. Known Constraints And Risks

| Risk | Current State | Mitigation |
| --- | --- | --- |
| Local PHP version | Local PHP is 8.2.12 while production target is 8.3. | Validate PHP 8.3 on the production host through preflight. |
| Local database | Local MySQL credentials are unavailable; tests use SQLite in-memory. | Run migrations, seeders, and runtime preflight against production MySQL. |
| Local Redis | Redis is unavailable locally; local env uses file/sync fallbacks. | Verify Redis cache/session/queue connectivity with runtime preflight. |
| Production services | HTTPS, workers, scheduler, backups, browser checks depend on the real host. | Complete `LAUNCH_CHECKLIST.md` after deployment. |
| Mail delivery | Local verification cannot prove real SMTP delivery. | Configure SMTP and send test notifications on production. |
| PDF rendering | Local tests cover PDF endpoints, but fonts/rendering must be visually checked. | Open invoice, manifest, and statement PDFs during launch smoke tests. |

## 13. Immediate Next Steps

1. Prepare the production host and required services.
2. Configure `.env` from `.env.production.example` with real credentials and HTTPS `APP_URL`.
3. Run `APP_DIR=/var/www/factory-system ./deploy.sh main`.
4. Run `php artisan factory:preflight --production --runtime` on the production host.
5. Complete every item in `LAUNCH_CHECKLIST.md`.
6. Record final launch results in `PROGRESS.md` and close the remaining Phase 12 items in `TODO.md`.

## 14. Final Position

The repository is ready for target-infrastructure launch validation. No additional application modules are currently planned before launch; the remaining work is operational verification on the real production host.
