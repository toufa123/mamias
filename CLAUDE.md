# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository layout

- `apps/` — Laravel application (all backend/frontend code lives here)
- `apps/CLAUDE.md` — Laravel Boost rules (Filament v5, Pest, Pint, PHP patterns); **read this for all app code changes**
- `AGENTS.md` — architectural decisions and safe-change boundaries; read before touching structure
- `backup/` — PostGIS DB bootstrap input only; do not repurpose

## What this app is

MAMIAS is a marine biodiversity database for Non-Indigenous Species (NIS) in the Mediterranean. Primary UI is a **Filament 5 admin panel at `/mamias`**. `routes/web.php` only serves a welcome view. There is no traditional REST API.

## Commands

All lifecycle commands run from **repo root** via Make:

```bash
make dev-up       # start full dev stack (Docker)
make dev-down     # stop
make dev-clean    # hard reset containers + volumes
make dev-cache    # rebuild routes/views/events/filament caches
make dev-clear    # clear all caches
make dev-queue    # start manual queue worker
```

Run Artisan/tests inside the running container:

```bash
docker compose --profile dev exec app php artisan test --compact
docker compose --profile dev exec app php artisan test --compact --filter=TestName
docker compose --profile dev exec app vendor/bin/pint --dirty --format agent
```

From inside `apps/` (if running locally without Docker):

```bash
composer run dev    # serve + queue:listen + pail + npm run dev (concurrent)
composer run test   # clear config cache + run pest
npm run build       # production asset build
```

## Architecture

**Runtime stack:** FrankenPHP + PostgreSQL/PostGIS + Redis + Mailpit (dev) — all in Docker.

**Panel:** `MamiasPanelProvider` (`apps/app/Providers/Filament/MamiasPanelProvider.php`) is the single source of truth for plugins, auth flow, theme, middleware, and widgets. Panel id/path is `mamias` — do not change without explicit approval.

**Filament resource pattern:** Each resource is split across four concerns:
```
app/Filament/Resources/Users/
├── UserResource.php
├── Schemas/UserForm.php        # static configurator: UserForm::configure(Schema $schema)
├── Tables/UsersTable.php       # static configurator: UsersTable::configure(Table $table)
└── Pages/{Create,Edit,List}User.php
```
Reuse this static configurator pattern for all new resources.

**Domain model:** `User` is the primary model. Profile fields (taxonomic area, subregions, countries, phone, bio) live directly on `users` table — no separate profile model. `name` is derived from `first_name`/`last_name` in `booted()`; do not duplicate that sync.

**Access control:** Spatie roles (`super_admin`, `panel_user`, `user`). Login redirects super_admin/panel_user to panel, others to `/`. Registration auto-assigns `user` role.

**External services (cached):**
- `WormsService` — WoRMS taxonomy API, cache prefix `worms_v2.*`
- `WhatsAppService` — GreenAPI phone validation, E.164 fallback, 7-day cache; returns `false` silently on API failure

## Testing

- Test DB is **SQLite in-memory** (`apps/phpunit.xml`); app runtime is PostgreSQL/PostGIS — watch for dialect differences
- Always `actingAs(User::factory()->create())` before testing Filament panel pages
- Create tests: `php artisan make:test --pest SomeName` (no suite prefix in name)
- Run: `php artisan test --compact` or `--filter=name`

## Key constraints

- Do not add new top-level folders under `apps/` without approval
- Do not change dependencies without approval
- Do not use `docker-compose.yml` for production — use `docker-compose.prod.yml` + `.env.production`
- Stale Filament behavior is almost always cache — run `make dev-cache`
- After any PHP file edit, run Pint: `vendor/bin/pint --dirty --format agent`

## graphify

This project has a graphify knowledge graph at graphify-out/.

Rules:
- Before answering architecture or codebase questions, read graphify-out/GRAPH_REPORT.md for god nodes and community structure
- If graphify-out/wiki/index.md exists, navigate it instead of reading raw files
- After modifying code files in this session, run `graphify update .` to keep the graph current (AST-only, no API cost)
