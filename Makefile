.PHONY: menu help dev-env dev-keys dev-up dev-down dev-clean dev-ports dev-kill-ports dev-cache dev-clear dev-queue dev-db-heal dev-db-backup dev-db-restore dev-db-full-restore dev-db-list dev-test prod-env prod-keys prod-up

# Recipes here use bash-isms (read -p, [[ ]]). Without this they run under
# /bin/sh, which is dash on Debian/Ubuntu — where `read -p` is not supported and
# the interactive prompts in prod-env and dev-db-full-restore fail outright.
SHELL := /bin/bash

# Bare `make` opens the picker instead of silently running the first target.
.DEFAULT_GOAL := menu

-include .env
export

DEV_COMPOSE = docker compose --profile dev -f docker-compose.yml

# Every name the dev .env defines. The `export` above hands them all to every
# recipe, and compose gives the shell environment precedence over --env-file,
# so on a machine that also has a dev .env a production command would take
# DB_PASSWORD, APP_KEY, CAP_* ... from it instead of .env.production. `env -u`
# strips them for the production commands only.
DEV_ENV_VARS := $(shell grep -oE '^[A-Za-z_][A-Za-z0-9_]*=' .env 2>/dev/null | tr -d '=')
PROD_COMPOSE = env $(addprefix -u ,$(DEV_ENV_VARS)) docker compose --env-file .env.production -f docker-compose.prod.yml

# ── Interactive picker ─────────────────────────────────────────────
#    Same picker as running `./mamias` directly — that script holds the
#    menu.php → menu.sh fallback, so it's not duplicated here too.
#
#    Both menu.php and menu.sh read the same "##" annotations below, so a
#    newly annotated target shows up in whichever one runs.
menu:
	@./mamias

# Deliberately unannotated: it would be a pointless entry inside the menu it prints.
help:
	@bash menu.sh --list

##@ Setup

# ── Seed .env + apps/.env for local dev ──────────────────────────────
#    Copies both examples, then writes one shared DB_USERNAME/DB_PASSWORD
#    into both — their *.example defaults don't actually agree with each
#    other (root's is spa_rac_admin, apps/'s is admin_mamias), which would
#    otherwise leave host-side artisan unable to authenticate against the
#    container it just provisioned. APP_KEY and the Cap keys are then set up
#    by env-keys.sh (same as `make dev-keys`), shared across both files.
#    DB_HOST/DB_PORT in apps/.env are left exactly as shipped — changing
#    them to db/redis breaks every host-side artisan call (see CLAUDE.md).
dev-env: ## Create and populate .env + apps/.env interactively (local dev)
	@if [ -f .env ]; then \
		echo "ERROR: .env already exists — refusing to overwrite."; \
		echo "Edit it directly, or 'rm .env' first to re-seed."; \
		exit 1; \
	fi
	@if [ -f apps/.env ]; then \
		echo "ERROR: apps/.env already exists — refusing to overwrite."; \
		echo "Edit it directly, or 'rm apps/.env' first to re-seed."; \
		exit 1; \
	fi
	@if [ ! -f .env.example ] || [ ! -f apps/.env.example ]; then \
		echo "ERROR: .env.example or apps/.env.example not found."; exit 1; \
	fi
	@cp .env.example .env
	@cp apps/.env.example apps/.env
	@echo "Seeding .env + apps/.env (press Enter to keep the shown default)..."
	@read -p "DB_USERNAME [spa_rac_admin]: " V; \
		V=$${V:-spa_rac_admin}; \
		sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$$V|" .env; \
		sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$$V|" apps/.env
	@read -p "DB_PASSWORD [spa_rac_2026]: " V; \
		V=$${V:-spa_rac_2026}; \
		sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$$V|" .env; \
		sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$$V|" apps/.env
	@bash env-keys.sh dev
	@echo ""
	@echo ".env + apps/.env seeded. DB_HOST/DB_PORT in apps/.env are left as shipped"
	@echo "(127.0.0.1:5433/6380 — the host-published ports; never point them at db/redis)."
	@echo "Run 'make dev-up' to start the stack."

# ── APP_KEY + Cap CAPTCHA keys, on an existing setup ─────────────────
#    Re-runnable: keeps every value already set unless you choose to replace
#    it. The Cap site key is issued by the dev Cap server through its API, so
#    no trip to the dashboard is needed. See env-keys.sh.
dev-keys: ## Set up APP_KEY and Cap CAPTCHA keys interactively (local dev)
	@bash env-keys.sh dev

##@ Stack

dev-up: ## Start the dev stack (build + up -d)
	@echo "Starting local development stack (dev profile only)..."
	$(DEV_COMPOSE) up -d --build

dev-down: ## Stop the dev stack (keeps volumes)
	@echo "Stopping local development stack (dev profile only)..."
	$(DEV_COMPOSE) down --remove-orphans

# ── Hard clean : containers + volumes + orphans ────────────────────
dev-clean: ##! Hard reset — removes containers AND volumes (drops the database)
	@echo "Hard reset: stopping containers, removing orphans, pruning volumes..."
	$(DEV_COMPOSE) down --remove-orphans --volumes
	@docker container prune -f
	@docker volume prune -f
	@echo "Clean done. Run 'make dev-up' to restart fresh."

##@ Diagnostics

# ── Diagnose port conflicts ──────────────────────────────────────
dev-ports: ## Show published ports and Windows reserved ranges
	@echo "--- Docker containers & published ports ---"
	@docker ps --format "table {{.Names}}\t{{.Ports}}"
	@echo ""
	@echo "--- Host processes listening on mail/db ports ---"
	@ss -tlnp 2>/dev/null | grep -E ':1025|:11025|:8025|:8026|:5432|:5433|:6379|:6380|:7443|:3000' || true
	@echo ""
	@echo "--- Windows/Hyper-V reserved TCP ranges (a published port inside one cannot bind) ---"
	@powershell.exe -NoProfile -Command "netsh interface ipv4 show excludedportrange protocol=tcp" 2>/dev/null || true

# ── Kill wslrelay.exe processes hogging ports (Windows last resort) ─
dev-kill-ports: ##! Kill wslrelay.exe processes holding ports (Windows last resort)
	@echo "Killing wslrelay.exe processes that may be holding ports..."
	@powershell.exe -Command "Get-Process wslrelay -ErrorAction SilentlyContinue | Stop-Process -Force; Write-Host 'Done.'"

##@ Caches

# ── Cache management for dev ───────────────────────────────────────
dev-cache: ## Rebuild route/view/event/filament caches
	@echo "Rebuilding caches (routes, views, events, filament)..."
	$(DEV_COMPOSE) exec app php artisan route:cache
	$(DEV_COMPOSE) exec app php artisan view:cache
	$(DEV_COMPOSE) exec app php artisan event:cache
	$(DEV_COMPOSE) exec app php artisan filament:cache-components
	@echo "All caches rebuilt. Run 'make dev-up' to restart if needed."

dev-clear: ## Clear all caches (fixes stale Filament behaviour)
	@echo "Clearing all caches..."
	$(DEV_COMPOSE) exec app php artisan cache:clear
	$(DEV_COMPOSE) exec app php artisan config:clear
	$(DEV_COMPOSE) exec app php artisan route:clear
	$(DEV_COMPOSE) exec app php artisan view:clear
	$(DEV_COMPOSE) exec app php artisan filament:clear-components
	@echo "All caches cleared."

# ── Queue worker (manual) ──────────────────────────────────────────
dev-queue: ## Run a queue worker in this terminal (blocks)
	@echo "Starting queue worker (keep this terminal open)..."
	$(DEV_COMPOSE) exec app php artisan queue:work --sleep=3 --tries=3

##@ Database

# ── Manual startup guard execution (migrate + seed when needed) ─────
dev-db-heal: ## Run the DB self-heal guard (migrate + seed if needed)
	@echo "Running dev DB self-heal guard..."
	$(DEV_COMPOSE) exec app php artisan app:dev-db-self-heal --no-interaction

# ── Database backup (custom format — supports selective restore) ───
BACKUP_DIR = .dev_snapshots
DB_EXEC = $(DEV_COMPOSE) exec -T -e PGPASSWORD=$(DB_PASSWORD) db

dev-db-backup: ## Snapshot the dev database (keeps the latest 5)
	@mkdir -p $(BACKUP_DIR)
	@echo "Backing up dev database (custom format)..."
	@$(DB_EXEC) pg_dump -U $(DB_USERNAME) -h localhost -d $(name)_db \
		-Fc --no-owner --no-privileges \
		-f /tmp/_mamias_backup.dump
	@$(DEV_COMPOSE) cp db:/tmp/_mamias_backup.dump $(BACKUP_DIR)/mamias_$$(date +%Y%m%d_%H%M%S).dump
	@$(DB_EXEC) rm -f /tmp/_mamias_backup.dump
	@echo "Backup saved to $(BACKUP_DIR)/"
	@cd $(BACKUP_DIR) && ls -t *.dump 2>/dev/null | tail -n +6 | xargs -r rm --
	@echo "Kept latest 5 snapshots."

# ── List available snapshots ───────────────────────────────────────
dev-db-list: ## List available snapshots
	@echo "Available snapshots in $(BACKUP_DIR)/:"
	@ls -lhrt $(BACKUP_DIR)/*.dump 2>/dev/null || echo "  (none)"

# ── Data-only restore (safe — keeps schema, reloads data) ─────────
#    Truncates all app tables then restores data from the dump.
#    Schema (tables, indexes, constraints) is untouched.
#    Usage: make dev-db-restore [FILE=path/to/file.dump]
dev-db-restore: ##! Reload data from a snapshot (truncates tables, keeps schema)
	@LATEST=$$(ls -t $(BACKUP_DIR)/*.dump 2>/dev/null | head -1); \
	TARGET=$${FILE:-$$LATEST}; \
	if [ -z "$$TARGET" ] || [ ! -f "$$TARGET" ]; then \
		echo "No backup found. Run 'make dev-db-backup' first or set FILE=path.dump"; exit 1; \
	fi; \
	echo "Restoring DATA from $$TARGET (schema untouched)..."; \
	$(DEV_COMPOSE) cp "$$TARGET" db:/tmp/_mamias_restore.dump; \
	echo "Truncating all app tables..."; \
	$(DB_EXEC) psql -U $(DB_USERNAME) -h localhost -d $(name)_db -q -c \
		"DO \$$\$$ DECLARE r RECORD; BEGIN FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename NOT IN ('spatial_ref_sys')) LOOP EXECUTE 'TRUNCATE TABLE public.' || quote_ident(r.tablename) || ' CASCADE'; END LOOP; END \$$\$$;"; \
	echo "Loading data..."; \
	$(DB_EXEC) pg_restore -U $(DB_USERNAME) -h localhost -d $(name)_db \
		--data-only --disable-triggers --no-owner --no-privileges \
		--single-transaction \
		/tmp/_mamias_restore.dump; \
	$(DB_EXEC) rm -f /tmp/_mamias_restore.dump; \
	echo "Data restore complete (schema preserved)."

# ── Full restore (destructive — drops and recreates tables) ────────
#    Usage: make dev-db-full-restore [FILE=path/to/file.dump]
dev-db-full-restore: ##! Full restore — DROPS and recreates every table
	@LATEST=$$(ls -t $(BACKUP_DIR)/*.dump 2>/dev/null | head -1); \
	TARGET=$${FILE:-$$LATEST}; \
	if [ -z "$$TARGET" ] || [ ! -f "$$TARGET" ]; then \
		echo "No backup found. Run 'make dev-db-backup' first or set FILE=path.dump"; exit 1; \
	fi; \
	echo ""; \
	echo "WARNING: This will DROP and RECREATE all tables in $(name)_db."; \
	echo "Source: $$TARGET"; \
	echo ""; \
	read -p "Type 'yes' to confirm: " CONFIRM; \
	if [ "$$CONFIRM" != "yes" ]; then \
		echo "Aborted."; exit 1; \
	fi; \
	echo "Full restore from $$TARGET ..."; \
	$(DEV_COMPOSE) cp "$$TARGET" db:/tmp/_mamias_restore.dump; \
	$(DB_EXEC) pg_restore -U $(DB_USERNAME) -h localhost -d $(name)_db \
		--clean --if-exists --no-owner --no-privileges \
		--single-transaction \
		/tmp/_mamias_restore.dump || true; \
	$(DB_EXEC) rm -f /tmp/_mamias_restore.dump; \
	echo "Full restore complete."

##@ Tests

# ── Test with automatic backup/restore ─────────────────────────────
dev-test: dev-db-backup ## Run the suite against mamias_test (snapshots first, restores after)
	@echo "Running tests (using mamias_test database)..."
	@$(DEV_COMPOSE) exec -T -e DB_DATABASE=mamias_test app php artisan test --compact $(if $(FILTER),--filter=$(FILTER)) || true
	@echo "Restoring data after test run..."
	@LATEST=$$(ls -t $(BACKUP_DIR)/*.dump 2>/dev/null | head -1); \
	if [ -n "$$LATEST" ]; then \
		$(DEV_COMPOSE) cp "$$LATEST" db:/tmp/_mamias_restore.dump; \
		$(DB_EXEC) psql -U $(DB_USERNAME) -h localhost -d $(name)_db -q -c \
			"DO \$$\$$ DECLARE r RECORD; BEGIN FOR r IN (SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename NOT IN ('spatial_ref_sys')) LOOP EXECUTE 'TRUNCATE TABLE public.' || quote_ident(r.tablename) || ' CASCADE'; END LOOP; END \$$\$$;" 2>/dev/null; \
		$(DB_EXEC) pg_restore -U $(DB_USERNAME) -h localhost -d $(name)_db \
			--data-only --disable-triggers --no-owner --no-privileges \
			--single-transaction \
			/tmp/_mamias_restore.dump 2>/dev/null; \
		$(DB_EXEC) rm -f /tmp/_mamias_restore.dump; \
		echo "Data restored from $$LATEST"; \
	fi
	@echo "Re-seeding developer users..."
	@$(DEV_COMPOSE) exec -T app php artisan db:seed --class=DeveloperLoginUsersSeeder

# ── Seed .env.production for a first-time production deploy ─────────
#    Copies the example, then fills in SERVER_NAME, APP_URL and the DB /
#    Redis credentials. A blank password answer auto-generates a strong
#    random secret. APP_KEY and the Cap keys are then set up by env-keys.sh
#    (same as `make prod-keys`), which asks the production Cap server to
#    issue the site key. Requires openssl (present on Debian/Plesk by default).
##@ Production

prod-env: ## Create and populate .env.production interactively
	@if [ -f .env.production ]; then \
		echo "ERROR: .env.production already exists — refusing to overwrite."; \
		echo "Edit it directly, or 'rm .env.production' first to re-seed."; \
		exit 1; \
	fi
	@if [ ! -f .env.production.example ]; then \
		echo "ERROR: .env.production.example not found."; exit 1; \
	fi
	@cp .env.production.example .env.production
	@echo "Seeding .env.production (press Enter to keep the example default)..."
	@read -p "SERVER_NAME (e.g. app.example.com): " V; \
		[ -n "$$V" ] && sed -i "s|^SERVER_NAME=.*|SERVER_NAME=$$V|" .env.production || true
	@read -p "APP_URL (e.g. https://app.example.com): " V; \
		[ -n "$$V" ] && sed -i "s|^APP_URL=.*|APP_URL=$$V|" .env.production || true
	@read -p "DB_DATABASE [mamias_db]: " V; \
		[ -n "$$V" ] && sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$$V|" .env.production || true
	@read -p "DB_USERNAME: " V; \
		[ -n "$$V" ] && sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$$V|" .env.production || true
	@read -p "DB_PASSWORD (blank = auto-generate): " V; \
		[ -z "$$V" ] && { V=$$(openssl rand -hex 24); echo "  -> generated DB_PASSWORD"; }; \
		sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$$V|" .env.production
	@read -p "REDIS_PASSWORD (blank = auto-generate): " V; \
		[ -z "$$V" ] && { V=$$(openssl rand -hex 24); echo "  -> generated REDIS_PASSWORD"; }; \
		sed -i "s|^REDIS_PASSWORD=.*|REDIS_PASSWORD=$$V|" .env.production
	@bash env-keys.sh prod
	@echo ""
	@echo ".env.production seeded. Still set MAIL_* to real SMTP values, then run 'make prod-up'."

# ── APP_KEY + Cap CAPTCHA keys, on an existing .env.production ───────
#    Re-runnable: keeps every value already set unless you choose to replace
#    it. Run it on the production host — the site key must come from the
#    production Cap server, which it starts and calls from inside its
#    container (production Cap publishes no port). See env-keys.sh.
prod-keys: ## Set up APP_KEY and Cap CAPTCHA keys interactively (production)
	@bash env-keys.sh prod

prod-up: ##! Build and start the PRODUCTION stack
	@if [ ! -f .env.production ]; then \
		echo "ERROR: .env.production is missing. Run 'make prod-env' to create it."; \
		exit 1; \
	fi
	@echo "Starting production stack from docker-compose.prod.yml..."
	$(PROD_COMPOSE) up -d --build
