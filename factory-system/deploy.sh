#!/usr/bin/env bash

set -Eeuo pipefail

BRANCH="${1:-${DEPLOY_BRANCH:-main}}"
APP_DIR="${APP_DIR:-/var/www/factory-system}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
RUN_BACKUP="${RUN_BACKUP:-true}"
PREFLIGHT_RUNTIME="${PREFLIGHT_RUNTIME:-false}"
SUPERVISOR_GROUP="${SUPERVISOR_GROUP:-factory:*}"
MAINTENANCE_ENTERED="false"

log() {
    printf '\n[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1"
}

artisan() {
    "$PHP_BIN" artisan "$@"
}

restore_on_failure() {
    if [ "$MAINTENANCE_ENTERED" = "true" ]; then
        artisan up || true
    fi
}

trap restore_on_failure ERR

log "1/10 Preflight checks"
cd "$APP_DIR"
test -f artisan
test -f .env
command -v "$PHP_BIN" >/dev/null
command -v "$COMPOSER_BIN" >/dev/null
command -v "$NPM_BIN" >/dev/null

log "2/10 Enabling maintenance mode"
artisan down --retry=60 --render="errors.503"
MAINTENANCE_ENTERED="true"

log "3/10 Updating code from ${BRANCH}"
git fetch origin "$BRANCH"
git pull --ff-only origin "$BRANCH"

log "4/10 Installing PHP dependencies"
"$COMPOSER_BIN" install --no-dev --prefer-dist --optimize-autoloader --no-interaction

log "5/10 Building frontend assets"
"$NPM_BIN" ci --prefer-offline
"$NPM_BIN" run build

log "6/10 Running database backup"
if [ "$RUN_BACKUP" = "true" ]; then
    artisan factory:backup
else
    log "Backup skipped because RUN_BACKUP=false"
fi

log "7/10 Clearing caches and migrating"
artisan optimize:clear
artisan migrate --force --no-interaction
artisan storage:link || true

log "8/10 Warming production caches"
artisan config:cache
artisan route:cache
artisan view:cache
artisan event:cache
PREFLIGHT_ARGS=(--production)
if [ "$PREFLIGHT_RUNTIME" = "true" ]; then
    PREFLIGHT_ARGS+=(--runtime)
fi
artisan factory:preflight "${PREFLIGHT_ARGS[@]}"

log "9/10 Restarting workers and scheduler"
artisan queue:restart
if command -v supervisorctl >/dev/null 2>&1; then
    supervisorctl reread || true
    supervisorctl update || true
    supervisorctl restart "$SUPERVISOR_GROUP" || true
fi

log "10/10 Fixing permissions and disabling maintenance"
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true
artisan up
MAINTENANCE_ENTERED="false"

log "Deployment complete"
