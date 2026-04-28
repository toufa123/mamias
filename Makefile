.PHONY: dev-up dev-down dev-clean dev-ports dev-kill-ports dev-cache dev-clear dev-queue prod-up

DEV_COMPOSE = docker compose --profile dev -f docker-compose.yml
PROD_COMPOSE = docker compose --env-file .env.production -f docker-compose.prod.yml

dev-up:
	@echo "Starting local development stack (dev profile only)..."
	$(DEV_COMPOSE) up -d --build

dev-down:
	@echo "Stopping local development stack (dev profile only)..."
	$(DEV_COMPOSE) down --remove-orphans

# ── Hard clean : containers + volumes + orphans ────────────────────
# Use this when you get port-conflict errors or zombie containers.
dev-clean:
	@echo "Hard reset: stopping containers, removing orphans, pruning volumes..."
	$(DEV_COMPOSE) down --remove-orphans --volumes
	@docker container prune -f
	@docker volume prune -f
	@echo "Clean done. Run 'make dev-up' to restart fresh."

# ── Diagnose port conflicts (Windows) ──────────────────────────────
dev-ports:
	@echo "--- Docker containers & published ports ---"
	@docker ps --format "table {{.Names}}\t{{.Ports}}"
	@echo ""
	@echo "--- Host processes listening on mail/db ports ---"
	@netstat -ano | findstr ":1025\|:11025\|:8025\|:8026\|:5432\|:54321\|:6379\|:443" || true

# ── Kill wslrelay.exe processes hogging ports (Windows last resort) ─
dev-kill-ports:
	@echo "Killing wslrelay.exe processes that may be holding ports..."
	@powershell -Command "Get-Process wslrelay -ErrorAction SilentlyContinue | Stop-Process -Force; Write-Host 'Done.'"

# ── Cache management for dev ───────────────────────────────────────
#  Rebuild caches after code changes. Config cache is kept false in
#  compose.yml because it is painful during active config editing.
dev-cache:
	@echo "Rebuilding caches (routes, views, events, filament)..."
	$(DEV_COMPOSE) exec app php artisan route:cache
	$(DEV_COMPOSE) exec app php artisan view:cache
	$(DEV_COMPOSE) exec app php artisan event:cache
	$(DEV_COMPOSE) exec app php artisan filament:cache-components
	@echo "All caches rebuilt. Run 'make dev-up' to restart if needed."

#  Clear all caches when things feel stale or after heavy refactoring.
dev-clear:
	@echo "Clearing all caches..."
	$(DEV_COMPOSE) exec app php artisan cache:clear
	$(DEV_COMPOSE) exec app php artisan config:clear
	$(DEV_COMPOSE) exec app php artisan route:clear
	$(DEV_COMPOSE) exec app php artisan view:clear
	$(DEV_COMPOSE) exec app php artisan filament:clear-components
	@echo "All caches cleared."

# ── Queue worker (manual) ──────────────────────────────────────────
#  If the dedicated queue container is stopped, run this in a separate
#  terminal to process background jobs (emails, imports, etc.).
dev-queue:
	@echo "Starting queue worker (keep this terminal open)..."
	$(DEV_COMPOSE) exec app php artisan queue:work --sleep=3 --tries=3

prod-up:
	@if [ ! -f .env.production ]; then \
		echo "ERROR: .env.production is missing. Copy .env.production.example first."; \
		exit 1; \
	fi
	@echo "Starting production stack from docker-compose.prod.yml..."
	$(PROD_COMPOSE) up -d --build
