# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Tech Stack

- Laravel + Filament v5 (use v5 API: check class namespaces like `Filament\Resources\Pages\Concerns\Tab`, avoid deprecated methods like `TextColumn::italic`, `Action::columns`, `minCharactersToSearch`)
- Livewire (do NOT use HtmlString for JS formatters - it cannot be serialized)
- Tailwind CSS 4 with Vite 8 (ensure `@vite` directive is in layouts). daisyUI is not installed — don't reintroduce it without approval (it stalled on Vite 8/Rolldown before)
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
- **Run `php artisan make:*` as `www-data`, not root.** `docker compose exec app …` runs as root, so generated files land `root:root` while the rest of the tree is `www-data` (uid 1000 = the host user). The host then cannot edit them — writes fail with `EPERM` over the `\\WSL$` share. Use `docker compose --profile dev exec -u www-data app php artisan make:…`, or `chown -R www-data:www-data` the generated paths afterwards.
- **Translations live in `apps/lang/`, and `apps/resources/lang/` must not exist.** `Application::bindPathsInContainer()` picks `resources/lang` whenever that directory is present and only falls back to `lang/` when it is absent — so an empty `resources/lang/` (a package publishing to the pre-Laravel-9 location will create one) silently orphans every file in `lang/`. Nothing errors: package defaults keep resolving, app overrides are ignored, and a key that exists *only* in an override renders as its raw dotted name in the UI. If you see something like `filament-actions::import.modal.actions.download_example_xlsx.label` on screen, check `app('translation.loader')` paths before touching the lang file — it is almost certainly right. `useLangPath()` exists on `Application` but not on the `ApplicationBuilder`, so there is nothing to pin in `bootstrap/app.php`; keeping `resources/lang/` deleted is the fix.

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
docker compose --profile dev exec app npm run format    # Prettier (Blade + Tailwind class order) over resources/
docker compose --profile dev exec app composer analyse   # Larastan level 5; new errors fail, phpstan-baseline.neon holds the old ones
```

From inside `apps/` (if running locally without Docker):

```bash
composer run dev    # serve + queue:listen + pail + npm run dev (concurrent)
composer run test   # clear config cache + run pest
```

### Asset builds — container only, and cache views first

```bash
docker compose --profile dev exec app php artisan view:cache
docker compose --profile dev exec app npm run build
```

**Never run `npm install` / `npm run build` from the Windows side.** `node_modules`
is bind-mounted, and npm installs the platform's native binaries into it. A
Windows install leaves `node_modules/@rolldown/binding-win32-x64-msvc` and no
Linux binding, which breaks the build *inside the container* two ways: the
`.bin/vite` shim loses its exec bit (`sh: 1: vite: Permission denied`) and
rolldown cannot load `@rolldown/binding-linux-x64-gnu`. Both failures exit
non-zero but leave `public/build` intact, so the site silently keeps serving the
previous bundle and nothing appears to change. If it happens, recover with
`docker compose --profile dev exec app npm ci`.

**`view:cache` before `npm run build`.** `resources/css/app.css` has
`@source '../../storage/framework/views/*.php'`, so Tailwind scans compiled
Blade to find classes that only exist in vendor and Layup CMS output. Building
after a `view:clear` silently purges them — the bundle drops from ~108 kB to
~80 kB and utilities go missing on pages nobody rebuilt.

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

**Access control:** Spatie roles (`super_admin`, `scientist`, `user`). Login redirects super_admin/scientist to panel, others to `/`. Registration auto-assigns `user` role.

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
