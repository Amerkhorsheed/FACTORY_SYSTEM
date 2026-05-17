# TASKS.md - Factory System Requirements Index

This file is the session-start entry point required by `AGENT.md`.

## Source Documents
- `../DOCS/AGENT_PROMPT_FACTORY_SYSTEM.md`
- `../DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART2.md`
- `../DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART3.md`
- `../DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART4.md`
- `../DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART5.md`
- `../DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART6.md`
- `../DOCS/AGENT_PROMPT_FACTORY_SYSTEM_PART7.md`

## Current Phase
Phase 12 - Final Launch Verification (target infrastructure)

## Current Local Baseline
- `php artisan test` passes: 180 tests, 481 assertions.
- `php artisan factory:preflight --json` reports 36 passed, 14 warnings, 0 failures.
- Application routes: 99 non-vendor routes.
- Remaining validation requires the real PHP 8.3/MySQL/Redis/SMTP/TLS production host.

## Non-Negotiable Rules
1. No file exceeds 400 lines.
2. Money is stored as BIGINT, never float.
3. Business logic belongs in services, not controllers.
4. Services use repositories for data access.
5. DB writes are wrapped in `DB::transaction()`.
6. Every controller action authorizes access.
7. Arabic strings belong in `lang/ar/`.
8. Lists are paginated; no unbounded `get()` in controllers.
