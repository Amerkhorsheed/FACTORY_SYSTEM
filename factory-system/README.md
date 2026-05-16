# 🏭 Factory Distribution & Shipping Management System

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20.svg?style=flat&logo=laravel)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4.svg?style=flat&logo=php)](https://php.net)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)]()
[![Tests](https://img.shields.io/badge/tests-146%20passed-success.svg)]()

A comprehensive, enterprise-grade Enterprise Resource Planning (ERP) application engineered to manage end-to-end factory operations. Built on a robust Laravel 11 foundation, this system seamlessly integrates inventory management, order processing, shipping and distribution logistics, financial invoicing, and advanced reporting.

## ✨ Core Modules & Features

- **📦 Inventory Management**: Centralized tracking of products, real-time stock adjustments, automated low-stock alerts, and comprehensive stock movement histories.
- **👥 Customer Portal & CRM**: Full customer lifecycles, credit line management, integrated portal access, and dynamic financial statements.
- **🛒 Order Processing Pipeline**: State-machine driven order lifecycles, automatic stock & credit validation, and comprehensive financial reconciliation.
- **🚚 Distribution & Logistics**: Shipment tracking, driver and truck management, manifest generation, and structured dispatch/delivery workflows.
- **💳 Financials & Invoicing**: Automated invoice generation, payment tracking, expense logging, and precise recalculation logic (using integer-based Money value objects to prevent float rounding errors).
- **📊 ERP Dashboard & Reporting**: Real-time KPIs, sales analytics, receivables, stock valuations, and P&L (Profit & Loss) reports.
- **🔐 Admin & Security**: Granular Role-Based Access Control (RBAC), comprehensive system activity auditing, customizable application settings, and secure user management.

## 🏗 Architecture & Engineering Standards

This system adheres to strict engineering and architectural patterns to ensure scalability, reliability, and maintainability:

- **Service-Repository Pattern**: Thin controllers delegating complex business logic to discrete Services, and data access logic abstracted behind Repositories and Contracts.
- **State Machines**: Orders and Shipments utilize formal state machines preventing invalid business transitions (e.g., dispatching an already delivered shipment).
- **Money Value Object**: All monetary values are handled as `BIGINT` in the database and wrapped in a domain-specific `Money` value object in PHP to guarantee absolute precision.
- **Transactional Integrity**: All critical state changes and database writes are strictly wrapped within `DB::transaction()`.
- **Strict Authorization**: Every controller action is gated by `$this->authorize()` calling dedicated model Policies.

## 🚀 Getting Started

### Prerequisites
- **PHP** >= 8.3
- **Composer** 2.x
- **Node.js** & **npm** (for frontend assets)
- **MySQL** 8.0+ or **SQLite** (for testing/local development)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Amerkhorsheed/FACTORY_SYSTEM.git
   cd factory-system
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install NPM dependencies:**
   ```bash
   npm install
   npm run build
   ```

4. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Configure your `.env` file with appropriate database credentials (`DB_CONNECTION`, `DB_HOST`, etc.).*

5. **Run Migrations & Seeders:**
   ```bash
   php artisan migrate --seed
   ```
   *This will run 17 strict-order migrations and seed the database with initial Roles, Permissions, Admin users, Settings, and Categories.*

6. **Serve the application:**
   ```bash
   php artisan serve
   ```

## 🧪 Testing

The platform maintains strict test coverage. To execute the test suite (currently 146 tests with 377 assertions passing):

```bash
php artisan test
```
*Note: The test suite is pre-configured to utilize an in-memory SQLite database for high-speed execution.*

## 📂 Project Management

Project progression, architectural decisions, and agent tasks are meticulously documented in the root directory:
- `PROGRESS.md` - Live build progress, phase tracking, and session logs.
- `AGENT.md` - Core system constraints and agent instructions.
- `DECISIONS.md` - Technical and architectural decision records.
- `TASKS.md` & `TODO.md` - Active task tracking and module specifications.

## 📄 License

The Factory Distribution & Shipping Management System is proprietary software. All rights reserved.
