# Factory Distribution & Shipping Management System

Enterprise Laravel 11 platform for Arabic RTL factory operations: inventory, customers, orders, distribution, invoices, payments, expenses, reports, administration, PDFs, notifications, and production launch readiness.

نظام احترافي لإدارة المعامل والتوزيع والشحن باللغة العربية واتجاه RTL، مبني على Laravel 11 مع فصل واضح بين الخدمات والمستودعات والسياسات والواجهات.

## Current Status

| Metric | Current Value |
|---|---|
| Repository status | Implementation complete through repository-side Phase 12 launch tooling |
| Remaining work | Target-host production validation |
| Test suite | 180 passing tests, 481 assertions |
| Application routes | 99 non-vendor routes |
| Scheduled commands | `factory:overdue-alerts`, `factory:low-stock-check`, `factory:backup` |
| Local style gate | `vendor/bin/pint --test` passes |
| Frontend build | `npm run build` passes |
| Production target | PHP 8.3 FPM, Nginx, MySQL 8, Redis, Supervisor |

## Core Scope

| Module | Delivered Capabilities |
|---|---|
| Inventory | Product CRUD, categories, stock movements, stock adjustment, low-stock alerts. |
| Customers | CRM, portal access, credit limits, balances, account statements. |
| Orders | State-machine lifecycle, update validation, stock/credit validation, customer portal ordering. |
| Distribution | Trucks, drivers, shipments, dispatch, guarded delivery, manifests. |
| Invoicing | Invoice issuing, voiding, invoice-scoped payments, private PDF generation. |
| ERP | Dashboard KPIs, expenses, sales, receivables, stock, profit/loss reports. |
| Admin | Users, roles, settings, audit log, temporary password notifications. |
| Notifications | Queued database/mail notifications, staff digests, Livewire notification bell. |
| Deployment | Production env template, Nginx config, Supervisor config, deploy script, backup command, preflight command. |

## Architecture

```text
HTTP Request
  -> Middleware
  -> FormRequest validation
  -> Controller authorization and DTO construction
  -> Service orchestration, transactions, events, PDFs, notifications
  -> Repository query/data access
  -> Model relationships, casts, observers, policies
```

## Engineering Rules

| Rule | Standard |
|---|---|
| Money | Integer/BIGINT storage only; no float money arithmetic. |
| Controllers | Thin, authorized, and delegated to services. |
| Services | Own business logic and transactional writes. |
| Repositories | Own Eloquent query composition and pagination. |
| Statuses | Changed through state-machine-aware services. |
| Localization | Arabic strings live in `lang/ar`. |
| File size | Project-managed files stay under 400 lines. |

## Project Structure

```text
FACTORY_SYSTEM/
  AGENT.md
  TASKS.md
  PROGRESS.md
  TODO.md
  DECISIONS.md
  SKILLS.md
  implementation_plan.md
  DOCS/
    AGENT_PROMPT_FACTORY_SYSTEM*.md
  factory-system/
    app/
    config/
    database/
    lang/ar/
    resources/
    routes/
    tests/
    DEPLOYMENT.md
    LAUNCH_CHECKLIST.md
```

## Local Setup

Run from `factory-system/`:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

## Verification

Run from `factory-system/` before release sign-off:

```bash
vendor/bin/pint --test
php artisan test
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan schedule:list
php artisan factory:preflight --production
php artisan optimize:clear
```

Run on the production host after services are configured:

```bash
php artisan factory:preflight --production --runtime
```

## Production Launch

Use these documents as the launch source of truth:

| Document | Purpose |
|---|---|
| `TASKS.md` | Root master requirements, execution, and launch index. |
| `implementation_plan.md` | Current implementation and production launch baseline. |
| `factory-system/DEPLOYMENT.md` | Operational deployment runbook. |
| `factory-system/LAUNCH_CHECKLIST.md` | Target-host launch checklist and acceptance evidence. |
| `factory-system/PROGRESS.md` | Live implementation progress and latest verification. |

Required production services: PHP 8.3 FPM, Nginx, MySQL 8, Redis, Supervisor, real SMTP, HTTPS/TLS, queue workers, scheduler, and backup validation.

## Current Constraints

| Constraint | Resolution |
|---|---|
| Local PHP is 8.2.12 | Production host must provide PHP 8.3+. |
| Local Redis is unavailable | Production must use Redis for cache, queue, session, and maintenance mode. |
| Local tests use SQLite | Production launch must validate MySQL migrations and seeders. |
| SMTP cannot be proven locally | Send test notifications through the real SMTP provider. |
| Browser/PDF smoke tests require host | Complete `factory-system/LAUNCH_CHECKLIST.md`. |

## License

Proprietary software. All rights reserved.
