#!/bin/bash
# =============================================================================
#  docker/entrypoint.sh
#  Custom startup wrapper for serversideup/php:8.5-frankenphp + Laravel 13
#
#  Execution order (runs as root):
#    1. Wait for the filesystem (WSL2 / Docker Desktop mount sync)
#    2. Create Laravel writable directory scaffold (idempotent)
#    3. Fix ownership & permissions on storage/ and bootstrap/cache/
#    4. (Optional) Remap www-data UID/GID to match the host user  ← PUID/PGID
#    5. Detect queue worker mode and run artisan directly (skip FrankenPHP)
#    6. Delegate to docker-php-serversideup-entrypoint  ← real serversideup init
# =============================================================================
set -euo pipefail

# ── Resolve application root (honours APP_BASE_DIR env var) ──────────────────
APP_DIR="${APP_BASE_DIR:-/var/www/html}"

# ─────────────────────────────────────────────────────────────────────────────
#  STEP 1 — Wait for the filesystem (WSL2 / Docker Desktop mount sync)
# ─────────────────────────────────────────────────────────────────────────────
CHECK_DIR="${CADDY_SERVER_ROOT:-${APP_DIR}/public}"
CHECK_FILE="${CHECK_DIR}/index.php"

# Docker Desktop / WSL2 can be slow to propagate a bind mount into a freshly
# started container — especially on a daemon restart, where depends_on ordering
# no longer applies and every container races for the same mount. Override with
# ENTRYPOINT_FS_TIMEOUT (seconds) per service.
MAX_RETRIES="${ENTRYPOINT_FS_TIMEOUT:-120}"

echo "[entrypoint] Waiting for ${CHECK_FILE} to become visible (timeout ${MAX_RETRIES}s)..."
RETRY_COUNT=0
while [ ! -f "${CHECK_FILE}" ]; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ "$RETRY_COUNT" -ge "$MAX_RETRIES" ]; then
        echo "[entrypoint] ERROR: ${CHECK_FILE} not found after ${MAX_RETRIES}s."
        echo "[entrypoint] DEBUG: APP_DIR=${APP_DIR}"
        echo "[entrypoint] DEBUG: CADDY_SERVER_ROOT=${CADDY_SERVER_ROOT:-not-set}"
        echo "[entrypoint] DEBUG: Current contents of ${APP_DIR}:"
        ls -la "${APP_DIR}" || echo "[entrypoint] Could not list ${APP_DIR}"
        echo "[entrypoint] Is the volume mounted correctly? (Hint: check Docker Desktop File Sharing)"
        exit 1
    fi
    echo "[entrypoint]   ... still waiting ($RETRY_COUNT/$MAX_RETRIES)"
    sleep 1
done
echo "[entrypoint] Filesystem is ready (${CHECK_FILE} found)."

# ─────────────────────────────────────────────────────────────────────────────
#  STEP 2 — Scaffold Laravel writable directories
# ─────────────────────────────────────────────────────────────────────────────
echo "[entrypoint] Scaffolding Laravel directories..."
mkdir -p \
    "${APP_DIR}/storage/app/public" \
    "${APP_DIR}/storage/framework/cache/data" \
    "${APP_DIR}/storage/framework/sessions" \
    "${APP_DIR}/storage/framework/testing" \
    "${APP_DIR}/storage/framework/views" \
    "${APP_DIR}/storage/logs" \
    "${APP_DIR}/bootstrap/cache"

# ─────────────────────────────────────────────────────────────────────────────
#  STEP 3 — Fix ownership and permissions
# ─────────────────────────────────────────────────────────────────────────────
echo "[entrypoint] Fixing ownership and permissions on writable directories..."

chown -R www-data:www-data \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

chmod -R 775 \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

docker-php-serversideup-set-file-permissions --owner www-data:www-data

echo "[entrypoint] Permissions OK."

# ── STEP 3b — Ensure vendor/ is writable ──────────────────────────────
if [ -d "${APP_DIR}/vendor" ]; then
    chown -R "${PUID:-33}:${PGID:-33}" "${APP_DIR}/vendor"
fi
if [ -f "${APP_DIR}/composer.lock" ]; then
    chown "${PUID:-33}:${PGID:-33}" "${APP_DIR}/composer.lock"
fi

# ─────────────────────────────────────────────────────────────────────────────
#  STEP 4 — (Optional) UID/GID remapping
# ─────────────────────────────────────────────────────────────────────────────
if [ -n "${PUID:-}" ] && [ -n "${PGID:-}" ]; then
    echo "[entrypoint] Remapping www-data → UID=${PUID} GID=${PGID}..."
    docker-php-serversideup-set-id www-data "${PUID}:${PGID}"

    chown -R "${PUID}:${PGID}" \
        "${APP_DIR}/storage" \
        "${APP_DIR}/bootstrap/cache"

    echo "[entrypoint] UID/GID remapped."
fi

# ── STEP 4b — Docker socket permissions ───────────────────────────────
if [ -S /var/run/docker.sock ]; then
    echo "[entrypoint] Adjusting docker socket permissions..."
    DOCKER_GID=$(stat -c '%g' /var/run/docker.sock)

    if [ "$DOCKER_GID" -eq 0 ]; then
        DOCKER_GROUP="root"
    elif ! getent group "$DOCKER_GID" > /dev/null; then
        groupadd -g "$DOCKER_GID" docker-socket
        DOCKER_GROUP="docker-socket"
    else
        DOCKER_GROUP=$(getent group "$DOCKER_GID" | cut -d: -f1)
    fi

    usermod -aG "$DOCKER_GROUP" www-data
    echo "[entrypoint] Added www-data to group ${DOCKER_GROUP} (${DOCKER_GID})"
fi

# ── STEP 5 — Ensure vendor/ is present before any artisan call ───────
if [ ! -f "${APP_DIR}/vendor/autoload.php" ]; then
    echo "[entrypoint] WARNING: ${APP_DIR}/vendor/autoload.php not found."
    if [ -f "${APP_DIR}/composer.json" ]; then
        echo "[entrypoint] Attempting to install dependencies..."
        if [ -d "${APP_DIR}/vendor" ]; then
            chown -R www-data:www-data "${APP_DIR}/vendor"
        fi
        su -s /bin/bash www-data -c "cd ${APP_DIR} && composer install --no-interaction --optimize-autoloader" || \
        echo "[entrypoint] ERROR: composer install failed."
    else
        echo "[entrypoint] ERROR: composer.json not found in ${APP_DIR}. Cannot install dependencies."
    fi
fi

if [ "${STARTUP_DB_GUARD:-false}" = "true" ]; then
    echo "[entrypoint] Running startup database self-heal guard..."
    if [ -d "${APP_DIR}/vendor" ]; then
        su -s /bin/bash www-data -c "cd ${APP_DIR} && php artisan app:dev-db-self-heal --no-interaction" || \
        echo "[entrypoint] WARNING: startup database self-heal failed; continuing startup."
    else
        echo "[entrypoint] WARNING: startup database self-heal skipped due to missing vendor; continuing startup."
    fi
fi

# ── STEP 6 — Detect queue worker mode ─────────────────────────────────
#  Use ${N:-} to avoid "unbound variable" errors with set -u when
#  the container CMD has fewer than 3 arguments.
# ─────────────────────────────────────────────────────────────────────────────
if [ "${1:-}" = "php" ] && [ "${2:-}" = "artisan" ] && { [ "${3:-}" = "queue:work" ] || [ "${3:-}" = "queue:listen" ]; }; then
    echo "[entrypoint] Queue worker detected — running artisan directly..."
    exec su -s /bin/bash www-data -c "cd ${APP_DIR} && exec $*"
fi

# ── STEP 7 — Delegate to the official serversideup entrypoint ─────────
echo "[entrypoint] Handing off to docker-php-serversideup-entrypoint..."
exec docker-php-serversideup-entrypoint "$@"
