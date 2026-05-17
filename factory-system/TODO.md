# TODO.md - Factory System Launch Board

This compact board replaces the historical phase checklist. Completed phase details live in `PROGRESS.md`.

## Current Status

| Area | Status | Evidence |
|---|---|---|
| Repository implementation | Done | Phases 00 through 12 are implemented. |
| Release hardening | Done | Payment ownership, order update validation, shipment delivery guards, money precision, backup/preflight hardening. |
| Local Laravel tests | Done | `php artisan test` -> 180 tests, 481 assertions. |
| Local preflight | Done | `factory:preflight --json` -> 36 passed, 14 warnings, 0 failures. |
| Production launch | Pending | Requires target host with PHP 8.3, MySQL 8, Redis, SMTP, TLS, Supervisor. |

## Blocking Before Release Sign-Off

| ID | Status | Task | Acceptance Criteria |
|---|---|---|---|
| REL-001 | Done | Run final formatting/build/cache gates after the latest edits. | Pint, tests, build, cache commands, Composer validate, route count, schedule list, and preflight all pass. |
| REL-002 | Done | Decide final handling of `database/seeders/SystemTestSeeder.php`. | Seeder is style-clean, tested, and intended to be kept for load-test operations. Add to version control when ready. |
| REL-003 | Done | Review unrelated working-tree changes. | Pre-existing modifications (AppServiceProvider, UserManagementService, product factories) and deleted Docker placeholders are safe and do not affect tests. |
| REL-004 | Pending | Validate production runtime. | `php artisan factory:preflight --production --runtime` returns zero failures on target host. |
| REL-005 | Pending | Complete launch checklist. | HTTPS, workers, scheduler, backups, SMTP, browser and PDF smoke tests pass. |

## Release Verification Commands

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

## Notes

- Local warnings are expected for PHP 8.2, Redis, SMTP/log mailer, insecure local URL, and missing local `mysqldump`.
- Do not run `SystemTestSeeder.php` in production unless a deliberate load-test dataset is required.
- Preserve unrelated user changes until they are explicitly approved for cleanup or commit.
