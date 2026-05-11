# ============================================================
#  Laravel 13 — FrankenPHP custom image
#  Base: serversideup/php:8.5-frankenphp (Debian Trixie)
# ============================================================

FROM serversideup/php:8.5-frankenphp

LABEL maintainer="Atef OUERGHI"
LABEL description="MAMIAS Laravel 13 + FrankenPHP + PostGIS support"

# ── All installation work must run as root ────────────────────────────
USER root

# ── System dependencies needed by PHP extensions ─────────────────────
RUN apt-get update \
    && apt-get install -y \
        libzip-dev \
        libxml2-dev \
        ca-certificates \
        curl \
        gnupg \
        lsb-release \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://download.docker.com/linux/debian/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null \
    && apt-get update \
    && apt-get install -y docker-ce-cli \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── Node.js for frontend asset build ─────────────────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ────────────────────────────────────────────────────
# install-php-extensions (bundled in every serversideup/php image) uses
# the mlocati/docker-php-extension-installer under the hood.
#
# Already included in serversideup/php:8.5-frankenphp (no need to list):
#   bcmath, ctype, curl, dom, exif, fileinfo, iconv, intl, mbstring,
#   opcache, openssl, pcntl, pdo, pdo_mysql, pdo_pgsql, pdo_sqlite,
#   posix, session, simplexml, sockets, sodium, tokenizer, xml,
#   xmlreader, xmlwriter, xsl, zip
#
# We add only what is NOT pre-installed:
# NOTE: imagick is currently NOT supported on PHP 8.5
RUN install-php-extensions soap intl bcmath redis gd

# ── Build-time toggle: "dev" includes require-dev, "prod" excludes them ──
ARG APP_BUILD=dev

# ── Application code ─────────────────────────────────────────────────
WORKDIR /var/www/html
COPY --chown=www-data:www-data apps/ /var/www/html/

# ── Install PHP dependencies ─────────────────────────────────────────
RUN if [ ! -d "vendor" ]; then \
      if [ "$APP_BUILD" = "prod" ]; then \
        composer install --no-dev --optimize-autoloader --no-interaction --no-scripts; \
      else \
        composer install --optimize-autoloader --no-interaction --no-scripts; \
      fi; \
    fi
RUN php artisan package:discover --ansi || true

# ── Silence "no files matching import glob" Caddy warning ────────────
RUN mkdir -p /etc/frankenphp/caddyfile.d \
    && frankenphp fmt --overwrite /etc/frankenphp/Caddyfile

# ── Build frontend assets ────────────────────────────────────────────
RUN npm ci --ignore-scripts && npm run build && rm -rf node_modules

# ── Runtime secrets ──────────────────────────────────────────────────
ENV APP_KEY=""

# ── Entrypoint wrapper ────────────────────────────────────────────────
COPY --chmod=755 entrypoint.sh /usr/local/bin/docker-entrypoint-laravel

# ── Override entrypoint ───────────────────────────────────────────────
ENTRYPOINT ["/usr/local/bin/docker-entrypoint-laravel"]

# Restore the base image CMD so the delegated entrypoint starts FrankenPHP.
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
