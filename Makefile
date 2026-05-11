.PHONY: dev-up dev-down dev-clean dev-ports dev-kill-ports dev-cache dev-clear dev-queue dev-db-heal dev-db-backup dev-db-restore dev-test prod-up

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

# ── Database backup ────────────────────────────────────────────────
BACKUP_DIR = .dev_snapshots

dev-db-backup:
	@mkdir -p $(BACKUP_DIR)
	@echo "Backing up dev database..."
	@$(DEV_COMPOSE) exec -T -e PGPASSWORD=$(DB_PASSWORD) db pg_dump -U $(DB_USERNAME) -h localhost -d $(name)_db \
		--clean --if-exists --no-owner --no-privileges \
		| gzip > $(BACKUP_DIR)/mamias_$$(date +%Y%m%d_%H%M%S).sql.gz
	@echo "Backup saved to $(BACKUP_DIR)/"
	@cd $(BACKUP_DIR) && ls -t *.sql.gz 2>/dev/null | tail -n +6 | xargs -r rm --
	@echo "Kept latest 5 snapshots."

# ── Database restore (latest snapshot) ─────────────────────────────
dev-db-restore:
	@LATEST=$$(ls -t $(BACKUP_DIR)/*.sql.gz 2>/dev/null | head -1); \
	if [ -z "$$LATEST" ]; then \
		echo "No backup found in $(BACKUP_DIR)/"; exit 1; \
	fi; \
	echo "Restoring from $$LATEST ..."; \
	gunzip -c "$$LATEST" | $(DEV_COMPOSE) exec -T -e PGPASSWORD=$(DB_PASSWORD) db psql -U $(DB_USERNAME) -h localhost -d $(name)_db -q; \
	echo "Restore complete."

# ── Test with automatic backup ─────────────────────────────────────
dev-test: dev-db-backup
	@echo "Running tests..."
	$(DEV_COMPOSE) exec -T app php artisan test --compact $(if $(FILTER),--filter=$(FILTER))
	@echo "Restoring developer users..."
	$(DEV_COMPOSE) exec -T app php artisan db:seed --class=DeveloperLoginUsersSeeder

prod-up:
	@if [ ! -f .env.production ]; then \
		echo "ERROR: .env.production is missing. Copy .env.production.example first."; \
		exit 1; \
	fi
	@echo "Starting production stack from docker-compose.prod.yml..."
	$(PROD_COMPOSE) up -d --build
