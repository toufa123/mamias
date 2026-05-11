# ============================================================
#  Laravel 13 — FrankenPHP custom image
#  Base: serversideup/php:8.5-frankenphp (Debian Bookworm)
# ============================================================

FROM serversideup/php:8.5-frankenphp

LABEL maintainer="Atef OUERGHI"
LABEL description="MAMIAS Laravel 13 + FrankenPHP + PostGIS support"

# ── All installation work must run as root ────────────────────────────
USER root

# ── System dependencies needed by PHP extensions ─────────────────────
# docker-php-serversideup-dep-install-debian is the official helper
# that silently skips on Alpine, handy if you ever switch base OS tags.
RUN docker-php-serversideup-dep-install-debian \
        libzip-dev \
        libxml2-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ────────────────────────────────────────────────────
# install-php-extensions (bundled in every serversideup/php image) uses
# the mlocati/docker-php-extension-installer under the hood.
#
# Already included in serversideup/php:8.5-frankenphp (no need to list):
#   bcmath, ctype, curl, dom, exif, fileinfo, gd, iconv, intl, mbstring,
#   opcache, openssl, pcntl, pdo, pdo_mysql, pdo_pgsql, pdo_sqlite,
#   posix, session, simplexml, sockets, sodium, tokenizer, xml,
#   xmlreader, xmlwriter, xsl, zip
#
# We add only what is NOT pre-installed:
# NOTE: imagick is currently NOT supported on PHP 8.5
RUN install-php-extensions soap intl bcmath redis

# ── Application code ─────────────────────────────────────────────────
# Keep a complete Laravel app inside the image so runtime does not
# depend on host bind-mount visibility.
WORKDIR /var/www/html
COPY --chown=www-data:www-data apps/ /var/www/html/

# ── Runtime secrets ──────────────────────────────────────────────────
# Default placeholders for runtime secrets. These should be overridden
# via Docker Compose or container orchestration at runtime.
ENV APP_KEY=""

# ── Entrypoint wrapper ────────────────────────────────────────────────
# Copied at build time; runtime execution is handled by compose.yml
# (container starts as root so the script can chown mounted volumes).
COPY --chmod=755 entrypoint.sh /usr/local/bin/docker-entrypoint-laravel

# ── Override entrypoint ───────────────────────────────────────────────
# Our wrapper runs first (as root), fixes permissions, then delegates to
# docker-php-serversideup-entrypoint which handles:
#   • Environment variable → php.ini / Caddyfile substitution
#   • AUTORUN_LARAVEL_* (migrate, storage:link, optimize, …)
#   • Dropping privileges and starting FrankenPHP
#
# Set a custom entrypoint wrapper.
ENTRYPOINT ["/usr/local/bin/docker-entrypoint-laravel"]

# Once ENTRYPOINT is overridden, we must explicitly restore the base image CMD.
# Without this, the delegated entrypoint receives an empty command and exits,
# causing a restart loop.
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]