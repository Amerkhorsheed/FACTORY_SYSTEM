<!-- ╔══════════════════════════════════════════════════════════════════════════╗ -->
<!-- ║             TASKS.md — MASTER REQUIREMENTS & EXECUTION INDEX            ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════════╝ -->

# 📋 Master Requirements & Execution Index

> **Project:** Factory Distribution & Shipping Management System  
> **نظام إدارة معمل التوزيع والشحن**  
> **Version:** 1.0.0 · **Status:** In Development  
> **Stack:** Laravel 11 · PHP 8.3 · MySQL 8.0 · Blade + Livewire 3 + Alpine.js · Tailwind CSS 3  
> **⚠️ This file is READ-ONLY — do not modify during execution.**

---

## 📚 Source Documents

The complete system specification is contained in **6 detailed prompt files** totaling **~663 KB**. These are the **single authoritative source of truth** for all requirements, code patterns, and implementation details.

| Part | File                                            | Lines  | Size    | Specification Coverage                                                                                   |
|------|-------------------------------------------------|--------|---------|----------------------------------------------------------------------------------------------------------|
| 1    | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM.md`           | 2,388  | ~88 KB  | Agent identity · SOLID principles · 12 design patterns · Directory structure · Phases 00–18 · Config files · `.env` template · `config/factory.php` with all status labels |
| 2    | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART2.md`     | 3,187  | ~121 KB | 5 DTOs (CreateOrderDTO, OrderItemDTO, RecordPaymentDTO, CreateCustomerDTO, CreateShipmentDTO) · OrderRepository · ProductRepository · OrderService · OrderStatusService · OrderFinancialsService · InvoiceService · SettingService · Pipeline pipes · Controllers (Product, Customer, Order, Invoice, Shipment, ERP) · Form Requests · Livewire components · Blade views |
| 3    | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART3.md`     | 3,387  | ~113 KB | Model Traits (HasMoneyFormatting, HasSoftDeleteGuard, GeneratesSequentialCode) · Full Models (Product, Customer, Invoice, Shipment) · 4 Observers · 5 Notifications · PdfService · ReportService · Frontend CSS/JS setup · Deployment (Docker, Nginx, Supervisor) · Seeders · README.md · CHANGELOG.md |
| 4    | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART4.md`     | 3,526  | ~117 KB | 7 Policies (Order, Invoice, Payment, Product, Customer, Shipment, Expense) · AuthServiceProvider · 4 Middleware · LoginController · ShipmentService · CustomerService · CustomerPortalController · Livewire (ShipmentOrderAssignment, NotificationBell) · Email templates · Error pages · 3 Export strategies · `config/money.php` · `config/pdf.php` · `bootstrap/app.php` |
| 5    | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART5.md`     | 3,411  | ~109 KB | Models (Truck, Driver, OrderItem, StockMovement, Expense, SystemSetting, ProductCategory, Payment, User, Order) · Repositories (Invoice, Shipment, Customer, StockMovement) · Services (ProductService, StockService, CustomerService, ExpenseService) · Events · Listeners · Model Factories (9) · Seeders (6) · Unit Tests (7) · Feature Tests (10) |
| 6    | `DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART6.md`     | ~3,000 | ~115 KB | Additional specifications, extended requirements, supplementary patterns and implementation details        |

**Cross-reference guide — which Part to read for each module:**

| Module                  | Primary Part(s) | Key Sections to Read                                          |
|-------------------------|-----------------|---------------------------------------------------------------|
| Bootstrap & Config      | Part 1          | Phase 00, `.env`, `config/factory.php`                        |
| Database Migrations     | Part 1          | Phase 01, migration example pattern                           |
| Value Objects           | Part 1          | Phase 02, `Money.php`, State Machines                         |
| Base Classes & DI       | Part 1          | Phase 03, directory structure                                 |
| Models & Traits         | Part 3 + 5      | Section A (Traits, Product, Customer, Invoice, Shipment) + Part 5 (remaining) |
| Observers               | Part 3          | Section B (all 4 observers)                                   |
| Seeders & Roles         | Part 5          | Seeders section, RBAC permissions                             |
| Auth & Middleware        | Part 4          | Sections B + C (middleware, LoginController)                   |
| Products Module         | Part 2 + 5      | ProductRepository, ProductService, StockService               |
| Customers Module        | Part 2 + 4      | CustomerRepository, CustomerService, CustomerPortalController |
| Orders Module           | Part 2          | All Services, Pipeline pipes, Controllers, Form Requests       |
| Distribution Module     | Part 4 + 5      | ShipmentService, ShipmentRepository                           |
| Invoicing Module        | Part 2 + 5      | InvoiceService, InvoiceRepository, PaymentObserver            |
| PDF Generation          | Part 3          | PdfService, PDF Blade templates                               |
| ERP & Reports           | Part 3          | ReportService, Dashboard views                                |
| Frontend                | Part 3          | CSS/JS setup, Blade components                                |
| Notifications           | Part 3          | 5 notification classes                                        |
| Policies & Security     | Part 4          | Section A (all 7 policies), AuthServiceProvider               |
| Tests                   | Part 5          | Unit tests (7 files), Feature tests (10 files)                |
| Deployment              | Part 3          | Docker, Nginx, Supervisor configs                             |

---

## 🗺️ Execution Roadmap

Phases **must** be executed in strict sequential order. Each phase has defined **entry criteria**, **key deliverables**, and **exit checkpoints** that must pass before proceeding.

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                              EXECUTION FLOW DIAGRAM                                      │
│                                                                                          │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐              │
│  │ Phase 00 │──→│ Phase 01 │──→│ Phase 02 │──→│ Phase 03 │──→│ Phase 04 │              │
│  │Bootstrap │   │ Database │   │ ValueObj │   │ Base/DI  │   │ Models   │              │
│  │ Setup    │   │Migrations│   │  State   │   │Contracts │   │Observers │              │
│  └──────────┘   └──────────┘   └──────────┘   └──────────┘   └──────────┘              │
│       │                                                            │                     │
│       ▼                                                            ▼                     │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐              │
│  │ Phase 05 │──→│ Phase 06 │──→│ Phase 07 │──→│ Phase 08 │──→│ Phase 09 │              │
│  │ Seeders  │   │   Auth   │   │Inventory │   │Customers │   │Orders ★  │              │
│  │  RBAC    │   │Middleware│   │ Products │   │ Credit   │   │Pipeline  │              │
│  └──────────┘   └──────────┘   └──────────┘   └──────────┘   └──────────┘              │
│                                                                    │                     │
│                                                                    ▼                     │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐              │
│  │ Phase 10 │──→│ Phase 11 │──→│ Phase 12 │──→│ Phase 13 │──→│ Phase 14 │              │
│  │  Distri- │   │Invoicing │   │   PDF    │   │   ERP    │   │Frontend  │              │
│  │  bution  │   │ Payments │   │ Arabic   │   │ Reports  │   │RTL/Blade │              │
│  └──────────┘   └──────────┘   └──────────┘   └──────────┘   └──────────┘              │
│                                                                    │                     │
│                                                                    ▼                     │
│  ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐                              │
│  │ Phase 15 │──→│ Phase 16 │──→│ Phase 17 │──→│ Phase 18 │                              │
│  │  Notif.  │   │Security  │   │  Tests   │   │  Deploy  │                              │
│  │  Email   │   │Hardening │   │ ≥80% cov │   │  Docker  │                              │
│  └──────────┘   └──────────┘   └──────────┘   └──────────┘                              │
│                                                                                          │
│  ★ = Most complex module (Orders: 25+ files, Pipeline, State Machine, 5+ tests)         │
│                                                                                          │
│  LEGEND: Each box represents a Phase with specific entry/exit criteria                   │
│          Arrows show strict sequential dependency                                        │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### Phase Details

#### Foundation Layer (Phases 00–05) — Infrastructure & Data

| Phase | Name                           | Source          | Key Deliverables                                                                          | Files Created | Exit Checkpoint                                       |
|-------|--------------------------------|-----------------|-------------------------------------------------------------------------------------------|---------------|-------------------------------------------------------|
| 00    | Project Bootstrap              | Part 1 §Phase00 | Laravel 11 install, 9 Composer packages, 10 NPM packages, `.env`, 3 config files, vendor publish | ~20           | `php artisan serve` → no errors                       |
| 01    | Database & Migrations          | Part 1 §Phase01 | 17 migration files, all BIGINT money columns, composite indexes, FK constraints           | 17            | `php artisan migrate:fresh` → zero errors             |
| 02    | Value Objects & State Machines | Part 1 §Phase02 | `Money.php`, `OrderStateMachine.php`, `ShipmentStateMachine.php`, unit tests              | 5             | All state machine + Money tests passing               |
| 03    | Base Classes & Contracts       | Part 1 §Phase03 | `BaseService`, `BaseRepository`, 6 repo interfaces, 5 service interfaces, DI bindings    | 15+           | `php artisan clear-compiled` → no errors              |
| 04    | Models, Traits & Observers     | Part 3+5        | 14 models, 4 traits, 4 observers, all relationships, scopes, casts                       | 22            | `php artisan tinker` → all relationships verified     |
| 05    | Seeders & RBAC                 | Part 5          | 6 seeders, 4 roles (super_admin, admin, accountant, warehouse, sales, driver, customer), 30+ permissions | 6  | `php artisan migrate:fresh --seed` → zero errors      |

#### Core Business Layer (Phases 06–11) — Domain Logic

| Phase | Name                           | Source          | Key Deliverables                                                                          | Files Created | Exit Checkpoint                                       |
|-------|--------------------------------|-----------------|-------------------------------------------------------------------------------------------|---------------|-------------------------------------------------------|
| 06    | Authentication & Middleware    | Part 4 §B–C    | Login via email/phone, rate limiting, 4 middleware, route groups (admin, portal, API)      | 12            | Auth tests passing, role redirects working            |
| 07    | Inventory Module               | Part 2+5       | ProductService, StockService, ProductController, StockController, CRUD views, categories  | 18            | Product CRUD + stock movement tests passing           |
| 08    | Customer Module                | Part 2+4       | CustomerService, CustomerController, portal, credit checks, statement view                | 15            | Customer CRUD + credit limit tests passing            |
| 09    | Orders Module ★                | Part 2         | OrderService (3 sub-services), Pipeline (3 pipes), 3 Controllers, DTOs, full lifecycle    | 25+           | Full lifecycle test: `pending` → `delivered`          |
| 10    | Distribution Module            | Part 4+5       | ShipmentService, TruckController, DriverController, manifest PDF, order assignment        | 18            | Shipment flow test passing                            |
| 11    | Invoicing & Payments           | Part 2+5       | InvoiceService, PaymentController, balance recalculation, statement generation            | 16            | Payment flow test, customer balance verification      |

#### Presentation & Cross-Cutting (Phases 12–18)

| Phase | Name                           | Source          | Key Deliverables                                                                          | Files Created | Exit Checkpoint                                       |
|-------|--------------------------------|-----------------|-------------------------------------------------------------------------------------------|---------------|-------------------------------------------------------|
| 12    | PDF Generation                 | Part 3         | DomPDF Arabic RTL setup, 4 PDF templates (invoice, return, manifest, statement)           | 8             | All 4 PDF types render Arabic correctly               |
| 13    | ERP Dashboard & Reports        | Part 3         | Dashboard KPIs, 5 report views, export strategies (Excel, CSV, PDF), Chart.js             | 12            | Dashboard loads with charts, exports work             |
| 14    | Frontend Architecture          | Part 3         | RTL master layout, 15+ Blade components, sidebar, Alpine.js interactions, responsive      | 30+           | All pages render correctly, responsive verified       |
| 15    | Notifications                  | Part 3         | 5 notification classes, database + email channels, NotificationBell Livewire component    | 10            | Bell works, emails render in Arabic                   |
| 16    | Security Hardening             | Part 4         | 7 policies enforced, CSRF on all forms, customer data scoping, rate limiting              | 7             | Role access tests passing                             |
| 17    | Full Test Suite                | Part 5         | 7 unit tests, 10 feature tests, ≥80% coverage on Services + Models                       | 17+           | `php artisan test` → zero failures, coverage ≥ 80%   |
| 18    | Deployment                     | Part 3         | Docker Compose (4 containers), Nginx config, Supervisor, `deploy.sh`, SSL                 | 8             | `docker-compose up` → all services healthy            |

---

## 🏗️ Entity Relationship Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                     ENTITY RELATIONSHIP MAP                          │
│                                                                      │
│  User ──────────────────────────────┐                                │
│    │ (created_by FK on many tables) │                                │
│    │                                │                                │
│  Customer ─────────┬───── Order ────┬───── OrderItem ──── Product   │
│    │  (1:N)        │      │ (1:N)   │       (N:1)          │        │
│    │               │      │         │                      │        │
│    │               │      ├── Shipment ──── Truck           │        │
│    │               │      │              └── Driver          │        │
│    │               │      │                                  │        │
│    ├── Invoice ────┘      └── Invoice                       │        │
│    │    │ (1:N)                │ (1:1 via order_id)          │        │
│    │    │                     │                              │        │
│    │    └── Payment           │                              │        │
│    │         (N:1)            │                              │        │
│    │                          │                              │        │
│    └── outstanding_balance ←──┘ (cached, recalculated)      │        │
│                                                              │        │
│  Product ──── ProductCategory (N:1)                         │        │
│    │                                                         │        │
│    └── StockMovement (1:N, immutable audit trail)           │        │
│                                                              │        │
│  Expense (standalone, categorized)                          │        │
│  SystemSetting (key-value config store)                     │        │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🚨 Non-Negotiable Rules

These rules apply to **every line of code** in the project. Violations must be corrected **immediately** — no exceptions, no "we'll fix it later."

| #   | Rule                                                                     | How to Verify                                      | Fix Strategy                              |
|-----|--------------------------------------------------------------------------|----------------------------------------------------|--------------------------------------------|
| 01  | No file exceeds **400 lines**                                            | `find app -name "*.php" \| xargs wc -l`            | Split into sub-classes/traits/partials    |
| 02  | Money is **BIGINT**, never float/decimal                                 | `grep -rn 'float.*price\|decimal.*amount' app/`    | Change to integer + Money value object    |
| 03  | Business logic in **Services only**                                      | Controllers ≤ 30 lines per method                  | Extract to appropriate Service class      |
| 04  | Services use **Repositories**, not Eloquent                              | `grep -rn 'Model::' app/Services/`                 | Inject repository interface               |
| 05  | All DB writes in **`DB::transaction()`**                                 | Review every `create()`/`update()`/`delete()`      | Wrap in `$this->transaction()`            |
| 06  | Every controller calls **`$this->authorize()`**                          | `grep -rL 'authorize' app/Http/Controllers/`       | Add authorize() as first line             |
| 07  | Arabic strings in **`lang/ar/`** only                                    | `grep -rP '[\x{0600}-\x{06FF}]' app/ --include="*.php"` | Move to lang file, use `__()`       |
| 08  | Lists are **paginated** — no unbounded `->get()`                         | Review every controller `index()` method           | Use `->paginate()` with `withQueryString` |
| 09  | Status changes via **State Machine** only                                | No direct `->update(['status' => ...])`            | Use `$stateMachine->transition()`         |
| 10  | Services accept **DTOs**, not arrays                                     | Type hints on service method parameters            | Create DTO class with `fromArray()`       |

---

## 📦 File Manifest — Total Expected Files

| Category                  | Count  | Location                                  |
|---------------------------|--------|-------------------------------------------|
| Management docs           | 6      | Root (AGENT, PROGRESS, TODO, DECISIONS, SKILLS, TASKS) |
| Migrations                | 17     | `database/migrations/`                    |
| Models                    | 14     | `app/Models/`                             |
| Model Traits              | 4      | `app/Models/Traits/`                      |
| Observers                 | 4      | `app/Observers/`                          |
| Services                  | 14     | `app/Services/`                           |
| Repositories              | 7      | `app/Repositories/` (including Base)      |
| Repository Interfaces     | 6      | `app/Contracts/Repositories/`             |
| Service Interfaces        | 5      | `app/Contracts/Services/`                 |
| DTOs                      | 5      | `app/DTOs/`                               |
| Controllers               | 16     | `app/Http/Controllers/`                   |
| Form Requests             | 10     | `app/Http/Requests/`                      |
| Middleware                 | 4      | `app/Http/Middleware/`                    |
| Policies                  | 7      | `app/Policies/`                           |
| Events                    | 7      | `app/Events/`                             |
| Listeners                 | 8      | `app/Listeners/`                          |
| Notifications             | 5      | `app/Notifications/`                      |
| Livewire Components       | 7      | `app/Livewire/`                           |
| Blade Views               | 50+    | `resources/views/`                        |
| Blade Components          | 15+    | `resources/views/components/`             |
| PDF Templates             | 4      | `resources/views/pdf/`                    |
| Email Templates           | 5      | `resources/views/emails/`                 |
| Config Files              | 3      | `config/` (factory, money, pdf)           |
| Lang Files                | 7      | `lang/ar/`                                |
| Seeders                   | 6      | `database/seeders/`                       |
| Model Factories           | 9      | `database/factories/`                     |
| Unit Tests                | 7      | `tests/Unit/`                             |
| Feature Tests             | 10     | `tests/Feature/`                          |
| State Machines            | 2      | `app/StateMachines/`                      |
| Value Objects             | 1      | `app/ValueObjects/`                       |
| Exceptions                | 5      | `app/Exceptions/`                         |
| Facades                   | 1      | `app/Facades/`                            |
| Factories (code gen)      | 1      | `app/Factories/`                          |
| Pipeline Pipes            | 3      | `app/Pipelines/Order/`                    |
| Artisan Commands          | 3      | `app/Console/Commands/`                   |
| JS Files                  | 3      | `resources/js/`                           |
| CSS Files                 | 1      | `resources/css/`                          |
| Providers                 | 3      | `app/Providers/`                          |
| Deployment Files          | 5      | `docker/`, `supervisor/`, root            |
| **TOTAL**                 | **~270** | —                                       |

---

## 📊 Current Phase

```
╔═══════════════════════════════════════════════════════════════════╗
║                                                                   ║
║   CURRENT PHASE:  Frontend Completion (Stream 2)                 ║
║   STATUS:         🟡 In Progress (70% overall)                    ║
║   COMPLETED:      Phases 00–07 + Frontend Foundation (153 tests) ║
║   REMAINING:      Frontend views, PDF, Notifications, Deploy     ║
║   ESTIMATED:      ~270 files total · ~200 built · ~85 remaining  ║
║                                                                   ║
╚═══════════════════════════════════════════════════════════════════╝
```

---

*Read this file at the beginning of every session. Cross-reference with the Source Documents table for detailed implementation specifications per module.*
