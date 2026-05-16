<!-- ╔══════════════════════════════════════════════════════════════════════════╗ -->
<!-- ║               SKILLS.md — DESIGN PATTERNS & TECHNIQUES CATALOG          ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════════╝ -->

# 🧩 Design Patterns & Techniques Catalog

> **Project:** Factory Distribution & Shipping Management System  
> **Purpose:** Comprehensive reference of every design pattern, SOLID principle, and architectural technique applied in this codebase — with rationale, file locations, code examples, and anti-patterns to avoid.

---

## 📑 Pattern Index

| #   | Pattern                    | GoF Category   | SOLID Principle      | Location                             | Applied In                              |
|-----|----------------------------|----------------|----------------------|--------------------------------------|-----------------------------------------|
| 01  | Service Layer              | —              | SRP, DIP             | `app/Services/`                      | All 10 modules                          |
| 02  | Repository                 | —              | DIP, ISP             | `app/Repositories/`                  | All data access                         |
| 03  | Observer                   | Behavioral     | OCP                  | `app/Observers/`                     | Orders, Products, Invoices, Payments    |
| 04  | State Machine              | Behavioral     | SRP                  | `app/StateMachines/`                 | Orders (8 statuses), Shipments (5)      |
| 05  | Strategy                   | Behavioral     | OCP, LSP             | `app/Contracts/Export/`              | Report export (Excel, CSV, PDF)         |
| 06  | Factory                    | Creational     | SRP                  | `app/Factories/`                     | Sequential code generation              |
| 07  | Template Method            | Behavioral     | DIP                  | `app/Services/BaseService.php`       | All 14 service classes                  |
| 08  | Facade                     | Structural     | —                    | `app/Facades/Setting.php`            | System settings access                  |
| 09  | DTO (Data Transfer Object) | —              | SRP                  | `app/DTOs/`                          | 5 DTOs across 4 modules                 |
| 10  | Pipeline                   | Behavioral     | SRP, OCP             | `app/Pipelines/Order/`               | Order creation validation               |
| 11  | Event/Listener             | Behavioral     | OCP, SRP             | `app/Events/`, `app/Listeners/`      | 7 domain events, 8+ listeners           |
| 12  | Value Object               | DDD            | —                    | `app/ValueObjects/Money.php`         | All financial operations                |
| 13  | Decorator                  | Structural     | OCP                  | `app/Models/Traits/`                 | Money formatting on 6+ models           |
| 14  | Chain of Responsibility    | Behavioral     | SRP, OCP             | `app/Pipelines/Order/`               | 3 order validation pipes                |
| 15  | Command                    | Behavioral     | SRP                  | `app/Console/Commands/`              | 3 scheduled jobs                        |

---

## SOLID Principles — How They Map to This Codebase

### S — Single Responsibility Principle

> *"A class should have one, and only one, reason to change."*

| ✅ Correct Implementation                                         | ❌ Anti-Pattern                                                  |
|--------------------------------------------------------------------|------------------------------------------------------------------|
| `ProductService` — only manages product CRUD                      | `ProductController` that also manages stock + categories         |
| `StockService` — only manages stock movements                     | `OrderService` that also generates PDFs and sends emails         |
| `OrderStatusService` — only manages status transitions            | A single `OrderService` with 600+ lines doing everything         |
| `OrderFinancialsService` — only calculates totals/discounts       | Models with business logic methods mixed with relationships      |

### O — Open/Closed Principle

> *"Software entities should be open for extension, closed for modification."*

| ✅ Implementation                                                 | How It Works                                                      |
|-------------------------------------------------------------------|-------------------------------------------------------------------|
| `ExportStrategyInterface` + strategies                            | Add `JsonExportStrategy` without modifying `ReportService`        |
| Pipeline validation pipes                                          | Add `MinimumOrderValuePipe` without modifying `OrderService`      |
| Observers for audit logging                                        | Add new side effects without modifying the Model                  |

### L — Liskov Substitution Principle

> *"Subtypes must be substitutable for their base types."*

| ✅ Implementation                                                 | Verification                                                      |
|-------------------------------------------------------------------|-------------------------------------------------------------------|
| All `ExportStrategy` implementations                              | `ExcelExportStrategy` and `CsvExportStrategy` are interchangeable |
| All `Repository` implementations                                  | Any repo implementing the interface can replace another           |
| `InvoicePdfGenerator` and `ManifestPdfGenerator`                  | Both implement `PdfGeneratorInterface`                            |

### I — Interface Segregation Principle

> *"Clients should not be forced to depend on interfaces they do not use."*

| ✅ Correct Segregation                          | ❌ Anti-Pattern                              |
|--------------------------------------------------|----------------------------------------------|
| `ExportableInterface` — just `export()`          | One `ModelInterface` with 20 methods         |
| `PrintableInterface` — just `generatePdf()`      | Forcing all models to implement audit methods|
| `SearchableInterface` — just `search($term)`     | One bloated "god interface"                  |

### D — Dependency Inversion Principle

> *"Depend on abstractions, not concretions."*

```php
// ✅ CORRECT — Controller depends on interface
class OrderController extends Controller {
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {}
}

// ✅ CORRECT — Service depends on repository interface
class OrderService extends BaseService {
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}
}

// ❌ WRONG — Direct dependency on concrete class
class OrderService extends BaseService {
    public function __construct(
        private readonly OrderRepository $orders,  // ← concrete, not interface
    ) {}
}
```

---

## Pattern 01 — Service Layer

> **Category:** Application Architecture · **SOLID:** S, D

### What

Every controller delegates **all** business logic to a dedicated Service class. Controllers handle HTTP concerns only: authorize, validate, call service, redirect.

### File Structure

```
app/Services/
├── BaseService.php                     ← Abstract: transaction(), money(), paginate()
│
├── Products/
│   ├── ProductService.php              ← create(), update(), delete(), list(), restore()
│   └── StockService.php                ← moveStock(), adjustStock(), getLowStock()
│
├── Customers/
│   └── CustomerService.php             ← create(), update(), delete(), recalculateBalance()
│
├── Orders/
│   ├── OrderService.php                ← create(), update(), delete(), list()
│   ├── OrderStatusService.php          ← accept(), markPreparing(), markReady(), confirmDelivery(), cancel(), recordReturn()
│   └── OrderFinancialsService.php      ← calculateTotals(), calculateTax(), applyDiscount()
│
├── Distribution/
│   └── ShipmentService.php             ← create(), dispatch(), assignOrders(), complete()
│
├── Invoices/
│   └── InvoiceService.php              ← createFromOrder(), issue(), recordPayment(), void()
│
├── Erp/
│   ├── ReportService.php               ← salesReport(), receivablesReport(), profitLoss()
│   └── ExpenseService.php              ← create(), update(), delete(), monthlyTotal()
│
├── Auth/
│   └── AuthService.php                 ← login(), logout(), resetPassword()
│
├── PdfService.php                      ← generateInvoice(), generateManifest(), generateStatement()
└── SettingService.php                  ← get(), set(), all(), getByGroup()
```

### Correct vs Anti-Pattern

```php
// ✅ CORRECT — Thin controller, delegates to service
public function store(StoreOrderRequest $request): RedirectResponse
{
    $this->authorize('create', Order::class);
    $dto   = CreateOrderDTO::fromArray($request->validated());
    $order = $this->orderService->create($dto);
    return redirect()->route('orders.show', $order)
        ->with('success', __('orders.created_successfully'));
}

// ❌ WRONG — Fat controller with business logic
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([...]); // Should use FormRequest
    $customer = Customer::find($validated['customer_id']); // Direct Eloquent
    if ($customer->outstanding_balance > $customer->credit_limit) { // Business logic
        throw new Exception('Credit exceeded'); // Hardcoded English
    }
    $order = Order::create($validated); // No transaction
    // ... 50 more lines of logic
}
```

---

## Pattern 02 — Repository

> **Category:** Data Access · **SOLID:** D, I

### What

Abstracts all Eloquent operations behind interface contracts. Services inject repository interfaces, never concrete classes or models directly.

### Interface Example

```php
interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;
    public function findByIdOrFail(int $id): Order;
    public function findByNumber(string $number): ?Order;
    public function paginateWithFilters(array $filters, int $perPage = 20): LengthAwarePaginator;
    public function getForDate(Carbon $date): Collection;
    public function getPendingForCustomer(int $customerId): Collection;
    public function getReadyOrders(): Collection;
    public function create(array $data): Order;
    public function update(Order $order, array $data): Order;
    public function delete(Order $order): void;
}
```

### File Inventory

```
Contracts/Repositories/              Repositories/
├── ProductRepositoryInterface       ├── BaseRepository.php
├── CustomerRepositoryInterface      ├── ProductRepository.php
├── OrderRepositoryInterface         ├── CustomerRepository.php
├── InvoiceRepositoryInterface       ├── OrderRepository.php
├── ShipmentRepositoryInterface      ├── InvoiceRepository.php
└── StockMovementRepositoryInterface ├── ShipmentRepository.php
                                     └── StockMovementRepository.php
```

---

## Pattern 03 — Observer

> **Category:** Behavioral · **SOLID:** O

### What

Model lifecycle events (created, updated, deleted) trigger audit logging and lightweight side effects via Observers, without modifying the Model class.

### Observer Registry

| Observer            | Model       | `created`                      | `updated`                           | `deleted`            |
|---------------------|-------------|--------------------------------|-------------------------------------|----------------------|
| `OrderObserver`     | `Order`     | Log order + customer + total   | Log status change, total changes    | Log deletion         |
| `ProductObserver`   | `Product`   | Log new product                | Log price changes, stock changes    | Log deletion         |
| `InvoiceObserver`   | `Invoice`   | Log invoice creation           | Log status change, payment updates  | —                    |
| `PaymentObserver`   | `Payment`   | Log payment amount + method    | —                                   | Log deletion         |

### Registration

```php
// app/Providers/EventServiceProvider.php
protected $observers = [
    Order::class   => [OrderObserver::class],
    Product::class => [ProductObserver::class],
    Invoice::class => [InvoiceObserver::class],
    Payment::class => [PaymentObserver::class],
];
```

---

## Pattern 04 — State Machine

> **Category:** Behavioral · **SOLID:** SRP

### Order Status Transitions

```
                    ┌─────────────────────────────────────────────────┐
                    │              ORDER LIFECYCLE                     │
                    │                                                  │
                    │  pending ───→ accepted ───→ preparing            │
                    │    │                            │                 │
                    │    │                            ↓                 │
                    │    │                         ready                │
                    │    │                            │                 │
                    │    │                            ↓                 │
                    │    │                        shipped               │
                    │    │                            │                 │
                    │    ↓                            ↓                 │
                    │  cancelled ←──────────     delivered              │
                    │                                 │                 │
                    │                                 ↓                 │
                    │                             returned              │
                    │                                                  │
                    │  FINAL STATES: delivered, cancelled, returned     │
                    └─────────────────────────────────────────────────┘
```

### Shipment Status Transitions

```
planned ──→ loading ──→ dispatched ──→ completed
   │
   └──→ cancelled

FINAL STATES: completed, cancelled
```

---

## Pattern 05 — Strategy

> **Category:** Behavioral · **SOLID:** O, L

```php
interface ExportStrategyInterface {
    public function export(Collection $data, string $filename): mixed;
    public function contentType(): string;
}
```

| Implementation          | Output Format | Content Type                                   |
|-------------------------|---------------|------------------------------------------------|
| `ExcelExportStrategy`   | .xlsx         | `application/vnd.openxmlformats-officedocument` |
| `CsvExportStrategy`     | .csv          | `text/csv`                                     |
| `PdfExportStrategy`     | .pdf          | `application/pdf`                              |

---

## Pattern 06 — Factory (Code Generation)

> **Category:** Creational

```
app/Factories/CodeGeneratorFactory.php
```

| Entity   | Pattern             | Example            | Auto-increment scope |
|----------|---------------------|--------------------|----------------------|
| Order    | `ORD-YYYY-NNNNN`   | `ORD-2026-00001`   | Per year             |
| Invoice  | `INV-YYYY-NNNNN`   | `INV-2026-00042`   | Per year             |
| Shipment | `SHP-YYYY-NNNNN`   | `SHP-2026-00015`   | Per year             |
| Payment  | `PAY-YYYY-NNNNN`   | `PAY-2026-00001`   | Per year             |
| Customer | `CUS-NNNN`         | `CUS-0128`         | Global               |

---

## Pattern 07 — Template Method

> **Category:** Behavioral · `app/Services/BaseService.php`

All 14 services extend `BaseService` and inherit:

| Method                              | Purpose                                         |
|-------------------------------------|-------------------------------------------------|
| `transaction(Closure $cb)`          | Wraps callback in `DB::transaction()`           |
| `paginate(Builder $q, int $pp)`     | Standardized pagination with query string       |
| `money(int $amount)`                | Creates `Money` value object                    |
| `parseMoney(string\|int $input)`    | Human input → integer (e.g., "1,500" → 1500)   |
| `formatMoney(int $amount)`          | Integer → display string (e.g., "1,500 ل.س")   |

---

## Pattern 08 — Facade (Settings)

> **Category:** Structural · `app/Facades/Setting.php`

```php
Setting::get('factory_name');                    // → "اسم المعمل"
Setting::get('invoice_due_days', 30);            // → 30 (with default)
Setting::set('invoice_tax_rate', 0.15);          // → update + cache bust
Setting::all();                                  // → full cached map
Setting::getByGroup('invoice');                  // → group-specific settings
```

**Backed by:** `app/Services/SettingService.php` with Redis caching (60-min TTL)  
**Data source:** `system_settings` table (key/value with type/group/label)

---

## Pattern 09 — DTO (Data Transfer Object)

> **Category:** Structural · `app/DTOs/`

| DTO                      | Properties                                                              | Used By                |
|--------------------------|-------------------------------------------------------------------------|------------------------|
| `CreateOrderDTO`         | `customerId`, `orderDate`, `items`, `requestedDeliveryDate`, `notes`    | `OrderService::create` |
| `OrderItemDTO`           | `productId`, `quantity`, `unitPrice`, `discountPercent`, `notes`        | Nested in CreateOrder  |
| `CreateCustomerDTO`      | `name`, `phone`, `address`, `category`, `creditLimit`, `portalAccess`   | `CustomerService`      |
| `RecordPaymentDTO`       | `invoiceId`, `amount`, `paymentMethod`, `paymentDate`, `referenceNumber`| `InvoiceService`       |
| `CreateShipmentDTO`      | `truckId`, `driverId`, `shipmentDate`, `notes`                         | `ShipmentService`      |

All DTO properties are `readonly`. Each provides `fromArray(array $data): self`.

---

## Pattern 10 — Pipeline

> **Category:** Behavioral · `app/Pipelines/Order/`

```
CreateOrderDTO ──→ ValidateCustomerCreditPipe
                       │ Checks: credit_limit >= outstanding + order_total
                       │ Throws: CreditLimitExceededException
                       ↓
                   ValidateStockAvailabilityPipe
                       │ Checks: stock_quantity >= order_quantity (per item)
                       │ Throws: InsufficientStockException
                       ↓
                   CalculateOrderTotalsPipe
                       │ Snapshots: current product prices into DTO
                       │ Computes: subtotal, discount, tax, total
                       ↓
                   OrderService::create() → persist to DB
```

---

## Pattern 11 — Event/Listener

> **Category:** Behavioral · `app/Events/` + `app/Listeners/`

### Complete Event Map

| Domain     | Event                | Listeners                                          | Queue? |
|------------|----------------------|----------------------------------------------------|--------|
| **Orders** | `OrderCreated`       | Activity log (via Observer)                        | No     |
|            | `OrderAccepted`      | `DeductStockOnOrderAccepted`                       | No     |
|            |                      | `CreateInvoiceOnOrderAccepted`                     | No     |
|            |                      | `NotifyCustomerOnOrderStatusChange`                | Yes    |
|            | `OrderCancelled`     | `ReturnStockOnOrderCancelled`                      | No     |
|            |                      | `NotifyCustomerOnOrderStatusChange`                | Yes    |
|            | `OrderShipped`       | `NotifyCustomerOnOrderStatusChange`                | Yes    |
|            | `OrderDelivered`     | Invoice issued, balance updated                    | No     |
| **Invoice**| `InvoiceIssued`      | `UpdateCustomerBalanceOnInvoiceIssued`              | No     |
|            |                      | `NotifyCustomerOnPaymentReceived`                  | Yes    |
|            | `PaymentReceived`    | `NotifyCustomerOnPaymentReceived`                  | Yes    |
| **Stock**  | `LowStockDetected`   | `SendLowStockAlert`                                | Yes    |

---

## Pattern 12 — Value Object (Money)

> **Category:** Domain-Driven Design · `app/ValueObjects/Money.php`

```php
$price    = Money::of(50000, 'SYP');           // 50,000 piasters
$qty      = 3;
$subtotal = $price->multiply($qty);             // 150,000
$discount = Money::of(15000);                   // 15,000
$total    = $subtotal->subtract($discount);     // 135,000
$display  = $total->format();                   // "135,000 ل.س"

// Properties
$total->amount();     // 135000 (int)
$total->currency();   // "SYP"
$total->isZero();     // false
$total->isGreaterThan(Money::of(100000)); // true
```

**Rules:**
- Immutable — all operations return new instances
- Currency-safe — throws on cross-currency arithmetic
- Integer-only — no floating-point contamination

---

## Patterns 13–15 — Additional

### 13 — Decorator (`HasMoneyFormatting` Trait)

Adds dynamic `formatted_*` accessors to models with monetary columns:

```php
// Model defines money columns
protected array $moneyColumns = ['unit_price', 'cost_price'];

// Blade usage
{{ $product->formatted_unit_price }}    →    "50,000 ل.س"
{{ $product->formatted_cost_price }}    →    "35,000 ل.س"
```

**Applied to:** Product, Customer, Order, OrderItem, Invoice, Expense (6 models)

### 14 — Chain of Responsibility (Validation Pipeline)

Each pipe either passes the DTO to the next pipe or throws a domain exception:

| Pipe                              | Pass Condition                           | Exception on Failure               |
|-----------------------------------|------------------------------------------|------------------------------------|
| `ValidateCustomerCreditPipe`      | Available credit ≥ order total           | `CreditLimitExceededException`     |
| `ValidateStockAvailabilityPipe`   | Stock ≥ quantity (all items)             | `InsufficientStockException`       |
| `CalculateOrderTotalsPipe`        | Always passes (calculates totals)        | —                                  |

### 15 — Command (Scheduled Jobs)

| Command                         | Schedule       | Purpose                                      |
|---------------------------------|----------------|----------------------------------------------|
| `SendOverdueInvoiceAlerts`      | Daily 9:00 AM  | Email overdue invoice reminders              |
| `CheckLowStockLevels`           | Every 6 hours  | Detect products below threshold              |
| `GenerateBackup`                | Daily 2:00 AM  | Database + media backup to storage           |

---

## 🏗️ Custom Exceptions Registry

| Exception Class                          | Thrown By                    | HTTP Code | User Message Key                    |
|------------------------------------------|------------------------------|-----------|-------------------------------------|
| `InvalidStatusTransitionException`       | State Machines               | 422       | `orders.invalid_status_transition`  |
| `InsufficientStockException`             | `ValidateStockAvailabilityPipe` | 422    | `orders.insufficient_stock`         |
| `CreditLimitExceededException`           | `ValidateCustomerCreditPipe` | 422       | `orders.credit_limit_exceeded`      |
| `InvoiceCannotBeVoidedException`         | `InvoiceService::void()`     | 422       | `invoices.cannot_void`              |

---

*This catalog is a living document. Update when new patterns are introduced or existing patterns evolve.*
