# PROGRESS.md - Live Build Progress
## Session Log
| Session | Date | Tasks Completed | Tests Passing | Notes |
|---------|------|-----------------|---------------|-------|
| 001 | 2026-05-16 | ENV setup, Laravel install, package install, vendor publish, verification | 2/2 | Initial bootstrap; PHP 8.2.12 detected while target is 8.3 |
| 002 | 2026-05-16 | All 17 strict-order migrations created and verified | 2/2 | MySQL credentials unavailable locally; SQLite verification passed |
| 003 | 2026-05-16 | Money value object, transition exception, order and shipment state machines | 32/32 | Phase 02 complete |
| 004 | 2026-05-16 | Base service/repository infrastructure, contracts, provider bindings, and tests | 40/40 | Phase 03 complete; test DB now uses SQLite in-memory via phpunit.xml |
| 005 | 2026-05-16 | Model traits, code generator, 13 domain models, observers, translations, and tests | 46/46 | Phase 04 complete |
| 006 | 2026-05-16 | Seeders for roles, permissions, system settings, admin users, and product categories | 53/53 | Phase 05 complete; 47 permissions, 4 roles, 3 users, 16 settings seeded |
| 007 | 2026-05-16 | Auth controller, 4 middleware classes, route structure, middleware registration, auth tests | 65/65 | Phase 06 complete; middleware reordered so portal runs before role |
| 008 | 2026-05-16 | Inventory module: StockService, ProductService, repositories, controllers, policies, requests, events, exceptions, views, routes, tests | 78/78 | Phase 07 Module 01 complete; removed placeholder routes from web.php; fixed BaseRepository restore for newQueryWithoutScopes |
| 009 | 2026-05-16 | Customer module: DTO, repository, service, controller, policy, requests, factory, views, routes, translations, tests | 89/89 | Phase 07 Module 02 complete; removed placeholder customer routes; created ReportService stub; fixed CustomerRepository variance issue |
| 010 | 2026-05-16 | Orders module: DTOs, pipelines, services, repository, controllers, policy, status transitions, events, exceptions, stubs, factories, views, routes, translations, tests | 100/100 | Phase 07 Module 03 complete; added AuthServiceProvider; added orders.delete permission; fixed state machine for ready->delivered; fixed strict-mode lazy-loading via eager-load in OrderStatusService |

## Module Status
| Module | Status | % Done | Blockers |
|--------|--------|--------|---------|
| 00 Bootstrap | [x] | 100% | PHP runtime is 8.2.12, target is 8.3; Redis unavailable locally; npm audit has 2 moderate findings |
| 01 Database | [x] | 100% | MySQL user/database unavailable locally; SQLite migration verification passed |
| 02 Value Objects | [x] | 100% | - |
| 03 Base Classes | [x] | 100% | Concrete repositories/services are scheduled for later model/module phases |
| 04 Models & Observers | [x] | 100% | - |
| 05 Seeders & Roles | [x] | 100% | - |
| 01 Auth | [x] | 100% | - |
| 02 Inventory | [x] | 100% | - |
| 03 Customers | [x] | 100% | - |
| 04 Orders | [x] | 100% | - |
| 05 Distribution | [ ] | 0% | - |
| 06 Invoicing | [ ] | 0% | - |
| 07 ERP | [ ] | 0% | - |
| 08 Admin | [ ] | 0% | - |
| 09 Frontend | [ ] | 0% | - |
| 10 PDF | [ ] | 0% | - |
| 11 Notifications | [ ] | 0% | - |
| 12 Reports | [ ] | 0% | - |
| 13 Tests | [ ] | 0% | - |
| 14 Deploy | [ ] | 0% | - |

## Session 001 - Environment & Bootstrap
**Date:** 2026-05-16
**Phase:** 00 - Bootstrap

### Completed:
- [x] Laravel 11 project created in `factory-system/`
- [x] Composer production packages installed
- [x] Composer development packages installed
- [x] Frontend packages installed
- [x] Tailwind pinned to v3
- [x] Vendor files published
- [x] `php artisan key:generate` succeeded
- [x] `php artisan storage:link` succeeded
- [x] Management files created in Laravel project root
- [x] `php artisan test` passed: 2/2
- [x] `npm run build` passed
- [x] Cache commands passed: config, route, and view
- [x] `php artisan serve` smoke test returned 200 OK

### Notes:
- Composer caret constraints could not be passed reliably through this Windows shell, so equivalent tilde major ranges were used.
- `npm install` reports 2 moderate vulnerabilities. `npm audit fix --force` was not run because it may introduce breaking upgrades.

### Tests:
- `php artisan test` -> 2 passed

### Next Session Plan:
- Start Phase 01 migrations.

## Session 002 - Database & Migrations
**Date:** 2026-05-16
**Phase:** 01 - Database & Migrations

### Completed:
- [x] Removed default Laravel scaffold migrations
- [x] Consolidated Spatie Permission migrations into `015_create_permission_tables.php`
- [x] Consolidated Spatie ActivityLog migrations into `016_create_activity_log_table.php`
- [x] Created strict ordered migrations `001` through `017`
- [x] Added conditional MySQL fulltext indexes for products and customers
- [x] Verified all migrations with local SQLite

### Files Created (17):
- `database/migrations/001_create_users_table.php`
- `database/migrations/002_create_product_categories_table.php`
- `database/migrations/003_create_products_table.php`
- `database/migrations/004_create_customers_table.php`
- `database/migrations/005_create_trucks_table.php`
- `database/migrations/006_create_drivers_table.php`
- `database/migrations/007_create_shipments_table.php`
- `database/migrations/008_create_orders_table.php`
- `database/migrations/009_create_order_items_table.php`
- `database/migrations/010_create_stock_movements_table.php`
- `database/migrations/011_create_invoices_table.php`
- `database/migrations/012_create_payments_table.php`
- `database/migrations/013_create_expenses_table.php`
- `database/migrations/014_create_system_settings_table.php`
- `database/migrations/015_create_permission_tables.php`
- `database/migrations/016_create_activity_log_table.php`
- `database/migrations/017_create_notifications_table.php`

### Verification:
- `php artisan migrate:fresh` with configured MySQL failed because `factory_user`/`factory_db` are not available locally.
- `php artisan migrate:fresh` with SQLite override passed all 17 migrations.
- `php artisan migrate:status` confirmed all 17 migrations ran.
- `php artisan test` with SQLite override -> 2 passed.

### Next Session Plan:
- PHASE 02: Money value object and state machines.

## Session 003 - Value Objects & State Machines
**Date:** 2026-05-16
**Phase:** 02 - Value Objects, Enums & State Machines

### Completed:
- [x] Created immutable `Money` value object
- [x] Created `InvalidStatusTransitionException`
- [x] Created `OrderStateMachine`
- [x] Created `ShipmentStateMachine`
- [x] Added unit tests for money arithmetic, immutability, formatting, and currency validation
- [x] Added unit tests for order transition rules and final states
- [x] Added unit tests for shipment transition rules and final states

### Files Created (7):
- `app/Exceptions/InvalidStatusTransitionException.php`
- `app/ValueObjects/Money.php`
- `app/StateMachines/OrderStateMachine.php`
- `app/StateMachines/ShipmentStateMachine.php`
- `tests/Unit/MoneyValueObjectTest.php`
- `tests/Unit/OrderStateMachineTest.php`
- `tests/Unit/ShipmentStateMachineTest.php`

### Verification:
- Focused Phase 02 tests -> 30 passed, 48 assertions
- Full suite -> 32 passed, 50 assertions

### Next Session Plan:
- PHASE 03: Base service, base repository, contracts, and DI bindings.

## Session 004 - Base Classes & Contracts
**Date:** 2026-05-16
**Phase:** 03 - Base Classes & Contracts

### Completed:
- [x] Created `BaseService` with transaction, pagination, money, and integer money parsing helpers
- [x] Created `BaseRepository` with common CRUD, pagination, and guarded soft-delete restore support
- [x] Created six repository contracts
- [x] Created six service contracts
- [x] Created export strategy contract
- [x] Registered repository/service interface bindings in `AppServiceProvider`
- [x] Registered state machines as singletons
- [x] Enabled SQLite in-memory testing through `phpunit.xml`
- [x] Added infrastructure tests for service helpers, repository CRUD, pagination, and bindings

### Files Created (18):
- `app/Services/BaseService.php`
- `app/Repositories/BaseRepository.php`
- `app/Contracts/Repositories/OrderRepositoryInterface.php`
- `app/Contracts/Repositories/ProductRepositoryInterface.php`
- `app/Contracts/Repositories/CustomerRepositoryInterface.php`
- `app/Contracts/Repositories/InvoiceRepositoryInterface.php`
- `app/Contracts/Repositories/ShipmentRepositoryInterface.php`
- `app/Contracts/Repositories/StockMovementRepositoryInterface.php`
- `app/Contracts/Services/OrderServiceInterface.php`
- `app/Contracts/Services/ProductServiceInterface.php`
- `app/Contracts/Services/CustomerServiceInterface.php`
- `app/Contracts/Services/InvoiceServiceInterface.php`
- `app/Contracts/Services/ShipmentServiceInterface.php`
- `app/Contracts/Services/PdfServiceInterface.php`
- `app/Contracts/Export/ExportStrategyInterface.php`
- `tests/Unit/BaseServiceTest.php`
- `tests/Feature/BaseRepositoryTest.php`
- `tests/Feature/AppServiceProviderBindingsTest.php`

### Files Updated (5):
- `app/Providers/AppServiceProvider.php`
- `phpunit.xml`
- `TASKS.md`
- `TODO.md`
- `PROGRESS.md`

### Verification:
- Focused Phase 03 tests -> 8 passed, 26 assertions
- `php artisan clear-compiled` -> passed
- Pint formatting pass -> fixed minor style issues
- Full suite -> 40 passed, 76 assertions

### Notes:
- Repository and service bindings are registered now to enforce contracts, while their concrete implementations are completed in later model/module phases.

### Next Session Plan:
- PHASE 04: Models, model traits, observers, relationships, casts, and model-level guards.

## Session 005 - Models & Observers
**Date:** 2026-05-16
**Phase:** 04 - Models & Observers

### Completed:
- [x] Created reusable model traits for sequential codes, money formatting, and soft-delete guards
- [x] Created `CodeGeneratorFactory` with transaction-safe, year-scoped sequential code generation
- [x] Implemented 13 domain models with fillables, integer money casts, relationships, scopes, and computed attributes
- [x] Updated `User` with soft deletes, Spatie roles, schema fields, casts, and relationships
- [x] Created observers for orders, products, invoices, and payments
- [x] Registered observers in `EventServiceProvider` and added the provider to Laravel 11 bootstrap providers
- [x] Added Arabic translation files for model guard messages, stock movement labels, and activity log messages
- [x] Added model layer tests covering generated codes, money casts, relationships, soft-delete guards, translated labels, settings, and observer activity logs

### Files Created (27):
- `app/Models/Traits/GeneratesSequentialCode.php`
- `app/Models/Traits/HasMoneyFormatting.php`
- `app/Models/Traits/HasSoftDeleteGuard.php`
- `app/Factories/CodeGeneratorFactory.php`
- `app/Models/ProductCategory.php`
- `app/Models/Product.php`
- `app/Models/Customer.php`
- `app/Models/Order.php`
- `app/Models/OrderItem.php`
- `app/Models/StockMovement.php`
- `app/Models/Truck.php`
- `app/Models/Driver.php`
- `app/Models/Shipment.php`
- `app/Models/Invoice.php`
- `app/Models/Payment.php`
- `app/Models/Expense.php`
- `app/Models/SystemSetting.php`
- `app/Observers/OrderObserver.php`
- `app/Observers/ProductObserver.php`
- `app/Observers/InvoiceObserver.php`
- `app/Observers/PaymentObserver.php`
- `app/Providers/EventServiceProvider.php`
- `lang/ar/products.php`
- `lang/ar/customers.php`
- `lang/ar/stock_movements.php`
- `lang/ar/activity.php`
- `tests/Feature/ModelLayerTest.php`

### Files Updated (7):
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `bootstrap/providers.php`
- `config/factory.php`
- `TASKS.md`
- `TODO.md`
- `PROGRESS.md`

### Verification:
- Quick boot smoke: `php artisan test tests/Unit/ExampleTest.php` -> 1 passed
- Focused Phase 04 tests -> 6 passed, 33 assertions
- Pint formatting pass -> fixed minor observer style issues
- Full suite -> 46 passed, 109 assertions
- `php artisan clear-compiled` -> passed
- Unit suite after clear -> 33 passed, 54 assertions

### Notes:
- Observer update handlers use `getChanges()` instead of `getDirty()` because Eloquent update events have already synchronized dirty attributes.
- Invoice PDF regeneration is guarded until `PdfService` exists in a later phase.

### Next Session Plan:
- PHASE 05: Seed roles, permissions, system settings, and default admin user.

## Session 006 - Seeders & Roles
**Date:** 2026-05-16
**Phase:** 05 - Seeders & Roles

### Completed:
- [x] Created `RolesAndPermissionsSeeder` with 47 permissions and 4 roles (super_admin, accountant, shipping_staff, customer)
- [x] `super_admin` receives all permissions via `givePermissionTo(Permission::all())`
- [x] Created `SystemSettingsSeeder` with 16 default settings across factory, invoices, stock, customers, and UI groups
- [x] Created `AdminUserSeeder` with 3 default accounts (admin, accountant, shipping_staff)
- [x] Created `ProductCategorySeeder` with 8 default Arabic product categories
- [x] Updated `DatabaseSeeder` as master orchestrator with strict run order
- [x] Added `SeedersTest` covering roles, permissions, super_admin coverage, shipping_staff exact permissions, settings, admin users, categories, and full DatabaseSeeder run

### Files Created (5):
- `database/seeders/RolesAndPermissionsSeeder.php`
- `database/seeders/SystemSettingsSeeder.php`
- `database/seeders/AdminUserSeeder.php`
- `database/seeders/ProductCategorySeeder.php`
- `tests/Feature/SeedersTest.php`

### Files Updated (1):
- `database/seeders/DatabaseSeeder.php`

### Verification:
- Focused Phase 05 tests -> 7 passed, 24 assertions
- `php artisan migrate:fresh --seed` with SQLite override -> all 17 migrations + 4 seeders succeeded
- Pint formatting pass -> fixed minor style issues
- Full suite -> 53 passed, 133 assertions

### Notes:
- MySQL `migrate:fresh --seed` not available locally; SQLite verification confirms zero errors.
- Permission count is 47 (not 34 as initially estimated) — validated against the explicit PERMISSIONS array.

### Next Session Plan:
- PHASE 06: Authentication & Middleware (login controller, active check, portal guard, locale, activity tracking).

## Session 007 - Authentication & Middleware
**Date:** 2026-05-16
**Phase:** 06 - Authentication & Middleware

### Completed:
- [x] Created `LoginController` with email/phone login, rate limiting (5 attempts / 15 min), active-user check, login metadata tracking, and role-based redirect
- [x] Created `SetLocale` middleware — forces Arabic locale on every request
- [x] Created `CheckUserIsActive` middleware — logs out deactivated users and redirects to login with Arabic error
- [x] Created `CustomerPortalMiddleware` — redirects customer-role users away from admin routes to portal
- [x] Created `LastActivityMiddleware` — updates `last_seen_at` every 5 minutes using cache
- [x] Registered all middleware in `bootstrap/app.php` with global web stack and named aliases
- [x] Restructured `routes/web.php` with guest login, authenticated admin/staff groups, customer portal prefix, and logout
- [x] Added Arabic auth translation strings in `lang/ar/auth.php`
- [x] Created minimal `resources/views/auth/login.blade.php` with Arabic text
- [x] Updated `UserFactory` to include `is_active` and `phone` defaults (fixes `MissingAttributeException` under strict mode)
- [x] Created `AuthTest` with 12 tests covering login page, email login, phone login, wrong password, inactive user, customer redirect, rate limiting, login metadata, ERP access control, customer redirect from admin, logout, and deactivated user middleware block

### Files Created (8):
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Middleware/SetLocale.php`
- `app/Http/Middleware/CheckUserIsActive.php`
- `app/Http/Middleware/CustomerPortalMiddleware.php`
- `app/Http/Middleware/LastActivityMiddleware.php`
- `lang/ar/auth.php`
- `resources/views/auth/login.blade.php`
- `tests/Feature/AuthTest.php`

### Files Updated (4):
- `bootstrap/app.php`
- `routes/web.php`
- `database/factories/UserFactory.php`
- `tests/Feature/ExampleTest.php`

### Verification:
- Focused Phase 06 tests -> 12 passed, 37 assertions
- `php artisan clear-compiled` -> passed
- Pint formatting pass -> fixed minor style issues
- Full suite -> 65 passed, 171 assertions

### Notes:
- Middleware order matters: `portal` must run before `role` so customer redirection happens before role-based 403 rejection.
- `LastActivityMiddleware` updates `last_seen_at` (not `last_login_at` which is only updated on login).

### Next Session Plan:
- PHASE 07: Module 01 — Inventory (StockService, ProductService, ProductRepository, ProductController, form requests, and tests).

## Session 008 - Inventory Module (Phase 07 Module 01)
**Date:** 2026-05-16
**Phase:** 07 - Module 01: Inventory

### Completed:
- [x] Created `StockService` with transaction-safe stock movements, low-stock event firing, and absolute stock adjustments
- [x] Created `ProductService` for product CRUD with optional image upload and soft-delete support
- [x] Created `ProductRepository` with search, filters, low-stock query, lock-for-update, and auto code generation
- [x] Created `StockMovementRepository` with product-scoped and date-range queries
- [x] Created `ProductController` with `authorizeResource`, full CRUD, and restore action
- [x] Created `StockController` for movement index, adjustment, and low-stock alert
- [x] Created `StoreProductRequest` and `UpdateProductRequest` with validation rules and authorization
- [x] Created `StockAdjustmentRequest` for stock adjustment authorization and validation
- [x] Created `ProductPolicy` with permission-based authorization for all product actions
- [x] Created `InsufficientStockException` and `LowStockDetected` event
- [x] Created minimal Blade views for product CRUD and stock movement pages
- [x] Created `routes/products.php` and required it in `routes/web.php`
- [x] Added Arabic translations for products and stock_movements
- [x] Created `ProductFactory` and `ProductCategoryFactory`
- [x] Created `StockServiceTest` (6 tests, 12 assertions)
- [x] Created `ProductCrudTest` (8 tests, 23 assertions)

### Files Created (17):
- `app/Services/Products/StockService.php`
- `app/Services/Products/ProductService.php`
- `app/Repositories/ProductRepository.php`
- `app/Repositories/StockMovementRepository.php`
- `app/Http/Controllers/Products/ProductController.php`
- `app/Http/Controllers/Products/StockController.php`
- `app/Http/Requests/Products/StoreProductRequest.php`
- `app/Http/Requests/Products/UpdateProductRequest.php`
- `app/Http/Requests/Products/StockAdjustmentRequest.php`
- `app/Policies/ProductPolicy.php`
- `app/Exceptions/InsufficientStockException.php`
- `app/Events/Stock/LowStockDetected.php`
- `database/factories/ProductFactory.php`
- `database/factories/ProductCategoryFactory.php`
- `tests/Unit/StockServiceTest.php`
- `tests/Feature/ProductCrudTest.php`
- `routes/products.php`

### Files Updated (8):
- `app/Repositories/BaseRepository.php` (removed return types to avoid PHP variance conflicts with interfaces; fixed `restore` to not call `withTrashed()` on `newQueryWithoutScopes()`)
- `app/Http/Controllers/Controller.php` (added `AuthorizesRequests` and `ValidatesRequests` traits, extended Laravel base `Controller`)
- `app/Contracts/Repositories/*` (removed return types from shared CRUD methods to allow `BaseRepository` inheritance)
- `routes/web.php` (removed placeholder product routes that conflicted with real routes)
- `app/Http/Requests/Products/StoreProductRequest.php` (made `code` nullable to allow auto-generation)
- `app/Http/Controllers/Products/ProductController.php` (added `DomainException` catch in `destroy`)
- `tests/Feature/BaseRepositoryTest.php` (removed obsolete non-soft-delete restore test)
- `lang/ar/products.php`

### Verification:
- Focused Phase 07 tests -> 14 passed, 35 assertions
- `php artisan clear-compiled` -> passed
- Pint formatting pass -> fixed minor style issues across 26 files
- Full suite -> 78 passed, 205 assertions

### Notes:
- `BaseRepository` cannot declare return types for shared CRUD methods when concrete repositories implement interfaces with narrower return types. PHP variance rules require the interface methods to omit return types so inherited implementations remain compatible.
- All placeholder product routes in `web.php` were removed because they shadowed real controller routes.

### Next Session Plan:
- PHASE 07: Module 02 — Customers (CustomerService, CustomerRepository, CustomerController, form requests, policy, and tests).

## Session 009 - Customer Module (Phase 07 Module 02)
**Date:** 2026-05-16
**Phase:** 07 - Module 02: Customers

### Completed:
- [x] Created `CreateCustomerDTO` immutable data transfer object with `fromArray` factory
- [x] Created `CustomerRepository` with search, filters, pagination, and order-search query
- [x] Created `CustomerService` implementing `CustomerServiceInterface` — CRUD, portal access enable/disable, balance recalculation, order history
- [x] Created `CustomerController` with `authorizeResource`, CRUD, activation toggle, portal access toggle, and statement view
- [x] Created `StoreCustomerRequest` and `UpdateCustomerRequest` with full validation and permission-based authorization
- [x] Created `CustomerPolicy` with permission checks for view, create, update, delete, manageCredit, and viewBalance
- [x] Created minimal `ReportService` stub in `app/Services/Erp/` to satisfy controller statement action
- [x] Created `CustomerFactory` with proper `User::factory()` relation for `created_by`
- [x] Created minimal Blade views for customer pages
- [x] Created `routes/customers.php` and required it in `routes/web.php`
- [x] Updated `lang/ar/customers.php` with full Arabic translation strings
- [x] Created `CustomerCrudTest` with 11 tests covering creation, uniqueness, portal access, credit calculation, update, soft delete, active-order guard, activation toggle, and statement view

### Files Created (14):
- `app/DTOs/Customers/CreateCustomerDTO.php`
- `app/Repositories/CustomerRepository.php`
- `app/Services/Customers/CustomerService.php`
- `app/Services/Erp/ReportService.php`
- `app/Http/Controllers/Customers/CustomerController.php`
- `app/Http/Requests/Customers/StoreCustomerRequest.php`
- `app/Http/Requests/Customers/UpdateCustomerRequest.php`
- `app/Policies/CustomerPolicy.php`
- `database/factories/CustomerFactory.php`
- `tests/Feature/CustomerCrudTest.php`
- `routes/customers.php`
- `resources/views/customers/index.blade.php`
- `resources/views/customers/create.blade.php`
- `resources/views/customers/show.blade.php`
- `resources/views/customers/edit.blade.php`
- `resources/views/customers/statement.blade.php`

### Files Updated (3):
- `routes/web.php` (removed placeholder customer routes, added `require customers.php`)
- `lang/ar/customers.php`
- `database/factories/CustomerFactory.php` (fixed `created_by` relation and Faker `state()` call)

### Verification:
- Focused Phase 07 Module 02 tests -> 11 passed, 31 assertions
- `php artisan clear-compiled` -> passed
- Pint formatting pass -> fixed minor style issues across 9 files
- Full suite -> 89 passed, 236 assertions

### Notes:
- `CustomerRepository` does NOT override `create`/`update`/`delete` from `BaseRepository` to avoid PHP parameter variance errors. `BaseRepository`'s broader `Model` parameter types naturally satisfy the interface's narrower `Customer` parameter types via contravariance.
- `ReportService` is a minimal stub; full ERP reporting engine will be built in a later phase.
- `CustomerFactory` uses `User::factory()` for `created_by` to satisfy the foreign key constraint in strict test environments.

### Next Session Plan:
- PHASE 07: Module 03 — Orders (OrderService, OrderRepository, OrderController, form requests, policy, DTOs, and tests).

## Session 010 - Orders Module (Phase 07 Module 03)
**Date:** 2026-05-16
**Phase:** 07 - Module 03: Orders

### Completed:
- [x] Created `CreateOrderDTO` and `OrderItemDTO` immutable data transfer objects with `fromArray` factories and financial calculation methods (`lineTotal`, `discountAmount`, `totalQuantity`)
- [x] Created validation pipeline: `ValidateCustomerCreditPipe`, `ValidateStockAvailabilityPipe`, `CalculateOrderTotalsPipe`
- [x] Created `OrderFinancialsService` for subtotal, discount, tax, and total calculations using `SettingService`
- [x] Created `OrderService` implementing `OrderServiceInterface` — CRUD through pipeline validation, transaction-safe creation with event firing
- [x] Created `OrderStatusService` managing full lifecycle transitions (accept, preparing, ready, deliver, cancel, return) with stock adjustments and invoice stub integration
- [x] Created `OrderRepository` with eager-loaded queries, search, filters, and status grouping
- [x] Created `OrderController` with `authorizeResource`, CRUD, and daily view
- [x] Created `OrderStatusController` for all status transition endpoints with proper authorization
- [x] Created `StoreOrderRequest`, `UpdateOrderRequest`, and `CancelOrderRequest` with validation and permission checks
- [x] Created `OrderPolicy` with permission-based authorization, customer self-view restriction, and editable/cancellable state checks
- [x] Created `AuthServiceProvider` and registered all policies (`ProductPolicy`, `CustomerPolicy`, `OrderPolicy`)
- [x] Created order events: `OrderCreated`, `OrderAccepted`, `OrderCancelled`, `OrderDelivered`
- [x] Created `CreditLimitExceededException`
- [x] Created minimal stubs: `SettingService`, `InvoiceService`, `RecordPaymentDTO`
- [x] Created `OrderFactory` and `OrderItemFactory`
- [x] Created `OrderCrudTest` with 11 tests covering creation, controller creation, stock deduction on acceptance, stock return on cancellation, credit limit blocking, full lifecycle, non-editable guard, deletion guard, update, and daily view
- [x] Added Arabic translations for orders and invoices
- [x] Created minimal Blade views and `routes/orders.php`

### Files Created (24):
- `app/DTOs/Orders/CreateOrderDTO.php`
- `app/DTOs/Orders/OrderItemDTO.php`
- `app/Pipelines/Order/ValidateCustomerCreditPipe.php`
- `app/Pipelines/Order/ValidateStockAvailabilityPipe.php`
- `app/Pipelines/Order/CalculateOrderTotalsPipe.php`
- `app/Services/Orders/OrderFinancialsService.php`
- `app/Services/Orders/OrderService.php`
- `app/Services/Orders/OrderStatusService.php`
- `app/Repositories/OrderRepository.php`
- `app/Http/Controllers/Orders/OrderController.php`
- `app/Http/Controllers/Orders/OrderStatusController.php`
- `app/Http/Requests/Orders/StoreOrderRequest.php`
- `app/Http/Requests/Orders/UpdateOrderRequest.php`
- `app/Http/Requests/Orders/CancelOrderRequest.php`
- `app/Policies/OrderPolicy.php`
- `app/Providers/AuthServiceProvider.php`
- `app/Events/Orders/OrderCreated.php`
- `app/Events/Orders/OrderAccepted.php`
- `app/Events/Orders/OrderCancelled.php`
- `app/Events/Orders/OrderDelivered.php`
- `app/Exceptions/CreditLimitExceededException.php`
- `app/Services/SettingService.php`
- `app/Services/Invoices/InvoiceService.php`
- `app/DTOs/Invoices/RecordPaymentDTO.php`
- `database/factories/OrderFactory.php`
- `database/factories/OrderItemFactory.php`
- `tests/Feature/OrderCrudTest.php`
- `routes/orders.php`

### Files Updated (7):
- `routes/web.php` (removed placeholder order routes, added `require orders.php`)
- `lang/ar/orders.php`
- `lang/ar/invoices.php`
- `database/seeders/RolesAndPermissionsSeeder.php` (added `orders.delete` permission)
- `app/StateMachines/OrderStateMachine.php` (allowed `ready` -> `delivered` transition)
- `tests/Feature/SeedersTest.php` (updated permission count from 47 to 48)
- `bootstrap/providers.php` (registered `AuthServiceProvider`)

### Verification:
- Focused Phase 07 Module 03 tests -> 11 passed, 31 assertions
- `php artisan clear-compiled` -> passed
- Pint formatting pass -> fixed minor style issues across 20 files
- Full suite -> 100 passed, 267 assertions

### Notes:
- `Eloquent strict mode` (`preventLazyLoading`) caused failures when iterating `$order->items` and accessing `$item->product` without eager loading. Fixed by adding `$order->load('items.product')` in `OrderStatusService::accept`, `cancel`, and `recordReturn`.
- `InvoiceService` is a minimal stub to satisfy `OrderStatusService::accept` invoice creation. Full invoicing module will replace it in Phase 07 Module 06.
- The blueprint test expects `ready` -> `delivered` directly. Updated `OrderStateMachine` and `OrderPolicy::confirmDelivery` to allow this transition.
- `AuthServiceProvider` was necessary because Laravel 11 auto-discovery alone was not resolving policies correctly for custom abilities like `confirmDelivery`.

### Next Session Plan:
- PHASE 07: Module 04 — Distribution (Shipments) (ShipmentService, ShipmentRepository, ShipmentController, form requests, policy, and tests).
