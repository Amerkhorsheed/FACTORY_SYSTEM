<!-- ╔══════════════════════════════════════════════════════════════════════════╗ -->
<!-- ║               DECISIONS.md — ARCHITECTURE DECISION RECORDS              ║ -->
<!-- ╚══════════════════════════════════════════════════════════════════════════╝ -->

# Architecture Decision Records

> **Project:** Factory Distribution & Shipping Management System  
> **Methodology:** [MADR](https://adr.github.io/madr/) — accepted ADRs are immutable; supersede by creating a new ADR.

---

## Index

| ADR | Title | Status | Date | Supersedes |
|-----|-------|--------|------|------------|
| 001 | Money Storage Strategy | Accepted | 2026-05-16 | — |
| 002 | Service Layer Architecture | Accepted | 2026-05-16 | — |
| 003 | DOCS Directory as Source of Truth | Accepted | 2026-05-16 | — |
| 004 | Repository Pattern for Data Access | Accepted | 2026-05-16 | — |
| 005 | State Machine for Lifecycle Status Flows | Accepted | 2026-05-16 | — |
| 006 | DTO Pattern at Service Boundaries | Accepted | 2026-05-16 | — |
| 007 | Pipeline Pattern for Order Validation | Accepted | 2026-05-16 | — |
| 008 | 400-Line File Size Hard Limit | Accepted | 2026-05-16 | — |
| 009 | Arabic-First Localization via lang/ar/ | Accepted | 2026-05-16 | — |
| 010 | Redis as Unified Infrastructure Driver | Accepted | 2026-05-16 | — |
| 011 | Observer Pattern for Audit Logging | Accepted | 2026-05-16 | — |
| 012 | Event-Driven Cross-Domain Coordination | Accepted | 2026-05-16 | — |
| 013 | Facade Pattern for System Settings | Accepted | 2026-05-16 | — |
| 014 | Policy-Based Authorization | Accepted | 2026-05-16 | — |
| 015 | Docker-Based Production Deployment | Accepted | 2026-05-16 | — |
| 016 | Local Driver Fallback Until Redis Available | Accepted | 2026-05-16 | 010 |

---

## ADR-001: Money Storage Strategy

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Store all monetary values as `BIGINT UNSIGNED` representing the smallest currency unit (piasters). Wrap amounts in the immutable `Money` value object (`app/ValueObjects/Money.php`).
- **Consequences:** Zero floating-point precision errors; integer arithmetic is deterministic and fast. Requires parsing human inputs into integer units and formatting at display time.

## ADR-002: Service Layer Architecture

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Enforce a 3-tier architecture: Controller → Service → Repository. Controllers authorize and delegate; services wrap business logic in transactions; repositories own Eloquent queries.
- **Consequences:** Business logic is testable without HTTP, reusable across CLI/Jobs, and single-responsibility per class.

## ADR-003: DOCS Directory as Source of Truth

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Use `DOCS/AGENT_PROMPT_FACTORY_SYSTEM*.md` Parts 1–7 as the single source of truth for requirements. `TASKS.md` serves as the index.

## ADR-004: Repository Pattern for Data Access

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Every model gets an interface + concrete repository. Services inject repository interfaces, never concrete classes or models directly.
- **Consequences:** Services are unit-testable with mocks; query logic is centralized and swappable.

## ADR-005: State Machine for Lifecycle Status Flows

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Dedicated `OrderStateMachine` and `ShipmentStateMachine` classes define allowed transitions. Status changes go through the machine; direct ad-hoc updates are prohibited.
- **Consequences:** Illegal transitions are impossible; rules are testable and explicit.

## ADR-006: DTO Pattern at Service Boundaries

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Immutable DTOs with `readonly` properties at all service entry points. Each DTO provides `fromArray(array $data): self`.
- **Consequences:** Compile-time type safety, IDE autocompletion, and immutability prevent accidental corruption.

## ADR-007: Pipeline Pattern for Order Validation

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Use Laravel Pipeline with discrete pipes: `ValidateCustomerCreditPipe`, `ValidateStockAvailabilityPipe`, `CalculateOrderTotalsPipe`.
- **Consequences:** Each pipe is independently testable; new validation steps can be added without modifying the service.

## ADR-008: 400-Line File Size Hard Limit

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Hard limit of 400 lines per file; begin splitting at 350. Large services split by sub-concern, controllers by action group, views into partials, models into traits.

## ADR-009: Arabic-First Localization

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** All user-facing text lives in `lang/ar/*.php`, accessed via `__()`. Zero Arabic characters in `app/` PHP files.

## ADR-010: Redis as Unified Infrastructure Driver

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Redis serves cache, session, and queue roles in production.

## ADR-011: Observer Pattern for Audit Logging

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Audit-critical model events are logged via Observers using Spatie ActivityLog. Registered in `EventServiceProvider`.

## ADR-012: Event-Driven Cross-Domain Coordination

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Domain events coordinate cross-module side effects (e.g., `OrderAccepted` triggers stock deduction, invoice creation, and notifications).

## ADR-013: Facade Pattern for System Settings

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** `Setting` facade wraps `SettingService` with Redis-cached key-value access and 60-min TTL.

## ADR-014: Policy-Based Authorization

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Policy classes protect every domain action. `super_admin` bypasses all checks via `Gate::before()`.

## ADR-015: Docker-Based Production Deployment

- **Status:** Accepted · **Date:** 2026-05-16
- **Decision:** Production deployment via Docker Compose with PHP-FPM, Nginx, MySQL 8, Redis, and Supervisor containers.

## ADR-016: Local Driver Fallback Until Redis Available

- **Status:** Accepted · **Date:** 2026-05-16 · **Supersedes:** ADR-010 (local-only)
- **Decision:** Local `.env` uses file/sync fallbacks for cache, session, and queue until Redis is available. Production and Docker targets remain Redis.

---

*Architecture decisions are immutable. Create a new ADR to supersede an existing one.*
