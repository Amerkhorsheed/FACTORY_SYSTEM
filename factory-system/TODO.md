# TODO.md - Factory System Sprint Board
*Updated by agent after every task*

---

## PHASE 00 - ENVIRONMENT BOOTSTRAP
- [x] Create AGENT.md
- [x] Create PROGRESS.md
- [x] Create TODO.md
- [x] Create DECISIONS.md
- [x] Create SKILLS.md
- [x] Create TASKS.md index
- [x] composer create-project laravel/laravel
- [x] Install all composer packages
- [x] Install all npm packages
- [x] Configure .env
- [x] Create config/factory.php
- [x] Create config/money.php
- [x] Create config/pdf.php
- [x] php artisan key:generate
- [x] php artisan storage:link
- [x] Run Phase 00 verification commands

## PHASE 01 - DATABASE MIGRATIONS
- [x] Migration 001: users
- [x] Migration 002: product_categories
- [x] Migration 003: products
- [x] Migration 004: customers
- [x] Migration 005: trucks
- [x] Migration 006: drivers
- [x] Migration 007: shipments
- [x] Migration 008: orders
- [x] Migration 009: order_items
- [x] Migration 010: stock_movements
- [x] Migration 011: invoices
- [x] Migration 012: payments
- [x] Migration 013: expenses
- [x] Migration 014: system_settings
- [x] Migration 015: permission_tables (Spatie)
- [x] Migration 016: activity_log (Spatie)
- [x] Migration 017: notifications
- [x] php artisan migrate:fresh -> zero errors locally using SQLite

## PHASE 02 - VALUE OBJECTS & STATE MACHINES
- [x] app/Exceptions/InvalidStatusTransitionException.php
- [x] app/ValueObjects/Money.php
- [x] app/StateMachines/OrderStateMachine.php
- [x] app/StateMachines/ShipmentStateMachine.php
- [x] tests/Unit/MoneyValueObjectTest.php
- [x] tests/Unit/OrderStateMachineTest.php
- [x] tests/Unit/ShipmentStateMachineTest.php
- [x] php artisan test tests/Unit/MoneyValueObjectTest.php tests/Unit/OrderStateMachineTest.php tests/Unit/ShipmentStateMachineTest.php
- [x] php artisan test

## PHASE 03 - BASE CLASSES & CONTRACTS
- [x] app/Services/BaseService.php
- [x] app/Repositories/BaseRepository.php
- [x] Repository interfaces
- [x] Service interfaces
- [x] Export strategy interface
- [x] AppServiceProvider DI bindings
- [x] Pint style check and formatting
- [x] php artisan clear-compiled
- [x] php artisan test

## PHASE 04 - MODELS & OBSERVERS
- [x] app/Models/Traits/GeneratesSequentialCode.php
- [x] app/Models/Traits/HasMoneyFormatting.php
- [x] app/Models/Traits/HasSoftDeleteGuard.php
- [x] app/Factories/CodeGeneratorFactory.php
- [x] app/Models/ProductCategory.php
- [x] app/Models/Product.php
- [x] app/Models/Customer.php
- [x] app/Models/Order.php
- [x] app/Models/OrderItem.php
- [x] app/Models/StockMovement.php
- [x] app/Models/Truck.php
- [x] app/Models/Driver.php
- [x] app/Models/Shipment.php
- [x] app/Models/Invoice.php
- [x] app/Models/Payment.php
- [x] app/Models/Expense.php
- [x] app/Models/SystemSetting.php
- [x] Update app/Models/User.php for schema, soft deletes, roles, and relationships
- [x] app/Observers/OrderObserver.php
- [x] app/Observers/ProductObserver.php
- [x] app/Observers/InvoiceObserver.php
- [x] app/Observers/PaymentObserver.php
- [x] app/Providers/EventServiceProvider.php registration
- [x] Arabic translation files for model guards, stock movement labels, and activity messages
- [x] tests/Feature/ModelLayerTest.php
- [x] Pint style check and formatting
- [x] php artisan clear-compiled
- [x] php artisan test

## PHASE 05 - SEEDERS & ROLES
- [x] database/seeders/RolesAndPermissionsSeeder.php
- [x] database/seeders/SystemSettingsSeeder.php
- [x] database/seeders/AdminUserSeeder.php
- [x] database/seeders/ProductCategorySeeder.php
- [x] Update database/seeders/DatabaseSeeder.php
- [x] Seed super_admin, accountant, shipping_staff, and customer roles
- [x] Seed all module permissions using module.action format
- [x] Assign all permissions to super_admin
- [x] Seed required system settings
- [x] Create default admin user
- [x] tests/Feature/SeedersTest.php
- [x] Pint style check and formatting
- [x] php artisan migrate:fresh --seed verification

## PHASE 06 - AUTHENTICATION & MIDDLEWARE
- [x] app/Http/Controllers/Auth/LoginController.php
- [x] app/Http/Middleware/CheckUserIsActive.php
- [x] app/Http/Middleware/CustomerPortalMiddleware.php
- [x] app/Http/Middleware/SetLocale.php
- [x] app/Http/Middleware/LastActivityMiddleware.php
- [x] Register middleware aliases in bootstrap/app.php
- [x] routes/web.php authentication structure
- [x] lang/ar/auth.php
- [x] resources/views/auth/login.blade.php
- [x] database/factories/UserFactory.php updated with is_active and phone defaults
- [x] tests/Feature/AuthTest.php
- [x] Pint style check and formatting
- [x] php artisan clear-compiled
- [x] php artisan test

## PHASE 07 - MODULE 01: INVENTORY
- [x] app/Services/Products/StockService.php
- [x] app/Services/Products/ProductService.php
- [x] app/Repositories/ProductRepository.php
- [x] app/Repositories/StockMovementRepository.php
- [x] app/Http/Controllers/Products/ProductController.php
- [x] app/Http/Controllers/Products/StockController.php
- [x] app/Http/Requests/Products/StoreProductRequest.php
- [x] app/Http/Requests/Products/UpdateProductRequest.php
- [x] app/Http/Requests/Products/StockAdjustmentRequest.php
- [x] app/Policies/ProductPolicy.php
- [x] app/Exceptions/InsufficientStockException.php
- [x] app/Events/Stock/LowStockDetected.php
- [x] Product CRUD tests (ProductCrudTest)
- [x] Stock service tests (StockServiceTest)
- [x] Views and translations for products/stock
- [x] routes/products.php

## PHASE 07 - MODULE 02: CUSTOMERS
- [x] app/DTOs/Customers/CreateCustomerDTO.php
- [x] app/Repositories/CustomerRepository.php
- [x] app/Services/Customers/CustomerService.php
- [x] app/Services/Erp/ReportService.php (minimal stub)
- [x] app/Http/Controllers/Customers/CustomerController.php
- [x] app/Http/Requests/Customers/StoreCustomerRequest.php
- [x] app/Http/Requests/Customers/UpdateCustomerRequest.php
- [x] app/Policies/CustomerPolicy.php
- [x] database/factories/CustomerFactory.php
- [x] Customer CRUD tests (CustomerCrudTest)
- [x] Views and translations for customers
- [x] routes/customers.php

## PHASE 07 - MODULE 03: ORDERS
- [x] app/DTOs/Orders/CreateOrderDTO.php + OrderItemDTO.php
- [x] app/Pipelines/Order/ValidateCustomerCreditPipe.php
- [x] app/Pipelines/Order/ValidateStockAvailabilityPipe.php
- [x] app/Pipelines/Order/CalculateOrderTotalsPipe.php
- [x] app/Services/Orders/OrderFinancialsService.php
- [x] app/Services/Orders/OrderService.php
- [x] app/Services/Orders/OrderStatusService.php
- [x] app/Repositories/OrderRepository.php
- [x] app/Http/Controllers/Orders/OrderController.php
- [x] app/Http/Controllers/Orders/OrderStatusController.php
- [x] app/Http/Requests/Orders/StoreOrderRequest.php
- [x] app/Http/Requests/Orders/UpdateOrderRequest.php
- [x] app/Http/Requests/Orders/CancelOrderRequest.php
- [x] app/Policies/OrderPolicy.php
- [x] app/Providers/AuthServiceProvider.php
- [x] app/Events/Orders/OrderCreated.php, OrderAccepted.php, OrderCancelled.php, OrderDelivered.php
- [x] app/Exceptions/CreditLimitExceededException.php
- [x] app/Services/SettingService.php
- [x] app/Services/Invoices/InvoiceService.php (minimal stub)
- [x] app/DTOs/Invoices/RecordPaymentDTO.php
- [x] database/factories/OrderFactory.php + OrderItemFactory.php
- [x] Order CRUD tests (OrderCrudTest)
- [x] Views, routes, translations for orders

## DONE
- [x] Initial workspace discovery
- [x] Management files created
- [x] Laravel 11 scaffold created
- [x] Backend and frontend packages installed
- [x] Vendor publishing completed
- [x] Initial configuration files created
- [x] Phase 00 verification completed
- [x] Phase 01 migrations completed and verified locally
- [x] Phase 02 value objects and state machines completed
- [x] Phase 03 base classes and contracts completed
- [x] Phase 04 models and observers completed
- [x] Phase 05 seeders and roles completed
- [x] Phase 06 authentication and middleware completed
