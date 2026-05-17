# Changelog

## v1.0.0 - 2026-05-17

Initial production-ready release of the Factory Distribution & Shipping Management System.

### Added

- Laravel 11 Arabic RTL application foundation.
- Authentication, active-user middleware, locale middleware, and customer portal guard.
- Role and permission model for `super_admin`, `accountant`, `shipping_staff`, and `customer`.
- Inventory module with product CRUD, stock adjustments, stock movements, and low-stock detection.
- Customer module with portal access, credit limits, balances, statements, and portal profile management.
- Order module with state-machine transitions, stock validation, credit validation, and lifecycle tests.
- Distribution module with trucks, drivers, shipments, dispatching, delivery, and manifests.
- Invoicing and payments with invoice issuing, voiding, balance recalculation, and payment deletion.
- ERP dashboard, expenses, sales report, receivables report, stock report, and profit/loss report.
- Admin module for users, settings, audit logs, and temporary password notifications.
- Arabic RTL frontend shell, reusable Blade components, public auth/welcome screens, and customer portal UI.
- DomPDF invoice, shipment manifest, and customer statement generation with private PDF storage.
- Queued database and email notifications for orders, invoices, payments, stock, overdue invoices, and temporary passwords.
- Livewire notification bell with polling, unread count, mark-read, and mark-all-read behavior.
- Deployment assets for PHP-FPM, Nginx, MySQL, Redis, Supervisor, and scripted VPS deployment.

### Verified

- `php artisan test` passes with 171 tests and 466 assertions.
- `vendor/bin/pint --test` passes.
- `npm run build` passes.
- `php artisan route:list --except-vendor` reports 99 application routes.
- `php artisan schedule:list` reports 3 scheduled commands.
- Project-managed files remain under the 400-line limit.
