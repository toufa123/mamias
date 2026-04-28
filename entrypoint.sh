#!/bin/bash
# =============================================================================
#  docker/entrypoint.sh
#  Custom startup wrapper for serversideup/php:8.5-frankenphp + Laravel 13
#
#  Execution order (runs as root):
#    1. Wait for the filesystem (WSL2 / Docker Desktop mount sync)
#    2. Create Laravel writable directory scaffold (idempotent)
#    3. Fix ownership & permissions on storage/ and bootstrap/cache/
#    4. Cache Filament components for faster back-office boot
#    5. (Optional) Remap www-data UID/GID to match the host user  ← PUID/PGID
#    6. Detect queue worker mode and run artisan directly (skip FrankenPHP)
#    7. Delegate to docker-php-serversideup-entrypoint  ← real serversideup init
#       which handles: Caddyfile generation, PHP ini, all AUTORUN_LARAVEL_* tasks,
#       privilege drop, and FrankenPHP startup.
# =============================================================================
set -euo pipefail

# ── Resolve application root (honours APP_BASE_DIR env var) ──────────────────
APP_DIR="${APP_BASE_DIR:-/var/www/html}"

# ─────────────────────────────────────────────────────────────────────────────
#  STEP 1 — Wait for the filesystem (WSL2 / Docker Desktop mount sync)
#
#  If you use a bind-mount (e.g. ./apps:/var/www/html) in compose.yml,
#  Docker Desktop + WSL2 can sometimes take several seconds to correctly
#  surface the files inside the container after a restart.
#
#  If FrankenPHP starts before index.php is visible, Caddy will
#  serve a 404 or close the connection.
# ─────────────────────────────────────────────────────────────────────────────
# Default fallback to /var/www/html/public if CADDY_SERVER_ROOT is not set
CHECK_DIR="${CADDY_SERVER_ROOT:-${APP_DIR}/public}"
CHECK_FILE="${CHECK_DIR}/index.php"

echo "[entrypoint] Waiting for ${CHECK_FILE} to become visible..."
MAX_RETRIES=30
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
#  mkdir -p is idempotent: safe to run even when directories exist.
#  This guarantees the paths are present even on a fresh `git clone` that has
#  only .gitkeep files or no storage/ scaffold at all.
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
#
#  • storage/ and bootstrap/cache/ must be writable by www-data at runtime.
#  • We do NOT touch the rest of the codebase to avoid obscuring bad
#    permissions that would matter in production.
#  • chmod 775 = owner (rwx) + group (rwx) + others (r-x).
#    This lets both www-data and any user in the www-data group write freely.
# ─────────────────────────────────────────────────────────────────────────────
echo "[entrypoint] Fixing ownership and permissions on writable directories..."

chown -R www-data:www-data \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

chmod -R 775 \
    "${APP_DIR}/storage" \
    "${APP_DIR}/bootstrap/cache"

# Also use the official serversideup helper to align web-server config
# file permissions with the expected owner (harmless if already correct).
docker-php-serversideup-set-file-permissions --owner www-data:www-data

echo "[entrypoint] Permissions OK."

# ── STEP 3b — Ensure vendor/ is writable for Composer ─────────────
if [ -d "${APP_DIR}/vendor" ]; then
    chown -R "${PUID:-33}:${PGID:-33}" "${APP_DIR}/vendor"
fi
if [ -f "${APP_DIR}/composer.lock" ]; then
    chown "${PUID:-33}:${PGID:-33}" "${APP_DIR}/composer.lock"
fi

# ── STEP 3c — Cache Filament components ───────────────────────────
#  This dramatically speeds up Filament back-office boot time by
#  pre-compiling the component registry into bootstrap/cache/filament.
#  It is safe to run on every container start; the command is idempotent.
# ─────────────────────────────────────────────────────────────────────────────
echo "[entrypoint] Caching Filament components..."
su -s /bin/bash www-data -c "cd ${APP_DIR} && php artisan filament:cache-components --ansi" \
    || echo "[entrypoint] Filament component cache skipped (may need composer install first)."

# ─────────────────────────────────────────────────────────────────────────────
#  STEP 4 — (Optional) UID/GID remapping
#
#  Set PUID and PGID in compose.yml to your host user's UID/GID so that
#  files created inside the container are owned by your host user.
#  This eliminates the classic "root-owned files on the host" problem.
#
#  Typically:
#    PUID: "${UID}"   (Linux/macOS: echo $UID → usually 1000)
#    PGID: "${GID}"   (Linux/macOS: echo $GID → usually 1000)
#
#  Skip this block entirely if PUID/PGID are not set.
# ─────────────────────────────────────────────────────────────────────────────
if [ -n "${PUID:-}" ] && [ -n "${PGID:-}" ]; then
    echo "[entrypoint] Remapping www-data → UID=${PUID} GID=${PGID}..."
    docker-php-serversideup-set-id www-data "${PUID}:${PGID}"

    # Re-apply ownership using the new numeric IDs so mounted files are
    # readable/writable by the remapped user immediately.
    chown -R "${PUID}:${PGID}" \
        "${APP_DIR}/storage" \
        "${APP_DIR}/bootstrap/cache"

    echo "[entrypoint] UID/GID remapped."
fi

# ── STEP 5 — Detect queue worker mode ─────────────────────────────────────
#  If the container is started with queue:work / queue:listen, skip the
#  FrankenPHP/Caddy handoff and run the worker directly as www-data.
# ─────────────────────────────────────────────────────────────────────────────
if [ "$1" = "php" ] && [ "$2" = "artisan" ] && { [ "$3" = "queue:work" ] || [ "$3" = "queue:listen" ]; }; then
    echo "[entrypoint] Queue worker detected — running artisan directly..."
    exec su -s /bin/bash www-data -c "cd ${APP_DIR} && $*"
fi

# ─────────────────────────────────────────────────────────────────────────────
#  STEP 6 — Delegate to the official serversideup entrypoint
#
#  docker-php-serversideup-entrypoint is the real init script for all
#  serversideup/php images. Delegating here preserves:
#    • Caddyfile template rendering (SSL_MODE, CADDY_* env vars)
#    • PHP ini injection (PHP_MEMORY_LIMIT, PHP_OPCACHE_*, …)
#    • All AUTORUN_LARAVEL_* automations (migrate, storage:link, …)
#    • Privilege drop from root → www-data for the FrankenPHP process
#    • The FrankenPHP / Caddy startup itself
#
#  "$@" forwards the CMD arguments inherited from the base image,
#  which is the "frankenphp run --config …" command.
# ─────────────────────────────────────────────────────────────────────────────
echo "[entrypoint] Handing off to docker-php-serversideup-entrypoint..."
exec docker-php-serversideup-entrypoint "$@"
