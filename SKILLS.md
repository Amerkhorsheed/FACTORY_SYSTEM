<!-- ╔══════════════════════════════════════════════════════════════════════════╗ -->
<!-- ║               SKILLS.md — DESIGN PATTERNS & TECHNIQUES CATALOG          ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════════╝ -->

# Design Patterns & Techniques Catalog

> **Project:** Factory Distribution & Shipping Management System  
> **Purpose:** Quick-reference of every architectural pattern applied in this codebase.

---

## Pattern Index

| # | Pattern | Category | SOLID | Location | Applied In |
|---|---------|----------|-------|----------|------------|
| 01 | Service Layer | Architecture | S, D | `app/Services/` | All modules |
| 02 | Repository | Data Access | D, I | `app/Repositories/` | All data access |
| 03 | Observer | Behavioral | O | `app/Observers/` | Orders, Products, Invoices, Payments |
| 04 | State Machine | Behavioral | S | `app/StateMachines/` | Orders, Shipments |
| 05 | Strategy | Behavioral | O, L | `app/Contracts/Export/` | Report exports |
| 06 | Factory | Creational | S | `app/Factories/` | Sequential code generation |
| 07 | Template Method | Behavioral | D | `app/Services/BaseService.php` | All services |
| 08 | Facade | Structural | — | `app/Facades/Setting.php` | System settings |
| 09 | DTO | Structural | S | `app/DTOs/` | Service boundaries |
| 10 | Pipeline | Behavioral | S, O | `app/Pipelines/Order/` | Order validation |
| 11 | Event/Listener | Behavioral | O, S | `app/Events/`, `app/Listeners/` | Cross-domain coordination |
| 12 | Value Object | DDD | — | `app/ValueObjects/Money.php` | Financial operations |
| 13 | Decorator | Structural | O | `app/Models/Traits/` | Money formatting |
| 14 | Chain of Responsibility | Behavioral | S, O | `app/Pipelines/Order/` | Validation pipes |
| 15 | Command | Behavioral | S | `app/Console/Commands/` | Scheduled jobs |

---

## SOLID Principles — Compact Reference

| Principle | Rule | How We Apply It |
|-----------|------|-----------------|
| **S**ingle Responsibility | One reason to change per class | `ProductService` handles products; `StockService` handles stock; `OrderStatusService` handles transitions only. |
| **O**pen/Closed | Open for extension, closed for modification | `ExportStrategyInterface` lets us add formats without touching `ReportService`. Pipeline pipes are added, not modified. |
| **L**iskov Substitution | Subtypes must be interchangeable | All repository and export implementations are swappable via interfaces. |
| **I**nterface Segregation | Clients depend only on methods they use | `ExportableInterface`, `PrintableInterface`, and `SearchableInterface` are minimal. |
| **D**ependency Inversion | Depend on abstractions | Controllers inject `OrderServiceInterface`; services inject `OrderRepositoryInterface`. |

---

## Pattern Summaries

**01 — Service Layer**  
Controllers authorize, validate, and call exactly one service method. Services own transactions, business rules, events, and repository calls. `BaseService` provides `transaction()`, `paginate()`, and money helpers.

**02 — Repository**  
All Eloquent operations live behind interface contracts. Services inject interfaces, never concrete models. Six repositories cover the core domain.

**03 — Observer**  
`OrderObserver`, `ProductObserver`, `InvoiceObserver`, and `PaymentObserver` log audit events via Spatie ActivityLog without modifying model classes.

**04 — State Machine**  
`OrderStateMachine` (8 statuses) and `ShipmentStateMachine` (5 statuses) define legal transitions. Illegal transitions throw `InvalidStatusTransitionException`.

**05 — Strategy**  
`ExportStrategyInterface` with `ExcelExportStrategy`, `CsvExportStrategy`, and `PdfExportStrategy` lets `ReportService` export datasets without format-specific logic.

**06 — Factory (Code Generation)**  
`CodeGeneratorFactory` produces sequential codes per entity: `ORD-YYYY-NNNNN`, `INV-YYYY-NNNNN`, `SHP-YYYY-NNNNN`, etc.

**07 — Template Method**  
All services extend `BaseService` and inherit `transaction()`, `paginate()`, `money()`, `parseMoney()`, and `formatMoney()`.

**08 — Facade (Settings)**  
`Setting` facade wraps `SettingService` with Redis-cached reads, defaults, and cache-busting writes. Data source is the `system_settings` table.

**09 — DTO**  
Immutable `readonly` DTOs (`CreateOrderDTO`, `OrderItemDTO`, `CreateCustomerDTO`, `RecordPaymentDTO`, `CreateShipmentDTO`) provide type-safe service boundaries and `fromArray()` constructors.

**10 — Pipeline**  
Order creation runs through `ValidateCustomerCreditPipe` → `ValidateStockAvailabilityPipe` → `CalculateOrderTotalsPipe` before persistence.

**11 — Event/Listener**  
Domain events decouple side effects: `OrderAccepted` deducts stock and creates invoices; `LowStockDetected` alerts staff; `PaymentReceived` confirms to customers.

**12 — Value Object (Money)**  
`Money` is immutable, currency-safe, and integer-only. All financial operations use it; cross-currency arithmetic throws.

**13 — Decorator**  
`HasMoneyFormatting` trait adds `formatted_*` accessors to models with monetary columns (Product, Customer, Order, OrderItem, Invoice, Expense).

**14 — Chain of Responsibility**  
Each pipeline pipe either passes the DTO forward or throws a domain exception (`CreditLimitExceededException`, `InsufficientStockException`).

**15 — Command (Scheduled Jobs)**  
`SendOverdueInvoiceAlerts` (daily 09:00), `CheckLowStockLevels` (daily 08:00), and `GenerateBackup` (daily 02:00) are registered in `routes/console.php`.

---

## Custom Exceptions Registry

| Exception | Thrown By | HTTP Code | Message Key |
|-----------|-----------|-----------|-------------|
| `InvalidStatusTransitionException` | State Machines | 422 | `orders.invalid_status_transition` |
| `InsufficientStockException` | Stock validation | 422 | `orders.insufficient_stock` |
| `CreditLimitExceededException` | Credit validation | 422 | `orders.credit_limit_exceeded` |
| `InvoiceCannotBeVoidedException` | `InvoiceService::void()` | 422 | `invoices.cannot_void` |

---

*Update this catalog when new patterns are introduced or existing patterns evolve.*
