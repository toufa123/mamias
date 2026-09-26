<p align="center">
  <img src="/apps/public/images/Logoweb.png" alt="MAMIAS Logo" width="180">
</p>
<p align="center">
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT"></a>
  <a href="#"><img src="https://img.shields.io/badge/version-1.0.0-blue.svg" alt="Version: 1.0.0"></a>
  <a href="https://www.php.net"><img src="https://img.shields.io/badge/PHP-8.3+-777BB4.svg?logo=php&logoColor=white" alt="PHP: 8.3+"></a>
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-13.0-FF2D20.svg?logo=laravel&logoColor=white" alt="Laravel: 13.0"></a>
  <a href="https://filamentphp.com"><img src="https://img.shields.io/badge/Filament-5.0-F1B024.svg?logo=filament&logoColor=white" alt="Filament: 5.0"></a>
  <a href="https://postgis.net"><img src="https://img.shields.io/badge/PostGIS-336791.svg?logo=postgresql&logoColor=white" alt="PostGIS"></a>
</p>

<p align="center"><em>Marine Mediterranean Alien Species Database</em></p>

---

**MAMIAS** (Marine Mediterranean Alien Species Database) is a scientific web application for managing, cataloguing, and disseminating data on Non-Indigenous Species (NIS) in the Mediterranean Sea.

It serves researchers, taxonomists, and marine biologists tracking NIS taxonomy, geographic distribution, introduction events, invasion pathways, and associated scientific literature.

## Key Features

- **Taxonomic Catalogue** — CRUD for marine species with automatic **WoRMS** (World Register of Marine Species) synchronization, bulk fetch, and data normalization.
- **Introduction Event Tracking** — Record NIS introduction events with year, country, establishment success, and **CBD pathway** classification.
- **Mediterranean Subregion Monitoring** — Track species arrival and establishment per **EcAp** subregion (WMED, CMED, ADRIA, EMED).
- **Pathway Analysis** — Classify introduction pathways by CBD category/subcategory and pathway type.
- **Literature Management** — Bibliographic reference management with **Crossref DOI** metadata auto-retrieval and auto-generated reference codes.
- **Dashboard Analytics** — Statistical widgets (species counts, kingdom/phylum/environment distribution charts) via Filament ECharts.
- **Bot Protection** — Proof-of-work CAPTCHA on login and registration via self-hosted Cap Standalone, alongside honeypot spam protection.
- **Import/Export** — Bulk data operations via Excel/CSV with session-based import tracking and error reporting.
- **User Management** — Multi-role RBAC (super_admin, scientist, user) via Spatie Permission & Filament Shield.
- **System Health** — Real-time health checks, backup management, and command runner from the admin panel.

For the full Software Requirements Specification, see **[requirements.md](requirements.md)**.

## Tech Stack

| Layer            | Technology             | Badge |
|------------------|------------------------|-------|
| **Backend**      | PHP 8.3+ / Laravel 13  | [![PHP: 8.3+](https://img.shields.io/badge/PHP-8.3+-777BB4.svg?logo=php)](https://www.php.net) [![Laravel: 13.0](https://img.shields.io/badge/Laravel-13.0-FF2D20.svg?logo=laravel)](https://laravel.com) |
| **Admin Panel**  | Filament 5.0           | [![Filament: 5.0](https://img.shields.io/badge/Filament-5.0-F1B024.svg?logo=filament)](https://filamentphp.com) |
| **Database**     | PostgreSQL + PostGIS   | [![PostGIS](https://img.shields.io/badge/PostGIS-336791.svg?logo=postgresql)](https://postgis.net) |
| **Cache/Queue**  | Redis 7+               | [![Redis: 7+](https://img.shields.io/badge/Redis-7+-DC382D.svg?logo=redis)](https://redis.io) |
| **Frontend**     | Vite 8 + Tailwind CSS 4.3 | [![Vite: 8.0](https://img.shields.io/badge/Vite-8.0-646CFF.svg?logo=vite)](https://vitejs.dev) [![Tailwind CSS: 4.2](https://img.shields.io/badge/Tailwind_CSS-4.2-38B2AC.svg?logo=tailwind-css)](https://tailwindcss.com) |
| **Runtime**      | Docker + FrankenPHP    | [![Docker](https://img.shields.io/badge/Docker-2496ED.svg?logo=docker&logoColor=white)](https://www.docker.com) |

## Architecture

The primary interface is the **Filament admin panel** at **`/mamias`**. There is no traditional REST API — `routes/web.php` only serves a welcome view.

### Domain Model

```
Taxon (taxas)
├── IntroEventRecord ──→ Literature
│   ├── SubregionRecord (EcAp subregion data)
│   └── PathwayRecord   (CBD pathway classification)
└── WoRMS sync (WormsService → TaxonNormalizer)

User (users)
└── Profile fields (title, phone, WhatsApp, country, taxonomic area, subregions, bio)
└── Roles: super_admin | scientist | user (Spatie)
```

### Filament Resources

| Resource | Model | Description |
|----------|-------|-------------|
| **TaxonResource** | Taxon | MAMIAS Catalogue — species with WoRMS sync, soft deletes |
| **IntroEventRecordResource** | IntroEventRecord | Introduction events with subregion & pathway records |
| **LiteratureResource** | Literature | Scientific references with DOI auto-fetch |
| **OccurrenceResource** | Occurrence | Species occurrence reports — density, extent, single-point location, moderation |
| **NisSuggestionResource** | NisSuggestion | Public NIS suggestions awaiting review |
| **UserResource** | User | User management with role assignment |

### Backend Services

| Service | Purpose |
|---------|---------|
| `WormsService` | WoRMS REST API integration (taxonomy lookup, bulk phyla fetch) |
| `TaxonService` | Core taxon operations, WoRMS refresh orchestration |
| `TaxonNormalizer` | Data normalization for taxon records |
| `TaxonStateHelper` | Taxon state management utilities |
| `EasinService` | EASIN (European Alien Species Information Network) integration |
| `DoiMetadataService` | Crossref DOI metadata resolution for literature |
| `GbifService` | GBIF occurrence/taxonomy lookups |
| `WhatsAppService` | GreenAPI WhatsApp phone validation with E.164 fallback |
| `CapService` | Proof-of-work CAPTCHA token verification via Cap Standalone |

### Project Structure

```
mamias/
├── apps/                        # Laravel application
│   ├── app/
│   │   ├── Enums/               # 18 enums (NisStatus, Subregion, CbdPathway*, Coverage*, etc.)
│   │   ├── Filament/
│   │   │   ├── Resources/       # 6 resources (Taxon, IntroEvent, Literature, Occurrence, NisSuggestion, User)
│   │   │   ├── Widgets/         # 11 dashboard widgets (stats, charts, WoRMS progress)
│   │   │   └── Pages/           # Dashboard, HealthCheck, BackupManager, Auth/*
│   │   ├── Models/              # 8 models (User, Taxon, Literature, IntroEventRecord, SubregionRecord, PathwayRecord, Occurrence, NisSuggestion)
│   │   └── Services/            # 9 services (Worms, Taxon*, Easin, Gbif, DOI, WhatsApp, Cap)
│   ├── database/migrations/     # 48 migrations (PostGIS, taxas, literatures, intro events, occurrences, imports/exports, RBAC, health)
│   ├── resources/               # Blade views & Filament theme
│   └── tests/                   # 39 test files (Pest PHP — unit + feature)
├── backups/                     # DB backup dumps
├── docker-compose.yml           # Development stack (8 services)
├── docker-compose.prod.yml      # Production stack
├── Dockerfile                   # FrankenPHP optimized image
├── Makefile                     # Dev/prod lifecycle commands (annotated; drives the menu)
├── menu.php                     # Interactive picker (laravel/prompts) — preferred
├── menu.sh                      # Interactive picker fallback (POSIX, no dependencies)
└── entrypoint.sh                # Container permissions & Filament cache
```

---

## Quick Start (Local Development)

### Prerequisites

- Docker & Docker Compose
- `make` available on your system
- Host ports available: `7443` (HTTPS), `5433` (PostGIS), `6380` (Redis), `8026` (Mailpit), `3000` (Cap)
  - All are bound to `127.0.0.1` only. Keep them below `50000` — Windows/Hyper-V reserves large blocks of the ephemeral range and a container cannot bind a reserved port.

### Installation

1. **Clone the repository:**
   ```bash
   git clone <repo-url> mamias
   cd mamias
   ```

2. **Prepare environment (interactive):**
   ```bash
   make dev-env
   ```
   Creates `.env` and `apps/.env`, asks for the database credentials, generates
   `APP_KEY`, and sets up the Cap CAPTCHA keys (next step) — the same menu entry is
   available from plain `make`.

3. **Start the stack:**
   ```bash
   make dev-up
   ```

4. **Cap CAPTCHA keys:**
   Cap is a self-hosted proof-of-work CAPTCHA that protects login and registration forms.
   `make dev-env` already configures it: it generates `CAP_ADMIN_KEY` and has your local
   Cap server issue `CAP_SITE_KEY` / `CAP_SECRET_KEY` through its API — no dashboard step.
   To set or rotate them later on an existing setup:
   ```bash
   make dev-keys
   ```
   A site key only works with the Cap server that issued it, so each machine gets its own.
   The stack refuses to start while any of the three is empty.

4. **Configure local domain:**
   Add `mamias.local` to your hosts file:
   - **Windows (PowerShell admin):** `Add-Content C:\Windows\System32\drivers\etc\hosts "127.0.0.1 mamias.local"`
   - **Linux/macOS:** `echo "127.0.0.1 mamias.local" | sudo tee -a /etc/hosts`

### Access

| Service             | URL                                                         |
|---------------------|-------------------------------------------------------------|
| Admin Panel         | [https://mamias.local/mamias](https://mamias.local/mamias)  |
| Health Check        | [https://mamias.local/up](https://mamias.local/up)          |
| Mailpit (email UI)  | [http://localhost:8026](http://localhost:8026)               |

*Note: Accept the self-signed certificate on first visit.*

### Interactive Menu

Run `make` with no target and an interactive picker opens — it is the Makefile's
`.DEFAULT_GOAL`, so a bare `make` never silently fires the first target.

```bash
make          # interactive picker
make help     # flat list, no picker
```

Two renderers back it, and the Makefile picks whichever can run:

| Renderer | Used when | Interface |
|----------|-----------|-----------|
| `menu.php` | Preferred — needs `apps/vendor/` and a terminal | [laravel/prompts](https://github.com/laravel/prompts): arrow keys, colours, boxed styling |
| `menu.sh` | Fallback, when `menu.php` exits `2` (no autoloader, or output is not a TTY) | Dependency-free POSIX numbered menu |

Both read the **Makefile's own annotations**, so neither keeps a second copy of
the command list — annotate a target and it appears in both:

```make
##@ Group name           # section header
target: ## Description   # menu entry
target: ##! Description  # entry that asks for confirmation before running
```

Destructive targets carry `##!` and are confirmed before they run — `dev-clean`,
`dev-kill-ports`, `dev-db-restore`, `dev-db-full-restore`, and `prod-up`.

### Make Commands

Grouped exactly as the menu shows them. ⚠ marks a target that asks for
confirmation first.

| Group | Command | Description |
|-------|---------|-------------|
| **Stack** | `make dev-up` | Start the dev stack (build + up -d) |
| | `make dev-down` | Stop the dev stack (keeps volumes) |
| | ⚠ `make dev-clean` | Hard reset — removes containers **and** volumes (drops the database) |
| **Diagnostics** | `make dev-ports` | Show published ports and Windows reserved ranges |
| | ⚠ `make dev-kill-ports` | Kill `wslrelay.exe` processes holding ports (Windows last resort) |
| **Caches** | `make dev-cache` | Rebuild route/view/event/Filament caches |
| | `make dev-clear` | Clear all caches (fixes stale Filament behaviour) |
| | `make dev-queue` | Run a queue worker in this terminal (blocks) |
| **Database** | `make dev-db-heal` | Run the DB self-heal guard (migrate + seed if needed) |
| | `make dev-db-backup` | Snapshot the dev database (keeps the latest 5) |
| | `make dev-db-list` | List available snapshots |
| | ⚠ `make dev-db-restore` | Reload data from a snapshot (truncates tables, keeps schema) |
| | ⚠ `make dev-db-full-restore` | Full restore — **drops** and recreates every table |
| **Tests** | `make dev-test` | Run the suite against `mamias_test` (snapshots first, restores after) |
| **Production** | `make prod-env` | Create and populate `.env.production` interactively |
| | ⚠ `make prod-up` | Build and start the **production** stack |

### Running Tests

```bash
# Via Make (with automatic DB backup/restore)
make dev-test
make dev-test FILTER=TaxonServiceTest

# Via Docker directly
docker compose --profile dev exec app php artisan test --compact
docker compose --profile dev exec app php artisan test --compact --filter=TestName

# Lint PHP
docker compose --profile dev exec app vendor/bin/pint --dirty --format agent
```

---

## Production Deployment

### Configuration

Use `docker-compose.prod.yml` exclusively in production (never `docker-compose.yml`).

1. **Environment file:** `.env.production` at the repository root, created interactively
   on the production host with:
   ```bash
   make prod-env
   ```

2. **Required secrets** (all but SMTP are set by `make prod-env`):
   - `APP_KEY` — generated
   - `DB_PASSWORD`, `DB_USERNAME`, Redis password — asked for (blank = generated)
   - `CAP_ADMIN_KEY`, `CAP_SITE_KEY`, `CAP_SECRET_KEY` — see **Cap CAPTCHA** below
   - SMTP and WoRMS API keys as needed — edit `.env.production` directly

### Launch

```bash
make prod-up
# or manually:
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

### Cap CAPTCHA (first deploy only)

The proof-of-work CAPTCHA on the login/registration forms is served by the self-hosted
`cap` service. In production it is reached by the browser through a **same-origin reverse
proxy** (`/cap` → `cap:3000`, configured in the app's Caddy directives) and verified
server-side over the internal Docker network — **no public port is exposed**.

Each Cap server has its own key store, so the production site key is **different from the
development one** and must be issued by the production Cap server. `make prod-env` does
this; to set or rotate the keys later, run on the **production host**:

```bash
make prod-keys
make prod-up
```

`prod-keys` generates `CAP_ADMIN_KEY` (or takes yours), starts `cap` + `cap-valkey`, and
asks the production Cap server for a site key through its API — called from inside the
`cap` container, since no Cap port is published. It writes `CAP_SITE_KEY` and
`CAP_SECRET_KEY` to `.env.production` without printing them. You can also paste a key
from the Cap dashboard instead.

> `CAP_SITE_KEY`, `CAP_SECRET_KEY`, and `CAP_ADMIN_KEY` are declared **required** in
> `docker-compose.prod.yml`, so the stack fails fast while any is empty rather than
> shipping a broken CAPTCHA. That check alone would also block starting Cap to issue the
> first key; `prod-keys` works around it for that one step.

### Maintenance

- **Update:** `docker compose -f docker-compose.prod.yml pull && docker compose -f docker-compose.prod.yml up -d`
- **Backups:** Automated via `db-backup` service (see `docker-compose.prod.yml`)
- **Security:** Keep `APP_DEBUG=false`, do not expose internal ports publicly

---

## Docker Services

| Service | Image | Ports | Purpose |
|---------|-------|-------|---------|
| **app** | Custom FrankenPHP | 7443 → 8443 (HTTPS + HTTP/3) | Laravel application server |
| **queue** | Same as app | Internal | Background job processing |
| **db** | kartoza/postgis | 5433 → 5432 (dev only) | PostgreSQL + PostGIS |
| **db-backup** | kartoza/pg-backup | — | Automated database backups |
| **redis** | redis:alpine | 6380 → 6379 (dev only) | Cache, sessions, queue broker |
| **mail** | axllent/mailpit | 8026 (dev only) | SMTP catcher + web UI |
| **cap** | tiago2/cap | 3000 (dev); internal-only in prod (via `/cap` proxy) | Proof-of-work CAPTCHA standalone server (`ADMIN_KEY` required) |
| **cap-valkey** | valkey/valkey:9-alpine | — | Token storage for Cap |

---

## Contributing

1. **Fork** the project.
2. Create a **feature branch** (`git checkout -b feature/my-feature`).
3. Run tests and linting before submitting.
4. Submit a detailed **Pull Request**.

**Guidelines:**
- Follow PSR standards; use Laravel Pint for formatting.
- Add Pest tests for new logic.
- Use the static configurator pattern for new Filament resources.

---

## Roadmap

- **v1.x (Current):**
  - NIS catalogue with WoRMS synchronization
  - Introduction event tracking with CBD pathways and EcAp subregions
  - Literature management with DOI integration
  - PostGIS-based geographic data storage
  - Advanced spatial visualization and mapping
  - Automated CBD compliance reporting
  - Import/Export infrastructure
  - Dashboard analytics with ECharts
- **v2.x (Planned):**
  - 

---

## License

This project is licensed under the **MIT License**. See [LICENSE](https://opensource.org/licenses/MIT) for details.

---

<p align="center"><em>Built with Larvel, Filament 5 &amp; PostGIS — for Mediterranean marine biodiversity science.</em></p>
