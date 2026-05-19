.PHONY: dev-up dev-down dev-clean dev-ports dev-kill-ports dev-cache dev-clear dev-queue dev-db-heal dev-db-backup dev-db-restore dev-db-full-restore dev-db-list dev-test prod-up

-include .env
export

DEV_COMPOSE = docker compose --profile dev -f docker-compose.yml
PROD_COMPOSE = docker compose --env-file .env.production -f docker-compose.prod.yml

dev-up:
	@echo "Starting local development stack (dev profile only)..."
	$(DEV_COMPOSE) up -d --build

dev-down:
	@echo "Stopping local development stack (dev profile only)..."
	$(DEV_COMPOSE) down --remove-orphans

# ── Hard clean : containers + volumes + orphans ────────────────────
dev-clean:
	@echo "Hard reset: stopping containers, removing orphans, pruning volumes..."
	$(DEV_COMPOSE) down --remove-orphans --volumes
	@docker container prune -f
	@docker volume prune -f
	@echo "Clean done. Run 'make dev-up' to restart fresh."

# ── Diagnose port conflicts ──────────────────────────────────────
dev-ports:
	@echo "--- Docker containers & published ports ---"
	@docker ps --format "table {{.Names}}\t{{.Ports}}"
	@echo ""
	@echo "--- Host processes listening on mail/db ports ---"
	@ss -tlnp 2>/dev/null | grep -E ':1025|:11025|:8025|:8026|:5432|:54321|:6379|:443' || true

# ── Kill wslrelay.exe processes hogging ports (Windows last resort) ─
dev-kill-ports:
	@echo "Killing wslrelay.exe processes that may be holding ports..."
	@powershell.exe -Command "Get-Process wslrelay -ErrorAction SilentlyContinue | Stop-Process -Force; Write-Host 'Done.'"

# ── Cache management for dev ───────────────────────────────────────
dev-cache:
	@echo "Rebuilding caches (routes, views, events, filament)..."
	$(DEV_COMPOSE) exec app php artisan route:cache
	$(DEV_COMPOSE) exec app php artisan view:cache
	$(DEV_COMPOSE) exec app php artisan event:cache
	$(DEV_COMPOSE) exec app php artisan filament:cache-components
	@echo "All caches rebuilt. Run 'make dev-up' to restart if needed."

dev-clear:
	@echo "Clearing all caches..."
	$(DEV_COMPOSE) exec app php artisan cache:clear
	$(DEV_COMPOSE) exec app php artisan config:clear
	$(DEV_COMPOSE) exec app php artisan route:clear
	$(DEV_COMPOSE) exec app php artisan view:clear
	$(DEV_COMPOSE) exec app php artisan filament:clear-components
	@echo "All caches cleared."

# ── Queue worker (manual) ──────────────────────────────────────────
dev-queue:
	@echo "Starting queue worker (keep this terminal open)..."
	$(DEV_COMPOSE) exec app php artisan queue:work --sleep=3 --tries=3

# ── Manual startup guard execution (migrate + seed when needed) ─────
dev-db-heal:
	@echo "Running dev DB self-heal guard..."
	$(DEV_COMPOSE) exec app php artisan app:dev-db-self-heal --no-interaction

# ── Database backup (custom format — supports selective restore) ───
BACKUP_DIR = .dev_snapshots
DB_EXEC = $(DEV_COMPOSE) exec -T -e PGPASSWORD=$(DB_PASSWORD) db

dev-db-backup:
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
dev-db-list:
	@echo "Available snapshots in $(BACKUP_DIR)/:"
	@ls -lhrt $(BACKUP_DIR)/*.dump 2>/dev/null || echo "  (none)"

# ── Data-only restore (safe — keeps schema, reloads data) ─────────
#    Truncates all app tables then restores data from the dump.
#    Schema (tables, indexes, constraints) is untouched.
#    Usage: make dev-db-restore [FILE=path/to/file.dump]
dev-db-restore:
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
dev-db-full-restore:
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

# ── Test with automatic backup/restore ─────────────────────────────
dev-test: dev-db-backup
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

prod-up:
	@if [ ! -f .env.production ]; then \
		echo "ERROR: .env.production is missing. Copy .env.production.example first."; \
		exit 1; \
	fi
	@echo "Starting production stack from docker-compose.prod.yml..."
	$(PROD_COMPOSE) up -d --build
