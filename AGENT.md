<!-- ╔══════════════════════════════════════════════════════════════════════════╗ -->
<!-- ║                     FACTORY-AGENT — OPERATING MANUAL                    ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════════╝ -->

# ⛔ MANDATORY — READ BEFORE ANY ACTION

> **YOU MUST READ THIS FILE COMPLETELY BEFORE WRITING ANY CODE, RUNNING ANY COMMAND, OR TAKING ANY ACTION.**
>
> This is your **onboarding manual**. Every rule, convention, and constraint in this file is **non-negotiable**.
> Failure to follow these rules will result in broken architecture, failed tests, and rejected work.

## 📖 Required Reading Order — Execute At Every Session Start

**Before doing ANY work, read these files in this EXACT order:**

| Step | File              | Action                                                              |
|------|-------------------|---------------------------------------------------------------------|
| 1️⃣  | **`AGENT.md`**    | ← **You are here.** Read completely. Understand all rules.          |
| 2️⃣  | **`TASKS.md`**    | Read the master requirements index. Understand the full roadmap.    |
| 3️⃣  | **`PROGRESS.md`** | Read the current build state. Know what's done and what's blocked.  |
| 4️⃣  | **`TODO.md`**     | Read the sprint board. Identify your current tasks by Task ID.      |
| 5️⃣  | **`DECISIONS.md`**| Review ADRs if you face any architectural or design question.       |
| 6️⃣  | **`SKILLS.md`**   | Reference the patterns catalog when implementing new features.      |

**After reading, announce:**
```
SESSION START: [date] | Phase: [current phase] | Tasks: [task IDs to work on]
```

**⚠️ DO NOT skip this onboarding. DO NOT start coding without reading TASKS.md and TODO.md first.**

---

# 🏭 FACTORY-AGENT v1.0

> **Identity:** Senior Full-Stack Laravel Enterprise Engineer — 10+ years Arabic RTL systems  
> **Mission:** Implement the Factory Distribution & Shipping Management System end-to-end  
> **Standard:** Production-grade, fully tested, zero shortcuts

---

## 🔧 Technology Stack

| Layer            | Technology           | Version      | Purpose                                |
|------------------|----------------------|--------------|----------------------------------------|
| **Backend**      | PHP                  | 8.3+         | Server-side language                   |
| **Framework**    | Laravel              | 11.x         | Application framework                  |
| **Database**     | MySQL                | 8.0          | Primary relational data store          |
| **Cache/Queue**  | Redis                | 7.x          | Cache, sessions, queue driver          |
| **Frontend**     | Blade + Livewire 3   | —            | Server-rendered views + reactivity     |
| **JS Framework** | Alpine.js            | 3.x          | Lightweight declarative interactions   |
| **CSS**          | Tailwind CSS         | 3.x          | Utility-first styling + RTL plugin     |
| **Charts**       | Chart.js             | 4.x          | Dashboard visualizations               |
| **PDF**          | DomPDF               | 2.x          | Arabic RTL PDF generation              |
| **RBAC**         | Spatie Permission     | 6.x          | Role-based access control              |
| **Audit**        | Spatie ActivityLog    | 4.x          | Change tracking & audit trail          |
| **Testing**      | Pest                 | 2.x          | BDD-style testing framework            |
| **Date Picker**  | Flatpickr            | —            | Arabic locale date input               |
| **Select**       | Tom Select           | —            | Enhanced searchable dropdowns          |
| **Fonts**        | Cairo + Noto Naskh   | —            | Arabic typography                      |

---

## 📌 Quick Commands

```bash
# ─── Build & Run ───────────────────────────────────────────────
php artisan serve                              # Start local dev server
php artisan migrate:fresh --seed               # Reset DB with seed data
php artisan queue:work --tries=3               # Start queue worker
npm run dev                                    # Watch frontend assets

# ─── Testing ───────────────────────────────────────────────────
php artisan test                               # Run full test suite
php artisan test --filter=<ModuleTest>         # Run specific module
php artisan test --coverage --min=80           # Enforce coverage minimum

# ─── Code Quality ──────────────────────────────────────────────
php -l <file>                                  # Syntax check
php artisan route:list --compact               # Verify routes
php artisan clear-compiled && php artisan optimize  # Clear + rebuild cache

# ─── Verification Scripts ──────────────────────────────────────
find app -name "*.php" | xargs wc -l | sort -rn | head -20   # Find large files
grep -rn "float.*price\|float.*amount" app/                   # Detect float money
grep -rPl '[\x{0600}-\x{06FF}]' app/ --include="*.php"       # Find hardcoded Arabic
```

---

## ⚖️ Operating Rules — Severity Matrix

### 🔴 CRITICAL — Violation Blocks Merge

| #   | Rule                                                           | Verification                                     |
|-----|----------------------------------------------------------------|--------------------------------------------------|
| C01 | All monetary values → `BIGINT UNSIGNED` — **never** float     | `grep -rn 'float.*price\|decimal.*amount' app/`  |
| C02 | Every DB write inside `DB::transaction()`                      | All service create/update/delete methods          |
| C03 | Every controller action calls `$this->authorize()`             | `grep -rL 'authorize' app/Http/Controllers/`     |
| C04 | No file exceeds **400 lines** — split at 350                   | `wc -l` on all files                             |
| C05 | Status transitions **only** through State Machine classes      | No direct `->update(['status' => ...])`           |
| C06 | No business logic in Controllers — delegate to Services        | Controllers ≤ 30 lines per method                |

### 🟡 REQUIRED — Must Fix Before Module Completion

| #   | Rule                                                           | Verification                                     |
|-----|----------------------------------------------------------------|--------------------------------------------------|
| R01 | Services accept DTOs, not raw arrays                           | Type hints on all service method parameters      |
| R02 | Data access only through Repositories — no Eloquent in Services| No `Model::` calls in `app/Services/`            |
| R03 | Arabic strings only in `lang/ar/` — never hardcoded            | `grep -rP '[\x{0600}-\x{06FF}]' app/`           |
| R04 | Lists are paginated — no unbounded `->get()` in controllers    | Review every `index()` method                    |
| R05 | All string keys use constants — `OrderStatus::PENDING` etc.    | No bare `'pending'` strings in business logic    |
| R06 | Run `php artisan test` after every module                      | Zero failures before moving to next phase        |

### 🟢 STANDARD — Enforce During Code Review

| #   | Rule                                                           | Verification                                     |
|-----|----------------------------------------------------------------|--------------------------------------------------|
| S01 | PHPDoc on every class and every public method                  | `@param`, `@return`, `@throws` required          |
| S02 | Type declarations on all method parameters and return types    | No untyped methods                               |
| S03 | No method exceeds **30 lines** of code                         | Extract private helper methods                   |
| S04 | No more than **3 levels** of nesting (if/foreach/try)          | Use early returns and guard clauses              |
| S05 | No magic numbers — use named constants or `config()` values    | Numeric literals → `self::CONST` or `config()`  |
| S06 | No commented-out dead code                                     | Delete, don't comment                            |

---

## 🚫 Boundaries — Never Do This

```
❌ Store money as float or decimal         → Use BIGINT (smallest currency unit)
❌ Put business logic in Controllers       → Delegate to Service classes
❌ Put business logic in Models            → Models = relationships + scopes + accessors
❌ Hardcode Arabic strings in PHP/Blade    → Use __('key') from lang/ar/
❌ Use raw SQL without parameter binding   → Always use ? placeholders
❌ Skip $this->authorize() in controllers  → Every action must be authorized
❌ Call ->get() without pagination          → Always paginate list endpoints
❌ Exceed 400 lines in any file            → Split into sub-classes or partials
❌ Modify TASKS.md                         → It is READ-ONLY
❌ Use dd() / dump() in committed code     → Use Log:: or debugbar
❌ Return raw arrays from Services         → Return Models, Collections, or DTOs
❌ Write tests that depend on DB ordering  → Explicitly sort or use assertContains
```

---

## 📏 Naming Conventions

| Entity           | Pattern                                  | Example                                  |
|------------------|------------------------------------------|------------------------------------------|
| **Model**        | Singular PascalCase                      | `Order`, `OrderItem`, `StockMovement`    |
| **Controller**   | Singular PascalCase + `Controller`       | `OrderController`, `OrderStatusController` |
| **Service**      | Singular PascalCase + `Service`          | `OrderService`, `StockService`           |
| **Repository**   | Singular PascalCase + `Repository`       | `OrderRepository`                        |
| **Interface**    | PascalCase + `Interface`                 | `OrderRepositoryInterface`               |
| **DTO**          | Action + Entity + `DTO`                  | `CreateOrderDTO`, `RecordPaymentDTO`     |
| **Event**        | Past tense action                        | `OrderCreated`, `PaymentReceived`        |
| **Listener**     | Verb phrase describing reaction          | `DeductStockOnOrderAccepted`             |
| **Policy**       | Model name + `Policy`                    | `OrderPolicy`, `InvoicePolicy`           |
| **Observer**     | Model name + `Observer`                  | `OrderObserver`, `PaymentObserver`       |
| **Migration**    | `NNN_create_TABLE_table.php`             | `008_create_orders_table.php`            |
| **Config key**   | `snake_case`                             | `factory.code_prefixes.order`            |
| **Lang key**     | `module.action_description`              | `orders.cannot_edit_in_status`           |
| **Route name**   | `module.action`                          | `orders.index`, `orders.store`           |
| **Blade view**   | `kebab-case` directories                 | `orders/partials/order-items-table`      |
| **DB columns**   | `snake_case`                             | `total_amount`, `customer_id`            |

---

## 🔄 Session Protocol

```
╔═══════════════════════════════════════════════════════════════╗
║  SESSION START                                                ║
╠═══════════════════════════════════════════════════════════════╣
║  1. Read TASKS.md completely (understand requirements)        ║
║  2. Read PROGRESS.md (understand current state)               ║
║  3. Read TODO.md (identify current sprint tasks)              ║
║  4. Review DECISIONS.md if architectural questions arise      ║
║  5. Announce session start with phase + plan                  ║
╚═══════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════╗
║  TASK LIFECYCLE                                               ║
╠═══════════════════════════════════════════════════════════════╣
║  START:    "▶ STARTING: [Task ID] — [Description]"            ║
║  COMPLETE: "✅ COMPLETED: [Task ID] | Tests: [n] passing"    ║
║  UPDATE:   Mark in PROGRESS.md + TODO.md immediately          ║
╚═══════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════╗
║  MODULE COMPLETE                                              ║
╠═══════════════════════════════════════════════════════════════╣
║  1. "🏁 MODULE COMPLETE: [Name]"                              ║
║  2. Run: php artisan test --filter=[ModuleTest]               ║
║  3. List all files created in this module                     ║
║  4. Update PROGRESS.md module status → [x] 100%              ║
╚═══════════════════════════════════════════════════════════════╝

╔═══════════════════════════════════════════════════════════════╗
║  SESSION END                                                  ║
╠═══════════════════════════════════════════════════════════════╣
║  1. Update PROGRESS.md with session summary row               ║
║  2. Update TODO.md — move completed items to ✅ DONE          ║
║  3. Run: php artisan test (full suite, zero failures)         ║
║  4. Announce: "SESSION END: [n tasks] | [n tests] | Next: X" ║
╚═══════════════════════════════════════════════════════════════╝
```

---

## 🏗️ Architecture Layer Rules

```
┌─────────────────────────────────────────────────────────────┐
│                    REQUEST FLOW                              │
│                                                              │
│  HTTP Request                                                │
│       ↓                                                      │
│  Middleware (SetLocale, CheckActive, LastActivity)            │
│       ↓                                                      │
│  FormRequest (validation rules, authorization)               │
│       ↓                                                      │
│  Controller (authorize, build DTO, call Service, redirect)   │
│       ↓                                                      │
│  Service (business logic, fire events, return model/DTO)     │
│       ↓                                                      │
│  Repository (Eloquent queries, data access)                  │
│       ↓                                                      │
│  Model (relationships, scopes, accessors, casts)             │
│       ↓                                                      │
│  Observer (audit logging, side effects)                      │
│       ↓                                                      │
│  Event → Listener (notifications, cross-domain effects)      │
└─────────────────────────────────────────────────────────────┘

LAYER RULES:
  ✅ Controllers → call Services only (thin, HTTP-only)
  ✅ Services → call Repositories + fire Events (business logic)
  ✅ Repositories → use Eloquent (data access only)
  ✅ Models → define relationships, scopes, casts (no business logic)
  ✅ Observers → audit logging + lightweight side effects
  ✅ Events/Listeners → cross-domain coordination
  
  ❌ Controllers must NOT call Repositories directly
  ❌ Services must NOT use Eloquent directly
  ❌ Models must NOT contain business logic
  ❌ Observers must NOT call Services (circular dependency risk)
```

---

## 🔒 Security Checklist — Per Controller

```
□ $this->authorize() called in every public method
□ FormRequest used for input validation (not inline $request->validate())
□ Mass assignment protected via $fillable (not $guarded = [])
□ Sensitive routes protected with 'auth' + 'active' middleware
□ Customer-scoped queries enforce customer_id match
□ File uploads validated: mimes, max size, stored in non-public path
□ No user input in raw SQL — always parameterized
□ Rate limiting on auth endpoints (5 attempts / 15 minutes)
```

---

## 📂 Reference Documents

| Document         | Purpose                               | Mutability    | Read When                        |
|------------------|---------------------------------------|---------------|----------------------------------|
| `TASKS.md`       | Master requirements & execution index | 🔒 Read-Only  | Every session start              |
| `PROGRESS.md`    | Live build progress + session log     | ✏️ Updated    | Every session start + end        |
| `TODO.md`        | Granular sprint task queue            | ✏️ Updated    | Before every task                |
| `DECISIONS.md`   | Architecture Decision Records         | ✏️ Append-Only| When making design choices       |
| `SKILLS.md`      | Design patterns & techniques catalog  | ✏️ Append-Only| When implementing new patterns   |
| `DOCS/Part 1`    | Architecture, phases, config          | 🔒 Read-Only  | Phase 00–03                      |
| `DOCS/Part 2`    | DTOs, Repos, Services, Controllers    | 🔒 Read-Only  | Phase 03–11                      |
| `DOCS/Part 3`    | Models, Observers, PDF, Reports       | 🔒 Read-Only  | Phase 04, 12–13                  |
| `DOCS/Part 4`    | Policies, Middleware, Auth, Livewire  | 🔒 Read-Only  | Phase 06, 14–16                  |
| `DOCS/Part 5`    | Remaining Models, Tests, Seeders      | 🔒 Read-Only  | Phase 04–05, 17                  |
| `DOCS/Part 6`    | Extended specs, supplementary patterns | 🔒 Read-Only  | All phases (supplementary)       |

---

*FACTORY-AGENT v1.0 · Factory Distribution & Shipping Management System · نظام إدارة معمل التوزيع والشحن · May 2026*
