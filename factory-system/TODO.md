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

## PHASE 07 - MODULE 04: DISTRIBUTION
- [x] app/DTOs/Shipments/CreateShipmentDTO.php
- [x] app/Repositories/ShipmentRepository.php
- [x] app/Services/Distribution/ShipmentService.php
- [x] app/Services/Distribution/ShipmentStatusService.php
- [x] app/Http/Controllers/Shipments/ShipmentController.php
- [x] app/Http/Controllers/Shipments/ShipmentStatusController.php
- [x] app/Http/Requests/Shipments/StoreShipmentRequest.php
- [x] app/Http/Requests/Shipments/UpdateShipmentRequest.php
- [x] app/Http/Requests/Shipments/DispatchShipmentRequest.php
- [x] app/Http/Requests/Shipments/AttachOrdersRequest.php
- [x] app/Policies/ShipmentPolicy.php
- [x] app/Events/ShipmentDispatched.php
- [x] app/Services/PdfService.php (stub)
- [x] database/factories/TruckFactory.php
- [x] database/factories/DriverFactory.php
- [x] database/factories/ShipmentFactory.php
- [x] Shipment CRUD tests (ShipmentCrudTest)
- [x] Views, routes, translations for shipments
- [x] Fixed BaseRepository variance in ShipmentRepositoryInterface
- [x] Registered ShipmentPolicy in AuthServiceProvider

## PHASE 07 - MODULE 05: INVOICING
- [x] app/Repositories/InvoiceRepository.php
- [x] app/Services/Invoices/InvoiceService.php (production rewrite)
- [x] app/Http/Controllers/Invoices/InvoiceController.php
- [x] app/Http/Requests/Invoices/StorePaymentRequest.php
- [x] app/Policies/InvoicePolicy.php
- [x] app/DTOs/Invoices/RecordPaymentDTO.php (updated)
- [x] app/Contracts/Services/InvoiceServiceInterface.php (updated)
- [x] database/factories/InvoiceFactory.php
- [x] database/factories/PaymentFactory.php
- [x] Invoice CRUD tests (InvoiceCrudTest)
- [x] Views, routes, translations for invoices
- [x] Registered InvoicePolicy in AuthServiceProvider

## PHASE 07 - MODULE 06: PAYMENTS & ERP
- [x] app/Repositories/ExpenseRepository.php
- [x] app/Repositories/PaymentRepository.php
- [x] app/Services/Erp/ExpenseService.php
- [x] app/Services/Erp/DashboardService.php
- [x] app/Services/Erp/ReportService.php (production report datasets)
- [x] app/Services/Invoices/PaymentService.php
- [x] app/Policies/PaymentPolicy.php
- [x] app/Policies/ExpensePolicy.php
- [x] app/Http/Requests/Erp/StoreExpenseRequest.php
- [x] app/Http/Requests/Erp/UpdateExpenseRequest.php
- [x] app/Http/Controllers/Invoices/PaymentController.php
- [x] app/Http/Controllers/Erp/ExpenseController.php
- [x] app/Http/Controllers/Erp/DashboardController.php
- [x] app/Http/Controllers/Erp/ReportController.php
- [x] database/factories/ExpenseFactory.php
- [x] Payment CRUD tests (PaymentCrudTest)
- [x] Expense CRUD tests (ExpenseCrudTest)
- [x] Dashboard tests (DashboardTest)
- [x] Views, routes, translations for payments, expenses, ERP
- [x] Registered PaymentPolicy and ExpensePolicy in AuthServiceProvider
- [x] Removed placeholder ERP routes from web.php
- [x] Refactored completed-module controllers to keep query/data access in services and repositories
- [x] Fixed customer statement invoice date filter to use issue_date
- [x] Full verification: php artisan test, Pint, route:list, 400-line source check

## QUALITY AUDIT - PHASE 00 THROUGH ERP
- [x] Confirmed `TASKS.md` source document index includes PART6
- [x] Audited completed module architecture against controller/service/repository rules
- [x] Moved payment, dashboard, report, product, stock, customer, and order listing/query logic into services/repositories
- [x] Confirmed completed controller actions authorize access
- [x] Confirmed money storage remains integer-based
- [x] Confirmed no non-generated project-managed file exceeds 400 lines
- [x] Updated `PROGRESS.md` with Session 014 audit record

## PHASE 07 - MODULE 07: ADMIN
- [x] User management repository/service/controllers/requests/policy
- [x] System settings repository/service/controllers/requests/policy
- [x] Audit/activity log listing and details
- [x] Admin routes, Arabic translations, and Blade views
- [x] Admin feature tests and authorization tests
- [x] Update `PROGRESS.md` and `TODO.md` after Admin module

## PHASE 08 - FRONTEND ENHANCEMENTS
- [x] Add Tailwind/PostCSS configuration and RTL frontend asset pipeline
- [x] Add shared RTL app layout with responsive sidebar, topbar, and alerts
- [x] Add reusable form/card/button/status/pagination/metric/empty-state components
- [x] Replace Admin and ERP dashboard pages with shared layout patterns
- [x] Implement customer portal repository/service/controller/routes/views
- [x] Add frontend/portal feature tests
- [x] Replace remaining module placeholder Blade pages with shared layout patterns
- [x] Add richer responsive CRUD screens for inventory, customers, orders, shipments, invoices, payments, expenses, and reports
- [x] Optional polish: upgrade auth login and welcome pages to the final visual standard
- [x] Keep PDF-specific Blade output for Phase 09 production PDF work

## PHASE 09 - PDF GENERATION
- [x] Rebuild PdfService around DomPDF with configured Arabic RTL output
- [x] Store PDFs under private configured storage paths
- [x] Persist invoice and manifest paths through service-owned transactions
- [x] Add invoice print and customer statement PDF routes
- [x] Replace invoice, shipment manifest, and customer statement PDF templates
- [x] Remove obsolete PDF placeholder stubs
- [x] Add PDF generation/download/auth feature tests
- [x] Run full Phase 09 verification commands

## PHASE 10 - NOTIFICATIONS & COMMUNICATION
- [x] Wire order status, invoice issued, payment received, low-stock, overdue, and temporary-password notifications
- [x] Ensure customer-facing and critical alerts use queued database/mail delivery
- [x] Add staff digest service for overdue invoices and low stock products
- [x] Register `factory:overdue-alerts` and `factory:low-stock-check` schedules
- [x] Add Livewire notification bell polling and mark-read behavior to the topbar
- [x] Add Arabic notification translations and email templates
- [x] Add notification feature tests and run full Phase 10 verification commands

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
- [x] Phase 07 Module 01 inventory completed
- [x] Phase 07 Module 02 customers completed
- [x] Phase 07 Module 03 orders completed
- [x] Phase 07 Module 04 distribution completed
- [x] Phase 07 Module 05 invoicing completed
- [x] Phase 07 Module 06 payments and ERP completed
- [x] Phase 07 Module 07 admin completed
- [x] Phase 08 frontend foundation and module view replacement completed
- [x] Phase 08 auth/welcome public frontend polish completed
- [x] Phase 09 PDF generation completed
- [x] Phase 10 notifications and communication completed
