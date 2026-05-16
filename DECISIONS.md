<!-- ╔══════════════════════════════════════════════════════════════════════════╗ -->
<!-- ║               DECISIONS.md — ARCHITECTURE DECISION RECORDS              ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════════╝ -->

# 📐 Architecture Decision Records

> **Project:** Factory Distribution & Shipping Management System  
> **Methodology:** [MADR](https://adr.github.io/madr/) (Markdown Architectural Decision Records)  
> **Convention:** Accepted ADRs are **immutable**. To reverse or modify a decision, create a new ADR that supersedes the original and link them.

---

## 📑 Index

| ADR   | Title                                        | Status        | Date       | Supersedes |
|-------|----------------------------------------------|---------------|------------|------------|
| 001   | Money Storage Strategy                       | ✅ Accepted    | 2026-05-16 | —          |
| 002   | Service Layer Architecture                   | ✅ Accepted    | 2026-05-16 | —          |
| 003   | DOCS Directory as Source of Truth             | ✅ Accepted    | 2026-05-16 | —          |
| 004   | Repository Pattern for Data Access           | ✅ Accepted    | 2026-05-16 | —          |
| 005   | State Machine for Lifecycle Status Flows     | ✅ Accepted    | 2026-05-16 | —          |
| 006   | DTO Pattern at Service Boundaries            | ✅ Accepted    | 2026-05-16 | —          |
| 007   | Pipeline Pattern for Order Validation        | ✅ Accepted    | 2026-05-16 | —          |
| 008   | 400-Line File Size Hard Limit                | ✅ Accepted    | 2026-05-16 | —          |
| 009   | Arabic-First Localization via lang/ar/       | ✅ Accepted    | 2026-05-16 | —          |
| 010   | Redis as Unified Infrastructure Driver       | ✅ Accepted    | 2026-05-16 | —          |
| 011   | Observer Pattern for Audit Logging           | ✅ Accepted    | 2026-05-16 | —          |
| 012   | Event-Driven Cross-Domain Coordination       | ✅ Accepted    | 2026-05-16 | —          |
| 013   | Facade Pattern for System Settings           | ✅ Accepted    | 2026-05-16 | —          |
| 014   | Policy-Based Authorization                   | ✅ Accepted    | 2026-05-16 | —          |
| 015   | Docker-Based Production Deployment           | ✅ Accepted    | 2026-05-16 | —          |
| 016   | Local Driver Fallback Until Redis Available  | ✅ Accepted    | 2026-05-16 | 010        |

---

## ADR-001: Money Storage Strategy

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Data Architecture
- **Impact:** All 17 database tables with monetary columns

### Context

The system manages financial operations in Syrian Pounds (SYP) — invoices, payments, order totals, customer balances, expenses, and profit calculations. Floating-point arithmetic introduces compounding rounding errors that are unacceptable in financial software. A single miscalculated piaster, multiplied across thousands of transactions, creates audit discrepancies.

**Affected columns across the system:**

| Table           | Monetary Columns                                                    |
|-----------------|---------------------------------------------------------------------|
| `products`      | `unit_price`, `cost_price`                                          |
| `orders`        | `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `paid_amount` |
| `order_items`   | `unit_price`, `discount_amount`, `line_total`                       |
| `invoices`      | `subtotal`, `discount_amount`, `tax_amount`, `total_amount`, `paid_amount`, `balance_due` |
| `payments`      | `amount`                                                            |
| `customers`     | `credit_limit`, `outstanding_balance`                               |
| `expenses`      | `amount`                                                            |
| `stock_movements` | `unit_cost`                                                       |

### Decision

Store **all monetary values** as `BIGINT UNSIGNED` representing the smallest currency unit (piasters). Wrap amounts in an immutable `Money` value object (`app/ValueObjects/Money.php`).

```php
// Storage: integer in database
$table->unsignedBigInteger('total_amount')->default(0);

// Usage: Money value object in code
$total = Money::of(150000, 'SYP');  // 150,000 piasters
$display = $total->format();         // "150,000 ل.س"

// Arithmetic: deterministic integer math
$subtotal  = Money::of(100000);
$tax       = $subtotal->multiply(0.15);  // 15,000
$total     = $subtotal->add($tax);       // 115,000
```

### Consequences

| Type      | Consequence                                                          |
|-----------|----------------------------------------------------------------------|
| ✅ Positive | Zero floating-point precision errors — deterministic calculations   |
| ✅ Positive | Integer arithmetic is fastest on all platforms                      |
| ✅ Positive | Money value object provides clean, chainable API                    |
| ✅ Positive | Industry standard — Stripe, Shopify, Square all use integer units   |
| ✅ Positive | Database indexes work optimally on integer columns                  |
| ⚠️ Negative | Requires `money_format()` at display layer (Blade, PDFs)            |
| ⚠️ Negative | Form inputs must parse human amounts → integer (`parseMoney()`)     |
| ⚠️ Negative | All team members must understand the convention                     |

### Alternatives Considered

| Alternative      | Verdict    | Reason for Rejection                                          |
|------------------|------------|---------------------------------------------------------------|
| `DECIMAL(15,2)`  | Rejected   | Division and percentage operations still cause precision loss |
| `FLOAT`/`DOUBLE` | Rejected   | IEEE 754 arithmetic — unacceptable for financial data         |
| External package | Rejected   | Over-engineering — custom value object is simpler and lighter |

---

## ADR-002: Service Layer Architecture

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Application Architecture
- **Impact:** Every module in the system

### Context

The system contains complex, multi-step business operations — order creation requires credit checks, stock verification, price calculation, invoice generation, and event dispatching. Without a dedicated service layer, this logic accumulates in controllers ("fat controllers"), making it:
- Untestable without HTTP layer
- Non-reusable across CLI commands or queued jobs
- Impossible to maintain as complexity grows

### Decision

Enforce a **3-tier architecture**: Controller → Service → Repository

```
┌────────────────────────────────────────────────────────┐
│  Controller (HTTP Layer)                                │
│  ├── Authorize the request                             │
│  ├── Build DTO from validated input                    │
│  ├── Call ONE service method                           │
│  └── Return response (redirect / view / JSON)          │
├────────────────────────────────────────────────────────┤
│  Service (Business Logic Layer)                         │
│  ├── Wrap in DB::transaction()                         │
│  ├── Execute business rules                            │
│  ├── Call repositories for data access                 │
│  ├── Fire domain events                                │
│  └── Return Model / Collection / DTO                   │
├────────────────────────────────────────────────────────┤
│  Repository (Data Access Layer)                         │
│  ├── Execute Eloquent queries                          │
│  ├── Handle pagination, filtering, sorting             │
│  └── Return Models / Collections / Paginators          │
└────────────────────────────────────────────────────────┘
```

**Service directory structure:**
```
app/Services/
├── BaseService.php              ← Abstract: transaction(), money(), paginate()
├── Products/
│   ├── ProductService.php       ← CRUD operations
│   └── StockService.php         ← Stock movement operations
├── Customers/
│   └── CustomerService.php      ← Customer lifecycle + credit
├── Orders/
│   ├── OrderService.php         ← Create, update, delete, list
│   ├── OrderStatusService.php   ← All status transitions
│   └── OrderFinancialsService.php ← Totals, discounts, tax calc
├── Distribution/
│   └── ShipmentService.php      ← Shipment lifecycle
├── Invoices/
│   └── InvoiceService.php       ← Invoice + payment handling
├── Erp/
│   ├── ReportService.php        ← Report generation + export
│   └── ExpenseService.php       ← Expense CRUD
├── Auth/
│   └── AuthService.php          ← Login, password reset
├── PdfService.php               ← DomPDF Arabic generation
└── SettingService.php           ← System settings (cached)
```

### Consequences

| Type      | Consequence                                                          |
|-----------|----------------------------------------------------------------------|
| ✅ Positive | 100% testable business logic without HTTP layer (unit tests)        |
| ✅ Positive | Reusable — same service called from Controller, Command, or Job     |
| ✅ Positive | Single Responsibility per service class                             |
| ✅ Positive | Easy to mock in feature tests                                       |
| ⚠️ Negative | More files and classes (~14 service files)                           |
| ⚠️ Negative | Additional indirection layer                                        |

### Alternatives Considered

| Alternative              | Verdict    | Reason                                          |
|--------------------------|------------|--------------------------------------------------|
| Fat Controllers          | Rejected   | Untestable, violates SRP, unmaintainable         |
| Action Classes (single)  | Rejected   | Too granular — 50+ action classes vs 14 services |
| Model-heavy (Active Record) | Rejected | Models become bloated, hard to test              |

---

## ADR-003: DOCS Directory as Source of Truth

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Project Management

### Context

The `DOCS/` directory contains 5 comprehensive prompt files totaling ~548 KB. These files specify **every aspect** of the system with zero ambiguity — from database schemas to PHP implementations to Blade templates to test cases.

### Decision

Use `DOCS/AGENT_PROMPT_FACTORY_SYSTEM*.md` Parts 1–5 as the **single source of truth** for all requirements. `TASKS.md` serves as an index pointing to these files.

| Part | File                                        | Spec Scope                                       |
|------|---------------------------------------------|--------------------------------------------------|
| 1    | `AGENT_PROMPT_FACTORY_SYSTEM.md`            | Architecture, directory, phases, config, bootstrap |
| 2    | `AGENT_PROMPT_FACTORY_SYSTEM_PART2.md`      | DTOs, Repositories, Services, Controllers, Views  |
| 3    | `AGENT_PROMPT_FACTORY_SYSTEM_PART3.md`      | Models, Traits, Observers, PDF, Reports, Deploy    |
| 4    | `AGENT_PROMPT_FACTORY_SYSTEM_PART4.md`      | Policies, Middleware, Auth, Livewire, Export       |
| 5    | `AGENT_PROMPT_FACTORY_SYSTEM_PART5.md`      | Remaining Models, Repos, Services, Tests, Seeders |

### Consequences

| Type      | Consequence                                                          |
|-----------|----------------------------------------------------------------------|
| ✅ Positive | Zero inference required — every class has a concrete specification  |
| ✅ Positive | Development can begin immediately                                   |
| ⚠️ Negative | Agent must cross-reference multiple files per module                 |

---

## ADR-004: Repository Pattern for Data Access

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Data Architecture

### Context

Services need to query and persist data. Direct Eloquent usage in services creates tight coupling to the ORM, making unit testing impossible without a database.

### Decision

Every model that services interact with gets an interface + concrete repository:

```
app/Contracts/Repositories/
├── ProductRepositoryInterface.php
├── CustomerRepositoryInterface.php
├── OrderRepositoryInterface.php
├── InvoiceRepositoryInterface.php
├── ShipmentRepositoryInterface.php
└── StockMovementRepositoryInterface.php

app/Repositories/
├── BaseRepository.php          ← Generic findById(), create(), update(), delete()
├── ProductRepository.php
├── CustomerRepository.php
├── OrderRepository.php
├── InvoiceRepository.php
├── ShipmentRepository.php
└── StockMovementRepository.php
```

**DI Registration in `AppServiceProvider`:**
```php
$this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
```

### Consequences

| Type      | Consequence                                                          |
|-----------|----------------------------------------------------------------------|
| ✅ Positive | Services are unit-testable with mocked repositories                 |
| ✅ Positive | Query logic centralized — no scattered `Model::where()` calls       |
| ✅ Positive | Swappable (e.g., cache-wrapped decorator around repository)         |
| ⚠️ Negative | 12 extra files (6 interfaces + 6 implementations)                   |

---

## ADR-005: State Machine for Lifecycle Status Flows

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Domain Logic

### Context

Orders transition through 8 statuses, shipments through 5 statuses. Each transition has business rules — stock deduction on acceptance, invoice generation on delivery, stock return on cancellation.

### Decision

Dedicated State Machine classes with transition maps:

**Order Status Flow:**
```
pending ──→ accepted ──→ preparing ──→ ready ──→ shipped ──→ delivered
  │             │                        │                       
  └─→ cancelled ←────────────────────────┘
                                                    shipped ──→ returned
```

**Shipment Status Flow:**
```
planned ──→ loading ──→ dispatched ──→ completed
  │
  └──→ cancelled
```

**API:**
```php
$machine->canTransition('pending', 'accepted');      // true
$machine->canTransition('pending', 'delivered');      // false
$machine->transition('pending', 'accepted');          // void (or throws)
$machine->allowedTransitions('ready');                // ['shipped', 'cancelled']
$machine->isFinal('delivered');                       // true
```

### Consequences

| Type      | Consequence                                                          |
|-----------|----------------------------------------------------------------------|
| ✅ Positive | Single source of truth for all transition rules                     |
| ✅ Positive | Impossible to make illegal transitions                              |
| ✅ Positive | 100% unit testable (every edge covered)                             |
| ⚠️ Negative | Requires discipline — never do `$order->update(['status' => ...])` directly |

---

## ADR-006: DTO Pattern at Service Boundaries

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Data Integrity

### Context

Passing `$request->validated()` arrays to services is brittle — typos fail silently, no IDE support, no type checking.

### Decision

Immutable DTOs with `readonly` properties at all service entry points:

```php
final class CreateOrderDTO {
    public function __construct(
        public readonly int        $customerId,
        public readonly Carbon     $orderDate,
        public readonly Collection $items,        // Collection<OrderItemDTO>
        public readonly ?Carbon    $requestedDeliveryDate = null,
        public readonly ?string    $notes = null,
        public readonly int        $createdBy = 0,
    ) {}

    public static function fromArray(array $data): self { /* ... */ }
}
```

**DTO inventory:**
```
app/DTOs/
├── Orders/CreateOrderDTO.php         ← Order creation
├── Orders/OrderItemDTO.php           ← Order line items
├── Customers/CreateCustomerDTO.php   ← Customer creation
├── Invoices/RecordPaymentDTO.php     ← Payment recording
└── Shipments/CreateShipmentDTO.php   ← Shipment planning
```

### Consequences

| Type      | Consequence                                                          |
|-----------|----------------------------------------------------------------------|
| ✅ Positive | Full compile-time type safety                                       |
| ✅ Positive | IDE autocompletion and refactoring                                  |
| ✅ Positive | Self-documenting service interfaces                                 |
| ✅ Positive | Immutability prevents accidental data corruption                    |
| ⚠️ Negative | 5+ extra DTO classes                                                |

---

## ADR-007: Pipeline Pattern for Order Validation

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Domain Logic

### Context

Order creation requires 3 independent validation steps before persistence. These should be composable, testable in isolation, and easy to extend.

### Decision

Use Laravel's Pipeline with 3 discrete pipes:

```
CreateOrderDTO
    ↓
┌─ ValidateCustomerCreditPipe ─┐
│  Check: credit_limit >= outstanding + order_total   │
│  Throws: CreditLimitExceededException               │
└──────────────────────────────────────────────────────┘
    ↓
┌─ ValidateStockAvailabilityPipe ─┐
│  Check: product.stock >= item.quantity (per item)    │
│  Throws: InsufficientStockException                  │
└──────────────────────────────────────────────────────┘
    ↓
┌─ CalculateOrderTotalsPipe ──────┐
│  Snapshot current prices into DTO                    │
│  Calculate subtotal, discount, tax, total            │
└──────────────────────────────────────────────────────┘
    ↓
Persist to database
```

### Consequences

| Type      | Consequence                                                          |
|-----------|----------------------------------------------------------------------|
| ✅ Positive | Each pipe is a single testable class                                |
| ✅ Positive | Easy to add new validation steps (e.g., minimum order value)        |
| ⚠️ Negative | Pipeline error propagation must be carefully handled                |

---

## ADR-008: 400-Line File Size Hard Limit

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Code Quality

### Decision

**Hard limit: 400 lines per file. Begin splitting at 350 lines.**

| File Type      | Split Strategy                                                        | Example                                           |
|----------------|-----------------------------------------------------------------------|----------------------------------------------------|
| Large Service  | Split by sub-concern                                                  | `OrderService` → `OrderStatusService` + `OrderFinancialsService` |
| Large Controller | Separate by action group                                           | `OrderController` → `OrderStatusController` + `OrderReturnController` |
| Large View     | Extract Blade partials                                                | `show.blade.php` → `partials/order-header`, `order-items`, `order-timeline` |
| Large Model    | Extract traits                                                        | `HasStatusTransitions`, `HasMoneyFormatting`, `GeneratesSequentialCode` |
| Large Migration| This should never happen (migrations should be single-table)          | —                                                  |

---

## ADR-009: Arabic-First Localization

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Internationalization

### Decision

All user-facing text in `lang/ar/*.php`, accessed via `__()` or `@lang()`.

```
lang/ar/
├── auth.php           ← Login, password, throttle messages
├── validation.php     ← Field validation messages
├── pagination.php     ← "عرض X من Y"
├── app.php            ← Global application labels
├── orders.php         ← Order-specific messages
├── invoices.php       ← Invoice-specific messages
└── notifications.php  ← Notification templates
```

**Rule:** Zero Arabic characters in `app/` PHP files. Verified via: `grep -rP '[\x{0600}-\x{06FF}]' app/ --include="*.php"`

---

## ADR-010: Redis as Unified Infrastructure Driver

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Infrastructure

### Decision

Redis serves 3 infrastructure roles:

| Role              | Config Key                | TTL / Details                           |
|-------------------|---------------------------|-----------------------------------------|
| **Cache Store**   | `CACHE_STORE=redis`       | KPIs: 5 min, Settings: 60 min          |
| **Session Store** | `SESSION_DRIVER=redis`    | 120 min lifetime                        |
| **Queue Driver**  | `QUEUE_CONNECTION=redis`  | Managed by Supervisor, 3 retries        |

---

## ADR-011: Observer Pattern for Audit Logging

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Cross-Cutting Concerns

### Decision

All audit-critical model events are logged via Observers using Spatie ActivityLog:

| Observer            | Events Logged                                              | Audit Category |
|---------------------|------------------------------------------------------------|--------------  |
| `OrderObserver`     | Created, status changes, total changes, deletion           | `orders`       |
| `ProductObserver`   | Created, price changes, stock changes, deletion            | `products`     |
| `InvoiceObserver`   | Created, issued, voided, status changes                    | `invoices`     |
| `PaymentObserver`   | Created (every payment is a critical record), deletion     | `payments`     |

---

## ADR-012: Event-Driven Cross-Domain Coordination

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Application Architecture

### Decision

Domain events coordinate cross-module side effects:

| Event               | Listeners                                              | Side Effects                   |
|---------------------|--------------------------------------------------------|--------------------------------|
| `OrderAccepted`     | `DeductStockOnOrderAccepted`                           | Stock quantity decremented     |
|                     | `CreateInvoiceOnOrderAccepted`                         | Draft invoice created          |
|                     | `NotifyCustomerOnOrderStatusChange`                    | SMS/email notification sent    |
| `OrderCancelled`    | `ReturnStockOnOrderCancelled`                          | Stock quantity restored        |
|                     | `NotifyCustomerOnOrderStatusChange`                    | Cancellation notification      |
| `OrderDelivered`    | Invoice issued, customer balance updated               | Financial records finalized    |
| `InvoiceIssued`     | `UpdateCustomerBalanceOnInvoiceIssued`                  | `outstanding_balance` updated  |
| `PaymentReceived`   | `NotifyCustomerOnPaymentReceived`                      | Payment confirmation sent      |
| `LowStockDetected`  | `SendLowStockAlert`                                    | Admin notified                 |

---

## ADR-013: Facade Pattern for System Settings

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Application Architecture

### Decision

`Setting` facade wraps `SettingService` with Redis-cached key-value access:

```php
Setting::get('factory_name');           // "اسم المعمل"
Setting::get('invoice_due_days', 30);   // 30 (with default)
Setting::set('tax_rate', 0.15);         // Update + cache invalidation
Setting::all();                         // Full cached settings map
```

---

## ADR-014: Policy-Based Authorization

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Security

### Decision

7 Policy classes + `super_admin` bypass via `Gate::before()`:

| Policy             | Model       | Key Business Rules                                        |
|--------------------|-------------|-----------------------------------------------------------|
| `OrderPolicy`      | `Order`     | Customers see only their own orders                       |
| `InvoicePolicy`    | `Invoice`   | Customers see only their own invoices                     |
| `PaymentPolicy`    | `Payment`   | Only accountant/admin can create/delete                   |
| `ProductPolicy`    | `Product`   | Cost price visibility restricted                          |
| `CustomerPolicy`   | `Customer`  | Credit management requires special permission             |
| `ShipmentPolicy`   | `Shipment`  | Dispatch requires ready orders                            |
| `ExpensePolicy`    | `Expense`   | Only current-month expenses editable                      |

**RBAC Role Matrix:**

| Permission                    | super_admin | admin | accountant | warehouse | sales | driver | customer |
|-------------------------------|:-----------:|:-----:|:----------:|:---------:|:-----:|:------:|:--------:|
| `orders.view`                 | ✅          | ✅    | ✅         | ✅        | ✅    | ✅     | 🔒 own   |
| `orders.create`               | ✅          | ✅    | —          | —         | ✅    | —      | —        |
| `orders.edit`                 | ✅          | ✅    | —          | —         | ✅    | —      | —        |
| `orders.cancel`               | ✅          | ✅    | —          | —         | —     | —      | —        |
| `orders.confirm_delivery`     | ✅          | ✅    | —          | —         | —     | ✅     | —        |
| `invoices.view`               | ✅          | ✅    | ✅         | —         | ✅    | —      | 🔒 own   |
| `invoices.void`               | ✅          | —     | ✅         | —         | —     | —      | —        |
| `payments.create`             | ✅          | —     | ✅         | —         | —     | —      | —        |
| `payments.delete`             | ✅          | —     | ✅         | —         | —     | —      | —        |
| `products.view`               | ✅          | ✅    | ✅         | ✅        | ✅    | —      | —        |
| `products.adjust_stock`       | ✅          | ✅    | —          | ✅        | —     | —      | —        |
| `products.view_cost_price`    | ✅          | ✅    | ✅         | —         | —     | —      | —        |
| `customers.manage_credit`     | ✅          | ✅    | ✅         | —         | —     | —      | —        |
| `shipments.dispatch`          | ✅          | ✅    | —          | ✅        | —     | —      | —        |
| `erp.expenses.create`         | ✅          | —     | ✅         | —         | —     | —      | —        |
| `admin.users`                 | ✅          | —     | —          | —         | —     | —      | —        |
| `admin.settings`              | ✅          | —     | —          | —         | —     | —      | —        |
| `admin.audit_log`             | ✅          | ✅    | —          | —         | —     | —      | —        |

---

## ADR-015: Docker-Based Production Deployment

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** DevOps

### Decision

Production deployment via Docker Compose with 4 containers:

```
docker-compose.yml
├── app        → PHP-FPM 8.3 + Laravel
├── nginx      → Reverse proxy + SSL termination
├── mysql      → MySQL 8.0 persistent volume
├── redis      → Cache + Session + Queue
└── supervisor → Queue worker management
```

---

## ADR-016: Local Driver Fallback Until Redis Available

- **Status:** ✅ Accepted
- **Date:** 2026-05-16
- **Author:** FACTORY-AGENT
- **Category:** Environment Bootstrap
- **Supersedes:** None. This is a local-only exception to ADR-010.

### Context

Phase 00 requires the Laravel application to serve locally with no HTTP errors. The current Windows PHP runtime does not have the Redis extension installed, and no Redis service is running locally. Keeping `SESSION_DRIVER=redis` and `CACHE_STORE=redis` caused HTTP 500 before any application code executed.

### Decision

Use local `.env` fallback drivers until Redis is available:

```dotenv
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Production and Docker environments still target Redis for cache, sessions, and queues.

### Consequences

| Type | Consequence |
|------|-------------|
| ✅ Positive | `php artisan serve` works locally during bootstrap |
| ✅ Positive | No machine-level PHP extension changes are required |
| ⚠️ Negative | Local `.env` differs from production target until Docker/Redis is configured |

---

*Architecture decisions are immutable. Create a new ADR to supersede an existing one.*
