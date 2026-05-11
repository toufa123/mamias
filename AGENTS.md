# AGENTS.md

## Scope and precedence
- This root guide covers the whole repo; Laravel app code lives in `apps/`.
- For code changes inside `apps/`, follow `apps/AGENTS.md` first (it contains Boost/Filament-specific rules).
- Do not use `docker-compose.yml` for production; use `docker-compose.prod.yml` + `.env.production` (`README.md`).

## Big picture architecture
- Runtime is Docker-first: FrankenPHP app + dedicated queue worker + PostGIS + Redis + Mailpit (`docker-compose.yml`).
- Main product surface is Filament panel at `/mamias`, not `routes/web.php` (which only serves `welcome`).
- App boot wires only web/console routes plus health route `/up` (`apps/bootstrap/app.php`).
- Filament panel configuration is centralized in `apps/app/Providers/Filament/MamiasPanelProvider.php` (plugins, auth flow, theme, middleware, widgets).
- Role model is core to access flow: `User` uses Spatie roles, login redirects `super_admin`/`panel_user` to panel, others to `/` (`apps/app/Filament/Pages/Auth/Login.php`).

## Data and service boundaries
- User profile is the main domain model; extra profile/taxonomy fields are on `users` table (`apps/database/migrations/2026_04_24_000000_add_profile_fields_to_users_table.php`).
- Forms call app services directly for external data/validation:
  - WoRMS taxonomy API via `WormsService` (cached keys `worms_v2.*`) (`apps/app/Services/WormsService.php`).
  - GreenAPI WhatsApp check via `WhatsAppService` with E.164 fallback and 7-day cache (`apps/app/Services/WhatsAppService.php`, `apps/config/services.php`).
- Queue-backed behaviors are expected in dev/prod; keep a worker running (`queue` service or `make dev-queue`).

## Project-specific coding patterns
- Filament v5 resources are split by concern: `Resource` + `Schemas/*` + `Tables/*` + `Pages/*` (example: `apps/app/Filament/Resources/Users/*`).
- Reuse the static configurator pattern: `UserForm::configure(Schema $schema)` and `UsersTable::configure(Table $table)`.
- Model metadata uses PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) and casts in `casts()` (`apps/app/Models/User.php`).
- `User` name is derived from `first_name`/`last_name` in `booted()`; avoid duplicating name-sync logic elsewhere.
- Registration assigns default `user` role in custom Filament register page (`apps/app/Filament/Pages/Auth/Register.php`).

## Developer workflows (non-obvious but critical)
- Preferred local lifecycle uses Make targets from repo root:
  - `make dev-up` / `make dev-down`
  - `make dev-cache` / `make dev-clear`
  - `make dev-queue`
- App container entrypoint preps permissions and runs `php artisan filament:cache-components`; stale Filament behavior is often cache-related (`entrypoint.sh`).
- Test command in container: `docker compose --profile dev exec app php artisan test`.
- Test environment uses in-memory SQLite (`apps/phpunit.xml`), while app runtime uses PostgreSQL/PostGIS; watch for DB-specific query differences.
- Frontend assets are Vite-based, including panel theme `resources/css/filament/mamias/theme.css` (`apps/vite.config.js`).

## Safe change boundaries
- Keep new backend code under existing Laravel folders in `apps/` (do not add new top-level app roots).
- Preserve panel id/path (`id('mamias')`, `path('mamias')`) unless a task explicitly requests URL/auth migration.
- Treat `backup/` as DB bootstrap input for PostGIS container; do not repurpose it for app uploads.

