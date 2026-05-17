# Launch Readiness Task Board

> **Project:** Factory Distribution & Shipping Management System
> **Current Sprint:** Phase 12 - Final Launch Verification
> **Sprint Goal:** complete repository cleanup and target-host validation for production launch.
> **Last Updated:** 2026-05-17

---

## Blocking Before Release Sign-Off

| ID | Status | Task | Owner Context |
|---|---|---|---|
| REL-001 | Done | Restore local PHP style gate. | `vendor/bin/pint --test` passes. |
| REL-002 | Done | Decide whether to keep and commit `SystemTestSeeder.php`. | Seeder is style-clean, tested, and intended to be kept for load-test operations. Add to version control when ready. |
| REL-003 | Done | Resolve or explicitly accept unrelated working-tree changes. | Pre-existing modifications (AppServiceProvider, UserManagementService, product factories) and deleted Docker placeholders are safe and do not affect tests. |
| REL-004 | Pending | Run production runtime preflight on the real host. | Requires PHP 8.3, MySQL, Redis, SMTP, TLS. |
| REL-005 | Done | Run final local release gates after latest hardening edits. | Pint, tests, build, cache commands, Composer validate, route count, schedule list, and preflight pass. |

---

## Completed Repository Work

| Phase | Status | Delivered |
|---|---|---|
| 00 Bootstrap | Done | Laravel app, dependencies, config, key/storage setup. |
| 01 Database | Done | 17 migrations with integer money columns and constraints. |
| 02 Domain Primitives | Done | `Money`, order state machine, shipment state machine. |
| 03 Base Architecture | Done | Base services, repositories, contracts, DI bindings. |
| 04 Models | Done | Domain models, traits, observers, activity support. |
| 05 Seeders/RBAC | Done | Roles, permissions, settings, admin users, categories, demo/load-test seeders. |
| 06 Auth | Done | Login/logout, active-user checks, locale, portal, last activity. |
| 07 Business Modules | Done | Inventory, customers, orders, distribution, invoicing, payments, ERP, admin. |
| 08 Frontend | Done | Shared RTL layout, responsive module pages, portal/public screens. |
| 09 PDF | Done | DomPDF service and Arabic PDF templates. |
| 10 Notifications | Done | Queued notifications, staff digests, Livewire bell, scheduled commands. |
| 11 Deployment Assets | Done | Env template, Nginx, Supervisor, deploy script, runbook, checklist. |
| 12 Launch Tooling | Done | `factory:preflight`, runtime checks, local verification gates. |

---

## Current Sprint Tasks

| ID | Priority | Status | Task | Acceptance Criteria |
|---|---|---|---|---|
| LR-001 | High | Done | Sync root `TASKS.md` to current launch baseline. | Root master index lists 7 prompt parts, Phase 12 state, actual counts, release gates. |
| LR-002 | High | Done | Implement missing load-test seeder cleanup. | `SystemTestSeeder.php` is Pint-clean and avoids float money math. |
| LR-003 | High | Done | Restore local style and test confidence. | Pint passes; full test suite passes. |
| LR-004 | Medium | Done | Sync root README/PROGRESS/TODO with current status. | Root docs no longer advertise outdated test counts or frontend-only progress. |
| LR-005 | High | Done | Harden release-critical business rules. | Payment ownership, order updates, shipment delivery, and integer percentage regressions pass. |
| LR-006 | Medium | Done | Harden backup and preflight checks. | `factory:backup` validates `mysqldump`; preflight reports backup readiness. |
| LR-007 | High | Done | Decide final handling of untracked `SystemTestSeeder.php`. | File is style-clean, tested, and intended to be kept for load-test operations. |
| LR-008 | High | Done | Review unrelated working-tree changes. | Pre-existing edits and deleted Docker placeholders are safe; release diff is clean. |
| LR-009 | High | Done | Compact root governance docs to 400-line limit. | `DECISIONS.md` and `SKILLS.md` are now under 400 lines. |
| LR-010 | High | Pending | Run target-host production preflight. | `php artisan factory:preflight --production --runtime` returns zero failures. |
| LR-011 | High | Pending | Complete launch checklist. | HTTPS, browser matrix, queue workers, scheduler, backups, SMTP, PDFs verified. |

---

## Production Host Checklist

| Area | Status | Required Evidence |
|---|---|---|
| PHP 8.3 FPM | Pending | `php -v` and preflight pass. |
| Nginx and HTTPS | Pending | HTTP redirects to HTTPS; valid certificate. |
| MySQL 8 | Pending | Migrations and seeders run against real database. |
| Redis | Pending | Cache, queue, session, and maintenance store use Redis. |
| Supervisor | Pending | Queue workers and scheduler are running. |
| SMTP | Pending | Test customer and staff notifications delivered. |
| Backups | Pending | `factory:backup` writes a valid backup. |
| Browser smoke | Pending | Chrome, Firefox, Edge, Safari and mobile widths verified. |
| PDF smoke | Pending | Invoice, manifest, and statement PDFs render Arabic correctly. |

---

## Verification Commands

Run from `factory-system/` before release sign-off:

```bash
vendor/bin/pint --test
php artisan test
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan schedule:list
php artisan factory:preflight --production
php artisan optimize:clear
```

Run on production after services are configured:

```bash
php artisan factory:preflight --production --runtime
```

---

## Notes

- Local verification cannot replace production runtime checks.
- Do not run `SystemTestSeeder.php` against production unless a deliberate load-test dataset is required.
- Preserve unrelated user changes until they are explicitly approved for cleanup or commit.
