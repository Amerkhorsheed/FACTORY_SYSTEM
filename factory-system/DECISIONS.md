# DECISIONS.md
## ADR-001: Money Storage
**Decision:** Store all monetary values as BIGINT UNSIGNED (smallest unit)
**Reason:** Avoid floating-point precision errors in financial calculations
**Date:** 2026-05-16

## ADR-002: Service Layer Pattern
**Decision:** Thin controllers delegate ALL logic to Service classes
**Reason:** Testability, single responsibility, no fat controllers
**Date:** 2026-05-16

## ADR-003: Composer Version Constraints
**Decision:** Use equivalent tilde major ranges for package installation on Windows.
**Reason:** The current shell passed caret ranges as exact constraints, causing dependency resolution failures.
**Date:** 2026-05-16

## ADR-004: Task Source
**Decision:** Use the prompt files under `../DOCS/` as the current source of truth.
**Reason:** A detailed requirements `TASKS.md` was absent before bootstrap.
**Date:** 2026-05-16

## ADR-005: Local Cache/Session Drivers
**Decision:** Use file cache, file session, and sync queue in local `.env` until Redis is available.
**Reason:** The current Windows PHP runtime has no Redis extension, causing local HTTP requests to fail before application code executes.
**Date:** 2026-05-16
