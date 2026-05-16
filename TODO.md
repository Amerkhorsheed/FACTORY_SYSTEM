<!-- ╔══════════════════════════════════════════════════════════════════════════╗ -->
<!-- ║                   TODO.md — SPRINT TASK BOARD                           ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════════╝ -->

# 📝 Sprint Task Board

> **Project:** Factory Distribution & Shipping Management System
> **Last Updated:** 2026-05-17 01:20 (Asia/Damascus)
> **Current Sprint:** Stream 2 — Frontend Completion
> **Sprint Goal:** Upgrade all module Blade views to production-quality RTL screens using shared layout and components

---

## 🔴 BLOCKING — Resolve Before Proceeding

| ID       | Severity | Issue                               | Impact                                   | Resolution                                     |
|----------|----------|--------------------------------------|------------------------------------------|-------------------------------------------------|
| BLK-001  | ⚠️ Low   | PHP 8.2.12 detected — spec says 8.3 | Laravel 11 supports 8.2 — non-blocking  | Deployment uses PHP 8.3                 |

---

## ✅ COMPLETED — Phases 00–07 + Frontend Foundation

<details>
<summary><strong>Phase 00 — Project Bootstrap</strong> ✅ (Session 001)</summary>

- [x] composer create-project laravel/laravel factory-system "^11.0"
- [x] Install all Composer packages (6 production + 3 dev)
- [x] Install all NPM packages (10 packages including Tailwind RTL)
- [x] Configure `.env` with Arabic locale, Damascus timezone, factory vars
- [x] Create `config/factory.php` with all status labels & prefixes
- [x] Create `config/money.php` and `config/pdf.php`
- [x] Publish Spatie Permission, ActivityLog, and DomPDF vendor files
- [x] `php artisan key:generate` + `storage:link`
- [x] Phase 00 exit checkpoint verified (serve, build, cache — all pass)

</details>

<details>
<summary><strong>Phase 01 — Database & Migrations</strong> ✅ (Session 002)</summary>

- [x] 17 migration files created in strict numbered order
- [x] All monetary columns: `unsignedBigInteger` (BIGINT) — zero float/decimal
- [x] Foreign key constraints with `restrictOnDelete()`
- [x] Composite indexes on orders, products, customers
- [x] `softDeletes()` on all core tables
- [x] `php artisan migrate:fresh` → zero errors (SQLite)

</details>

<details>
<summary><strong>Phase 02 — Value Objects & State Machines</strong> ✅ (Session 003)</summary>

- [x] `app/ValueObjects/Money.php` — immutable, currency-safe, integer-only
- [x] `app/StateMachines/OrderStateMachine.php` — 8 statuses, transition map
- [x] `app/StateMachines/ShipmentStateMachine.php` — 5 statuses
- [x] `app/Exceptions/InvalidStatusTransitionException.php`
- [x] Unit tests: MoneyValueObjectTest, OrderStateMachineTest, ShipmentStateMachineTest

</details>

<details>
<summary><strong>Phase 03 — Base Classes & Contracts</strong> ✅ (Session 004)</summary>

- [x] `BaseService.php` — transaction(), money(), paginate(), parseMoney()
- [x] `BaseRepository.php` — findById(), create(), update(), delete()
- [x] 6 repository interfaces + 5 service interfaces + ExportStrategyInterface
- [x] AppServiceProvider DI bindings for all interface → concrete pairs

</details>

<details>
<summary><strong>Phase 04 — Models, Traits & Observers</strong> ✅ (Session 005)</summary>

- [x] 14 Eloquent models with relationships, scopes, casts
- [x] 3 model traits: GeneratesSequentialCode, HasMoneyFormatting, HasSoftDeleteGuard
- [x] CodeGeneratorFactory for ORD/INV/SHP/PAY/CUS prefixed codes
- [x] 4 observers: Order, Product, Invoice, Payment (Spatie ActivityLog)
- [x] EventServiceProvider observer registration

</details>

<details>
<summary><strong>Phase 05 — Seeders & RBAC</strong> ✅ (Session 006)</summary>

- [x] RolesAndPermissionsSeeder — 4 roles, 30+ granular permissions
- [x] SystemSettingsSeeder — default factory settings
- [x] AdminUserSeeder — super_admin user
- [x] ProductCategorySeeder — initial categories
- [x] DatabaseSeeder orchestrator

</details>

<details>
<summary><strong>Phase 06 — Authentication & Middleware</strong> ✅ (Session 007)</summary>

- [x] LoginController — email/phone login with rate limiting
- [x] 4 middleware: SetLocale, CheckUserIsActive, CustomerPortal, LastActivity
- [x] Route groups: admin (auth), portal (customer), API (charts)
- [x] Arabic login page, auth translations

</details>

<details>
<summary><strong>Phase 07 — All 7 Business Modules</strong> ✅ (Sessions 008–015)</summary>

- [x] **07.01 Inventory** — ProductService, StockService, CRUD, stock movements, policies
- [x] **07.02 Customers** — CustomerService, DTO, credit calculations, portal access
- [x] **07.03 Orders ★** — Pipeline (3 pipes), 3 sub-services, DTOs, full lifecycle
- [x] **07.04 Distribution** — ShipmentService, status transitions, manifest stub
- [x] **07.05 Invoicing** — InvoiceService, payment recording, balance recalculation
- [x] **07.06 Payments & ERP** — Expenses, dashboard KPIs, 4 report datasets
- [x] **07.07 Admin** — User management, system settings, audit log

</details>

<details>
<summary><strong>Phase 08 — Frontend Foundation</strong> ✅ Partial (Session 016)</summary>

- [x] Tailwind/PostCSS RTL configuration + Alpine.js/Tom Select/Flatpickr setup
- [x] Shared RTL app layout with responsive sidebar, topbar, flash alerts
- [x] 10 reusable Blade components (btn, card, page-header, metric-card, etc.)
- [x] Admin and ERP dashboard upgraded to shared layout
- [x] Customer portal: repository, service, controller, routes, views, tests
- [ ] Replace remaining module placeholder Blade pages with shared layout
- [ ] Add richer responsive CRUD screens for all modules

</details>

---

## 🟡 CURRENT SPRINT — Stream 2: Frontend Completion

### 🎯 Sprint Goal

> Upgrade all module placeholder Blade views to production-quality RTL screens using the shared layout, components, and Tailwind CSS system.

| Task ID    | Module         | Scope                                                    | Status  |
|------------|----------------|----------------------------------------------------------|---------|
| FE-001     | Components     | Add 5 new components (confirm-modal, search, table, timeline, money-input) | ⬜ Todo |
| FE-002     | Products       | Upgrade 7 product/stock views with data tables, filters  | ⬜ Todo |
| FE-003     | Customers      | Upgrade customer views with credit indicators, statements | ⬜ Todo |
| FE-004     | Orders         | Upgrade 6 order views with status timeline, item tables   | ⬜ Todo |
| FE-005     | Shipments      | Upgrade shipment views with driver/truck assignment       | ⬜ Todo |
| FE-006     | Invoices       | Upgrade invoice views with payment history, PDF download  | ⬜ Todo |
| FE-007     | Payments       | Upgrade payment views with method filters, money format   | ⬜ Todo |
| FE-008     | Expenses/ERP   | Upgrade expense and report views with charts, exports     | ⬜ Todo |

---

## 📅 UPCOMING SPRINTS

<details>
<summary><strong>Stream 3 — Event Listeners & Architecture Alignment</strong> (~17 files)</summary>

| Task ID    | Task                                                        | Status  |
|------------|-------------------------------------------------------------|---------|
| EL-001     | `app/Listeners/DeductStockOnOrderAccepted.php`              | ⬜ Todo |
| EL-002     | `app/Listeners/CreateInvoiceOnOrderAccepted.php`            | ⬜ Todo |
| EL-003     | `app/Listeners/ReturnStockOnOrderCancelled.php`             | ⬜ Todo |
| EL-004     | `app/Listeners/NotifyCustomerOnOrderStatusChange.php`       | ⬜ Todo |
| EL-005     | `app/Listeners/UpdateCustomerBalanceOnInvoiceIssued.php`    | ⬜ Todo |
| EL-006     | `app/Listeners/NotifyCustomerOnPaymentReceived.php`         | ⬜ Todo |
| EL-007     | `app/Listeners/SendLowStockAlert.php`                       | ⬜ Todo |
| EL-008     | Missing events: InvoiceIssued, PaymentReceived, OrderShipped| ⬜ Todo |
| EL-009     | `app/Exceptions/InvoiceCannotBeVoidedException.php`         | ⬜ Todo |
| EL-010     | `database/seeders/DemoDataSeeder.php`                       | ⬜ Todo |
| EL-011     | Register all event→listener mappings in EventServiceProvider| ⬜ Todo |

</details>

<details>
<summary><strong>Stream 4 — PDF Generation</strong> (~8 files)</summary>

| Task ID    | Task                                                        | Status  |
|------------|-------------------------------------------------------------|---------|
| PDF-001    | Rewrite `PdfService.php` with DomPDF Arabic RTL             | ⬜ Todo |
| PDF-002    | `resources/views/pdf/invoice.blade.php`                     | ⬜ Todo |
| PDF-003    | `resources/views/pdf/invoice-return.blade.php`              | ⬜ Todo |
| PDF-004    | `resources/views/pdf/shipment-manifest.blade.php`           | ⬜ Todo |
| PDF-005    | `resources/views/pdf/customer-statement.blade.php`          | ⬜ Todo |
| PDF-006    | Arabic font setup for DomPDF                                | ⬜ Todo |
| PDF-007    | `tests/Unit/PdfServiceTest.php`                             | ⬜ Todo |
| PDF-008    | `tests/Feature/PdfDownloadTest.php`                         | ⬜ Todo |

</details>

<details>
<summary><strong>Stream 5 — Notifications & Livewire</strong> (~15 files)</summary>

- [ ] 5 notification classes (OrderStatusChanged, InvoiceIssued, PaymentReceived, LowStockAlert, InvoiceOverdue)
- [ ] NotificationBell Livewire component
- [ ] 4 email templates + shared layout
- [ ] 3 Artisan scheduled commands (overdue alerts, low stock check, backup)

</details>

<details>
<summary><strong>Stream 6 — Test Suite Hardening</strong> (~8 files)</summary>

- [ ] InvoiceServiceTest, PdfServiceTest (unit)
- [ ] OrderLifecycleTest, OrderCancellationTest, RoleAccessTest (feature)
- [ ] Edge cases and negative tests on existing files
- [ ] Target: 200+ tests, ≥80% coverage

</details>

<details>
<summary><strong>Stream 7 — Deployment</strong> (~10 files)</summary>

- [ ] Nginx config + Supervisor
- [ ] `deploy.sh` + optional CI pipeline
- [ ] Error pages (404, 403, 500) in Arabic
- [ ] 3 export strategies (Excel, CSV, PDF)

</details>

---

## ✅ DONE — Completed Tasks Summary

| Phase     | Tasks Completed | Session | Tests Passing |
|-----------|-----------------|---------|---------------|
| Phase 00  | 16 tasks        | 001     | 2/2           |
| Phase 01  | 17 migrations   | 002     | 2/2           |
| Phase 02  | 5 files         | 003     | 32/32         |
| Phase 03  | 15+ files       | 004     | 40/40         |
| Phase 04  | 22 files        | 005     | 46/46         |
| Phase 05  | 5 seeders       | 006     | 53/53         |
| Phase 06  | 12 files        | 007     | 65/65         |
| Phase 07  | 100+ files      | 008–015 | 146/146       |
| Phase 08  | 20+ files       | 016     | 153/153       |
| **Total** | **200+ files**  | **16**  | **153/153**   |

---

## 📊 Sprint Velocity

| Sprint    | Phase       | Tasks Planned | Tasks Completed | Velocity | Notes                         |
|-----------|-------------|---------------|-----------------|----------|-------------------------------|
| Week 01   | 00–08       | ~200          | ~200            | 100%     | 16 sessions, 153 tests        |
| Week 02   | Streams 1–4 | ~65           | In progress     | —        | Documentation + Frontend + PDF |

---

*Updated by FACTORY-AGENT after every task completion. Tasks move from current sprint → ✅ DONE as they complete.*
