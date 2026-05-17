<!-- ╔══════════════════════════════════════════════════════════════════════════╗ -->
<!-- ║          TASKS.md — MASTER REQUIREMENTS, EXECUTION & LAUNCH INDEX       ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════════╝ -->

# Master Requirements, Execution & Launch Index

> **Project:** Factory Distribution & Shipping Management System
> **Arabic Name:** نظام إدارة معمل التوزيع والشحن
> **Application Path:** `factory-system/`
> **Version:** 1.0.0 · **Repository Status:** Implementation complete through repository-side launch tooling
> **Current Phase:** Phase 12 — Final Launch Verification on target infrastructure
> **Stack:** Laravel 11 · PHP 8.3 target · MySQL 8 · Redis · Blade · Livewire 3 · Alpine.js · Tailwind CSS 3 · DomPDF
> **Last Verified Locally:** 2026-05-17

---

## 1. Authority Model

This file is the root session-start index. It is not a replacement for the detailed specifications; it tells the agent or engineer exactly where the current truth lives.

| Priority | Document | Authority |
|---|---|---|
| 1 | `implementation_plan.md` | Current implementation and launch baseline. Supersedes older scaffolding where it conflicts with prompt Part 7. |
| 2 | `factory-system/PROGRESS.md` | Live implementation progress, session history, verification status, constraints, and next production steps. |
| 3 | `factory-system/TASKS.md` | Compact application-local task index required by `AGENT.md`. |
| 4 | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM*.md` | Original detailed specification set. Use for missing implementation detail and design intent. |
| 5 | Root management docs | Governance and project overview. If stale, prefer this file plus `implementation_plan.md`. |

**Conflict rule:** if a source prompt conflicts with implemented launch architecture, use `implementation_plan.md` unless the user explicitly asks to restore the older specification.

---

## 2. Source Documents

The complete prompt specification now contains **7 source files**, not 6. Current total: **20,751 lines** and approximately **724 KB**.

| Part | File | Lines | Size | Primary Coverage |
|---|---|---:|---:|---|
| 1 | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM.md` | 2,387 | 85.8 KB | Agent rules, SOLID principles, design patterns, phases 00-18, bootstrap, config, migrations, state machines. |
| 2 | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART2.md` | 3,186 | 117.7 KB | DTOs, repositories, order/product/customer/invoice/shipment services, controllers, requests, Blade and Livewire patterns. |
| 3 | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART3.md` | 3,386 | 110.5 KB | Traits, models, observers, notifications, PDF service, reports, frontend setup, seeders, deployment references. |
| 4 | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART4.md` | 3,525 | 114.7 KB | Policies, middleware, auth, distribution, customer portal, exports, money/pdf config, error pages. |
| 5 | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART5.md` | 3,410 | 106.6 KB | Remaining models, repositories, services, events, listeners, factories, seeders, unit and feature tests. |
| 6 | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART6.md` | 2,850 | 111.9 KB | Additional extended requirements, supplementary implementation details, final prompt refinements. |
| 7 | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART7.md` | 2,007 | 76.9 KB | Late gap specification: Livewire filters, exports, report views, portal/distribution views, migrations, verification scripts, master index template. |

---

## 3. Current Baseline

| Area | Current State |
|---|---|
| Repository implementation | Phases 00 through 12 are implemented on the repository side. |
| Remaining work | Production-host validation only: HTTPS, PHP 8.3, MySQL, Redis, workers, scheduler, backups, SMTP, browser/PDF visual smoke tests. |
| Local tests | `php artisan test` → 180 passed, 481 assertions in the latest local run. |
| Application routes | `php artisan route:list --except-vendor --json` → 99 application routes. |
| Total routes | `php artisan route:list --json` → 114 total routes including vendor routes. |
| Scheduled commands | `factory:overdue-alerts`, `factory:low-stock-check`, `factory:backup`. |
| Frontend build | `npm run build` passed locally. |
| Local preflight | `php artisan factory:preflight --json` → 36 passed, 14 warnings, 0 failures. |
| Local PHP | 8.2.12. Production target remains PHP 8.3+. |
| Working-tree caveat | `vendor\bin\pint --test` now passes locally. `factory-system/database/seeders/SystemTestSeeder.php` is implemented but still untracked until intentionally added. |

---

## 4. Execution Roadmap

The original phases were executed sequentially, then compacted into the current launch baseline. Do not reopen completed modules unless launch validation finds a concrete defect.

| Phase | Status | Delivered Outcome | Primary Evidence |
|---|---|---|---|
| 00 Bootstrap | Complete | Laravel 11 app, packages, config, key/storage verification. | `composer.json`, `package.json`, config files. |
| 01 Database | Complete | 17 migrations for users, products, customers, logistics, orders, invoices, payments, expenses, settings, permissions, activity log, notifications. | `factory-system/database/migrations/`. |
| 02 Domain Primitives | Complete | `Money`, order state machine, shipment state machine, invalid transition exception. | Unit tests pass. |
| 03 Base Architecture | Complete | Base service/repository primitives, contracts, export strategy interface, DI bindings. | Provider binding tests pass. |
| 04 Models & Observers | Complete | 14 models, traits, code generation, soft-delete guards, 4 observers, activity logging. | Model layer tests pass. |
| 05 Seeders & RBAC | Complete | Roles, permissions, settings, default users, categories, demo/system seeders. | Seeder tests pass. |
| 06 Auth & Middleware | Complete | Login/logout, active user checks, locale, portal guard, last activity, role redirects. | Auth tests pass. |
| 07.01 Inventory | Complete | Products, categories, stock adjustments, stock movements, low-stock detection. | Product and stock tests pass. |
| 07.02 Customers | Complete | Customer CRUD, portal access, credit calculations, statements. | Customer tests pass. |
| 07.03 Orders | Complete | Order pipeline, update validation, stock/credit validation, lifecycle transitions, financials. | Order lifecycle and update-rule tests pass. |
| 07.04 Distribution | Complete | Trucks, drivers, shipments, assignment, dispatch/cancel/guarded deliver, manifests. | Shipment tests pass. |
| 07.05 Invoicing | Complete | Issue/void/download invoices, invoice-scoped payments, recalculation logic. | Invoice/payment tests pass. |
| 07.06 Payments & ERP | Complete | Payments, expenses, dashboard KPIs, sales/receivables/stock/profit-loss reports. | Dashboard and CRUD tests pass. |
| 07.07 Admin | Complete | User management, settings, audit log, policies, temporary password notifications. | Admin tests pass. |
| 08 Frontend | Complete | Shared Arabic RTL layout, module views, public auth/welcome polish, customer portal. | Frontend render tests pass. |
| 09 PDF | Complete | DomPDF service, private PDF storage, Arabic invoice/manifest/statement templates. | PDF feature tests pass. |
| 10 Notifications | Complete | Queued database/mail notifications, staff digests, Livewire notification bell, scheduled digests. | Notification tests pass. |
| 11 Deployment Assets | Complete | Production env template, Nginx, Supervisor, deploy script, runbook, launch checklist, error pages. | Deployment readiness tests pass. |
| 12 Final Launch | In Progress | Repo-side tooling complete; target-host runtime verification remains. | `factory:preflight --production --runtime` must pass on production. |

---

## 5. Module Cross-Reference

| Module | Source Parts | Implemented Location | Current Status |
|---|---|---|---|
| Bootstrap & Config | 1 | `factory-system/config/`, `.env.example`, `.env.production.example` | Complete |
| Database | 1, 7 | `factory-system/database/migrations/` | Complete |
| Domain Primitives | 1 | `app/ValueObjects/`, `app/StateMachines/` | Complete |
| Architecture Contracts | 1, 2, 5 | `app/Contracts/`, `app/Services/`, `app/Repositories/` | Complete |
| Inventory | 2, 5 | `app/Services/Products/`, `routes/products.php`, `resources/views/products/` | Complete |
| Customers & Portal | 2, 4, 7 | `app/Http/Controllers/Customers/`, `routes/customers.php`, `routes/portal.php` | Complete |
| Orders | 2, 7 | `app/Services/Orders/`, `app/Pipelines/Order/`, `routes/orders.php` | Complete |
| Distribution | 4, 5, 7 | `app/Services/Distribution/`, `routes/shipments.php`, `resources/views/shipments/` | Complete |
| Invoices & Payments | 2, 5 | `app/Services/Invoices/`, `routes/invoices.php`, `routes/payments.php` | Complete |
| ERP & Reports | 3, 5, 7 | `app/Services/Erp/`, `routes/erp.php`, `resources/views/erp/` | Complete |
| Frontend & RTL | 3, 7 | `resources/views/`, `resources/css/app.css`, `resources/js/app.js` | Complete |
| PDF Generation | 3, 4 | `app/Services/PdfService.php`, `resources/views/pdf/` | Complete |
| Notifications | 3, 5 | `app/Notifications/`, `app/Listeners/`, `app/Livewire/NotificationBell.php` | Complete |
| Security | 4 | `app/Policies/`, form requests, middleware, route groups | Complete |
| Deployment | 3, `implementation_plan.md` | `deploy.sh`, `nginx/factory.conf`, `supervisor/factory.conf`, `DEPLOYMENT.md` | Target-host validation pending |

---

## 6. Architecture Baseline

```text
HTTP Request
  -> Middleware
  -> FormRequest validation
  -> Controller authorization and DTO construction
  -> Service orchestration, transactions, events, PDFs, notifications
  -> Repository query/data access
  -> Model relationships, casts, observers, policies
```

| Principle | Required Standard |
|---|---|
| Controllers | Thin; authorize every action; no business or query-heavy logic. |
| Services | Own business decisions, transactions, lifecycle transitions, cross-module orchestration. |
| Repositories | Own Eloquent query composition, eager loading, filters, reports, pagination. |
| Money | Stored as integer/BIGINT and handled through `Money`; never float/decimal arithmetic. |
| Statuses | Changed through state machines/services, not ad-hoc controller updates. |
| Events | Explicitly registered; automatic event discovery remains disabled. |
| PDFs | Generated through `PdfService`, stored privately, streamed/downloaded through authorized routes. |
| Notifications | Database/mail delivery via queued notifications and scheduled digest commands. |
| Localization | Arabic strings belong in `lang/ar`; application UI is RTL-first. |

---

## 7. Domain Relationship Map

```text
User
  -> creates/manages many operational records

Customer
  -> Orders -> OrderItems -> Products -> ProductCategory
  -> Invoices -> Payments
  -> Portal user account
  -> Cached outstanding balance recalculated by services/listeners

Order
  -> Customer
  -> OrderItems
  -> Shipment assignment
  -> Invoice
  -> State machine lifecycle

Shipment
  -> Truck
  -> Driver
  -> Assigned orders
  -> Manifest PDF

Product
  -> StockMovements immutable audit trail
  -> Low-stock detection and notifications

Expense, SystemSetting, ActivityLog
  -> ERP/admin support entities
```

---

## 8. Non-Negotiable Engineering Rules

| # | Rule | Verification Gate |
|---:|---|---|
| 01 | Project-managed files stay under 400 lines. | File-count audit before release; generated lockfiles are excluded. |
| 02 | Money is stored as BIGINT/integer values, never float. | Migration and service review; Money tests. |
| 03 | Business logic belongs in services. | Controller review and feature tests. |
| 04 | Services use repositories for data access. | Service review and binding tests. |
| 05 | Critical writes run inside transactions. | Service review; transaction helper usage. |
| 06 | Every controller action authorizes access. | Policy tests and route/controller audit. |
| 07 | Arabic strings belong in `lang/ar/`. | Translation review; no hardcoded Arabic in PHP application logic. |
| 08 | Lists are paginated. | Index actions and repository methods use pagination. |
| 09 | Status changes go through state-machine-aware services. | Lifecycle tests and state machine tests. |
| 10 | Production target is PHP-FPM/Nginx/Supervisor unless explicitly changed. | `implementation_plan.md`, `DEPLOYMENT.md`, preflight checks. |

---

## 9. Current File Inventory

Working-tree inventory as of 2026-05-17. Counts include current uncommitted files visible to the workspace.

| Category | Current Count | Location |
|---|---:|---|
| App PHP files | 171 | `factory-system/app/**/*.php` |
| Migrations | 17 | `database/migrations/` |
| Models | 14 | `app/Models/` |
| Model traits | 3 | `app/Models/Traits/` |
| Observers | 4 | `app/Observers/` |
| Services | 20 | `app/Services/` |
| Repositories | 13 | `app/Repositories/` |
| Repository interfaces | 6 | `app/Contracts/Repositories/` |
| Service interfaces | 6 | `app/Contracts/Services/` |
| DTOs | 5 | `app/DTOs/` |
| Controllers | 18 | `app/Http/Controllers/` |
| Form requests | 20 | `app/Http/Requests/` |
| Middleware | 4 | `app/Http/Middleware/` |
| Policies | 10 | `app/Policies/` |
| Events | 9 | `app/Events/` |
| Listeners | 8 | `app/Listeners/` |
| Notifications | 6 | `app/Notifications/` |
| Livewire components | 1 | `app/Livewire/` |
| Blade views | 92 | `resources/views/**/*.blade.php` |
| Blade components | 15 | `resources/views/components/` |
| PDF views | 5 | `resources/views/pdf/` |
| Email views | 7 | `resources/views/emails/` |
| Arabic lang files | 18 | `lang/ar/` |
| Seeders | 7 | `database/seeders/` |
| Model factories | 12 | `database/factories/` |
| Unit tests | 6 | `tests/Unit/` |
| Feature tests | 21 | `tests/Feature/` |
| All tests | 28 | `tests/**/*.php` |
| State machines | 2 | `app/StateMachines/` |
| Value objects | 1 | `app/ValueObjects/` |
| Exceptions | 4 | `app/Exceptions/` |
| Export strategies | 3 | `app/Exports/` |
| Console commands | 4 | `app/Console/Commands/` |
| Route files | 11 | `routes/` |
| Primary deployment assets | 6 | `.env.production.example`, `deploy.sh`, `nginx/`, `supervisor/`, `DEPLOYMENT.md`, `LAUNCH_CHECKLIST.md` |

---

## 10. Release Verification Gates

Run from `factory-system/` before any release sign-off:

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
php artisan optimize:clear
```

Run on the real production host after `.env` and services are configured:

```bash
php artisan factory:preflight --production --runtime
```

The production runtime preflight must report **zero failures** before launch approval.

---

## 11. Production Launch Checklist

| Area | Required Before Launch |
|---|---|
| Runtime | PHP 8.3+ FPM with required extensions including Redis and pcntl. |
| Web server | Nginx serves `public/`, HTTP redirects to HTTPS, TLS certificate is valid. |
| Database | MySQL 8 configured, migrations and seeders verified against real credentials. |
| Redis | Cache, queue, session, and maintenance store use Redis. |
| Workers | Supervisor runs `default` and `notifications` queue workers. |
| Scheduler | Laravel scheduler triggers overdue alerts, low-stock checks, and backup command. |
| Mail | Real SMTP sends customer/staff notifications successfully. |
| Backups | `mysqldump` is installed and `factory:backup` writes a valid backup under `storage/app/backups`. |
| Browser smoke | Chrome, Firefox, Edge, Safari; widths 375px, 768px, 1280px. |
| PDF smoke | Invoice, shipment manifest, and customer statement render Arabic correctly. |
| Security | `APP_DEBUG=false`, secure cookies, encrypted sessions, no stack traces, scoped portal data. |

---

## 12. Known Constraints

| Constraint | Current Impact | Resolution |
|---|---|---|
| Local PHP is 8.2.12 | Non-blocking locally; production target is stricter. | Validate PHP 8.3+ on production with preflight. |
| Local Redis unavailable | Local env uses file/sync fallbacks. | Production must use Redis for cache/session/queue. |
| Local MySQL not fully validated | Tests run on SQLite in-memory. | Run migration, seed, and runtime preflight on MySQL. |
| Local SMTP cannot prove delivery | Mail rendering is tested, delivery requires real provider. | Send test notifications after production SMTP setup. |
| Current load-test seeder is untracked | The seeder is implemented and style-clean, but not yet part of version control. | Add it intentionally if the load-test dataset is required for release operations. |
| Production validation requires real host | Repository-side work cannot prove TLS, workers, scheduler, Redis, backups, or browser matrix. | Complete `factory-system/LAUNCH_CHECKLIST.md`. |

---

## 13. Immediate Next Steps

1. Decide whether to keep and commit the implemented `factory-system/database/seeders/SystemTestSeeder.php` load-test dataset seeder.
2. Run final local release gates after the latest hardening and documentation updates.
3. Prepare the production host with PHP 8.3 FPM, Nginx, MySQL 8, Redis, Supervisor, Composer, Node, npm, and `mysqldump`.
4. Configure `.env` from `.env.production.example` with real production secrets and HTTPS `APP_URL`.
5. Run `APP_DIR=/var/www/factory-system ./deploy.sh main` on the host.
6. Run `php artisan factory:preflight --production --runtime` on the host.
7. Complete `factory-system/LAUNCH_CHECKLIST.md` and record final launch evidence in `factory-system/PROGRESS.md`.

---

## 14. Session Start Protocol

At the beginning of every serious implementation or launch session:

1. Read this file first.
2. Read `implementation_plan.md` for the current launch baseline.
3. Read `factory-system/PROGRESS.md` for latest verification and constraints.
4. Check `git status --short` and preserve unrelated user changes.
5. If editing implementation code, run the relevant tests plus the release gates that apply to the changed area.

---

**Final Position:** the application implementation is complete at repository level. The only planned work before launch is final local gate rerun, release diff cleanup/approval, and real target-infrastructure validation.
