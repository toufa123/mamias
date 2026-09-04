# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Tech Stack

- Laravel + Filament v5 (use v5 API: check class namespaces like `Filament\Resources\Pages\Concerns\Tab`, avoid deprecated methods like `TextColumn::italic`, `Action::columns`, `minCharactersToSearch`)
- Livewire (do NOT use HtmlString for JS formatters - it cannot be serialized)
- Tailwind + daisyUI with Vite (ensure `@vite` directive is in layouts; be cautious with Vite 8/Rolldown + daisyUI purging)
- Docker-based dev environment (Pint, psql, and other tools run inside containers)

## Working Style

- Prefer direct action on the specified file over broad codebase exploration via the Agent tool unless I explicitly ask for an audit.
- When I name a file or feature, edit it directly; don't spawn exploratory sub-agents first.
- Skip planning phases for small, well-scoped changes — just implement.

## Environment

**Service hostnames differ by where the code runs. Do not "fix" one context by breaking the other.**

- Containers get `DB_HOST=db` / `REDIS_HOST=redis` from the `environment:` block in `docker-compose.yml`. That block always wins: Laravel's dotenv is immutable and never overwrites an already-set environment variable, so `apps/.env` is *ignored* inside containers.
- `apps/.env` is therefore only ever read by host-side runs (artisan/tests from the WSL shell, IDE runners). It must point at `127.0.0.1` plus the **host-published** ports — currently `5433` for Postgres and `6380` for Redis. Setting it to `db`/`redis` makes every host-side artisan call fail at boot: the cookie-consent provider calls `Cache::rememberForever()` during `boot()`, so it dies before any command runs.
- **The stack is hostname-agnostic — keep it that way.** `CADDY_HTTP_SERVER_ADDRESS`/`CADDY_HTTPS_SERVER_ADDRESS` are bare schemes with no host, so Caddy serves any `Host`: `127.0.0.1`, `localhost`, `mamias.local`, `dev.mamias.org`, anything. Putting a hostname in them re-pins the stack to one name. `SERVER_NAME` is *not* referenced by the image's Caddyfile and changes nothing. Live requests build URLs from the request host (`trustProxies` forwards `X-Forwarded-Host`); `APP_URL` only matters where there is no request (queued mail, notifications, artisan).
- Two things do stay host-bound, both overridable from the root `.env`: `DEV_SSL_HOSTNAMES` (names baked into the self-signed cert by `ssl-sans.sh` — add any hostname you browse to) and `APP_URL`. Browser-facing URLs in app code must stay root-relative — `CAP_PUBLIC_URL=/cap` is proxied by Caddy to the cap container, so an absolute value there breaks the CAPTCHA under every other hostname.
- Host port choice: keep published ports **below 50000**. Windows/Hyper-V reserves large blocks of the ephemeral range (`netsh interface ipv4 show excludedportrange protocol=tcp`); the old `54321` landed inside one and the db container could no longer bind it.
- To tell which context an error came from, check the paths in the stack trace: `/var/www/html/...` is the container, `/home/toufa/...` is the host.
- Fix root config issues before falling back to reseeding.
- Pint, psql, artisan normally run via Docker — if Docker is unavailable, surface that immediately rather than retrying.
- After `composer require` restarts containers, volume mounts may need to be re-verified.
- The queue container runs artisan directly and never starts Caddy, so it cannot use the base image's HTTP healthcheck — `docker-compose.yml` overrides it with a worker-process + Redis probe. Don't remove that override; the container will read "unhealthy" while working fine.

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

- Test DB is the dedicated PostgreSQL database **`mamias_test`** (`apps/phpunit.xml`) — the same engine as runtime, because the schema uses PostGIS types SQLite cannot express. Only the database *name* is pinned; host/port/credentials follow the environment, so the suite runs both in the container and from the host.
- `Tests\TestCase::setUpTraits()` aborts if the active database name does not end in `_test`, so a misconfiguration can never wipe the dev data.
- `tests/Pest.php` applies `RefreshDatabase` (transaction per test) and seeds a baseline: roles, developer-login users, Layup home/about pages. **Attach `beforeEach` to the `pest()` chain** — a bare top-level `beforeEach()` in `Pest.php` is silently never executed.
- `phpunit.xml` blanks `CAP_SITE_KEY`/`CAP_SECRET_KEY` so the CAPTCHA takes its local/testing bypass, and sets `SHIELD_SUPER_ADMIN_VIA_GATE=true` so `super_admin` passes authorization via `Gate::before` rather than permission rows that `RefreshDatabase` truncates.
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
