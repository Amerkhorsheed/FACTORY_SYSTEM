<!-- ╔══════════════════════════════════════════════════════════════════════════╗ -->
<!-- ║           FACTORY DISTRIBUTION & SHIPPING MANAGEMENT SYSTEM             ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════════╝ -->

# 🏭 Factory Distribution & Shipping Management System

### نظام إدارة معمل التوزيع والشحن

> Enterprise-grade Arabic RTL management platform for factory distribution operations — orders, invoicing, shipments, inventory, and customer relationship management.

[![Laravel 11](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php)](https://php.net)
[![Tests 153](https://img.shields.io/badge/Tests-153%20passing-4BC51D?style=flat-square)](https://pestphp.com)
[![License MIT](https://img.shields.io/badge/License-MIT-blue?style=flat-square)](LICENSE)

---

## ✨ Key Features

| Module | Capabilities |
|--------|-------------|
| **📦 Inventory** | Product CRUD, stock movements, low-stock alerts, barcode support, category management |
| **👥 Customers** | Customer lifecycle, A/B/C categorization, credit limits, outstanding balance tracking |
| **📋 Orders** | Full lifecycle (8 statuses), pipeline validation (credit + stock), price snapshots |
| **🚛 Distribution** | Shipment planning, truck/driver assignment, manifest generation, delivery tracking |
| **🧾 Invoicing** | Auto-generation from orders, partial payments, balance recalculation, statement view |
| **💰 Payments** | Multi-method (cash, credit, check, bank transfer), receipt tracking, audit trail |
| **📊 ERP Dashboard** | KPI cards, sales reports, receivables aging, stock valuation, profit & loss |
| **🔧 Admin** | User management, RBAC (7 roles, 30+ permissions), system settings, audit log |
| **🌐 Customer Portal** | Self-service order viewing, invoice access, profile management, scoped data |

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  HTTP Request → Middleware → FormRequest → Controller       │
│       ↓                                                      │
│  Controller (authorize, DTO, call service, redirect)         │
│       ↓                                                      │
│  Service (business logic, events, transactions)              │
│       ↓                                                      │
│  Repository (Eloquent queries, data access)                  │
│       ↓                                                      │
│  Model (relationships, scopes, casts) → Observer (audit)     │
└─────────────────────────────────────────────────────────────┘
```

**15 Design Patterns Applied:** Service Layer, Repository, Observer, State Machine, Strategy, Factory, Template Method, Facade, DTO, Pipeline, Event/Listener, Value Object, Decorator, Chain of Responsibility, Command.

---

## 🔧 Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| **Backend** | PHP 8.2+ / Laravel 11 | Application framework |
| **Database** | MySQL 8.0 / SQLite (dev) | Relational data store |
| **Cache/Queue** | Redis 7.x (prod) / File (dev) | Cache, sessions, queues |
| **Frontend** | Blade + Alpine.js 3 | Server-rendered + reactive |
| **Styling** | Tailwind CSS 3 + RTL plugin | Arabic RTL utility-first CSS |
| **Charts** | Chart.js 4 | Dashboard visualizations |
| **PDF** | DomPDF 2 | Arabic RTL PDF generation |
| **RBAC** | Spatie Permission 6 | Role-based access control |
| **Audit** | Spatie ActivityLog 4 | Change tracking & audit trail |
| **Testing** | Pest 2 | BDD-style testing (153 tests) |
| **Typography** | Cairo + Noto Naskh Arabic | Arabic font families |

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `xml`, `gd`, `intl`
- Composer 2.x
- Node.js 18+ / npm 9+
- MySQL 8.0 (or SQLite for development)

### Installation

```bash
# Clone the repository
git clone https://github.com/Amerkhorsheed/FACTORY_SYSTEM.git
cd FACTORY_SYSTEM/factory-system

# Install PHP dependencies
composer install

# Install frontend dependencies
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed data
php artisan migrate:fresh --seed

# Build frontend assets
npm run build

# Start development server
php artisan serve
```

### Default Login

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@factory.local` | `password` |

---

## 📁 Project Structure

```
FACTORY_SYSTEM/
├── AGENT.md              ← Agent operating manual & session protocols
├── TASKS.md              ← Master requirements & phase execution index
├── PROGRESS.md           ← Live build progress tracker (16 sessions)
├── TODO.md               ← Sprint task board with granular tracking
├── DECISIONS.md          ← 16 Architecture Decision Records (MADR)
├── SKILLS.md             ← 15 design patterns catalog with examples
├── DOCS/                 ← 6 source specification files (~663 KB)
├── factory-system/       ← Laravel 11 application
│   ├── app/
│   │   ├── Contracts/    ← Repository & service interfaces
│   │   ├── DTOs/         ← 5 immutable data transfer objects
│   │   ├── Events/       ← Domain events (Order, Stock, Invoice)
│   │   ├── Exceptions/   ← Custom domain exceptions
│   │   ├── Http/         ← Controllers, middleware, form requests
│   │   ├── Models/       ← 14 Eloquent models + 3 traits
│   │   ├── Observers/    ← 4 audit logging observers
│   │   ├── Pipelines/    ← Order validation pipeline (3 pipes)
│   │   ├── Policies/     ← 10 authorization policies
│   │   ├── Repositories/ ← 13 data access repositories
│   │   ├── Services/     ← 14+ business logic services
│   │   ├── StateMachines/← Order (8 status) & Shipment (5 status)
│   │   └── ValueObjects/ ← Money value object (integer-based)
│   ├── config/           ← factory.php, money.php, pdf.php
│   ├── database/         ← 17 migrations, 12 factories, 5 seeders
│   ├── lang/ar/          ← 14 Arabic translation files
│   ├── resources/views/  ← Blade views + 10 components
│   ├── routes/           ← 11 route files (97 routes)
│   └── tests/            ← 22 test files (153 passing, 395 assertions)
└── README.md             ← You are here
```

---

## 💰 Money Handling

All monetary values are stored as `BIGINT UNSIGNED` (integer-based, zero floating-point). The `Money` value object provides immutable arithmetic:

```php
$price = Money::of(50000, 'SYP');       // 50,000 piasters
$total = $price->multiply(3);            // 150,000
$display = $total->format();             // "150,000 ل.س"
```

---

## 📊 Build Status

| Metric | Value |
|--------|-------|
| **Tests** | 153 passing · 395 assertions |
| **Routes** | 97 registered |
| **Models** | 14 Eloquent models |
| **Services** | 14+ business services |
| **Policies** | 10 authorization policies |
| **Lang Files** | 14 Arabic translation files |
| **Overall Progress** | ~70% (Phases 00–08 of 18) |

---

## 📖 Documentation

| Document | Purpose |
|----------|---------|
| [AGENT.md](AGENT.md) | Agent operating manual — rules, conventions, architecture |
| [TASKS.md](TASKS.md) | Master requirements index — phases, deliverables, ER diagram |
| [PROGRESS.md](PROGRESS.md) | Live progress — session log, metrics, statistics |
| [TODO.md](TODO.md) | Sprint board — current tasks, upcoming work |
| [DECISIONS.md](DECISIONS.md) | 16 Architecture Decision Records with rationale |
| [SKILLS.md](SKILLS.md) | 15 design patterns catalog with code examples |

---

## 📄 License

This project is licensed under the MIT License.

---

*Built with precision for enterprise-scale factory distribution management.*
*نظام إدارة معمل التوزيع والشحن · May 2026*
