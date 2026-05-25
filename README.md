<p align="center">
  <img src="/apps/public/images/Logoweb.png" alt="MAMIAS Logo" width="180">
</p>
<p align="center">
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT"></a>
  <a href="#"><img src="https://img.shields.io/badge/version-1.0.0-blue.svg" alt="Version: 1.0.0"></a>
</p>

<p align="center"><em>Marine Mediterranean Alien Species Database — Laravel 13, Filament 5 &amp; PostGIS.</em></p>

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
- **User Management** — Multi-role RBAC (super_admin, panel_user, user) via Spatie Permission & Filament Shield.
- **System Health** — Real-time health checks, backup management, and command runner from the admin panel.

For the full Software Requirements Specification, see **[requirements.md](requirements.md)**.

## Tech Stack

| Layer            | Technology             | Badge |
|------------------|------------------------|-------|
| **Backend**      | PHP 8.3+ / Laravel 13  | [![PHP: 8.3+](https://img.shields.io/badge/PHP-8.3+-777BB4.svg?logo=php)](https://www.php.net) [![Laravel: 13.0](https://img.shields.io/badge/Laravel-13.0-FF2D20.svg?logo=laravel)](https://laravel.com) |
| **Admin Panel**  | Filament 5.0           | [![Filament: 5.0](https://img.shields.io/badge/Filament-5.0-F1B024.svg?logo=filament)](https://filamentphp.com) |
| **Database**     | PostgreSQL + PostGIS   | [![PostGIS](https://img.shields.io/badge/PostGIS-336791.svg?logo=postgresql)](https://postgis.net) |
| **Cache/Queue**  | Redis 7+               | [![Redis: 7+](https://img.shields.io/badge/Redis-7+-DC382D.svg?logo=redis)](https://redis.io) |
| **Frontend**     | Vite 8 + Tailwind CSS 4.2 + DaisyUI 5 | [![Vite: 8.0](https://img.shields.io/badge/Vite-8.0-646CFF.svg?logo=vite)](https://vitejs.dev) [![Tailwind CSS: 4.2](https://img.shields.io/badge/Tailwind_CSS-4.2-38B2AC.svg?logo=tailwind-css)](https://tailwindcss.com) |
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
└── Roles: super_admin | panel_user | user (Spatie)
```

### Filament Resources

| Resource | Model | Description |
|----------|-------|-------------|
| **TaxonResource** | Taxon | MAMIAS Catalogue — species with WoRMS sync, soft deletes |
| **IntroEventRecordResource** | IntroEventRecord | Introduction events with subregion & pathway records |
| **LiteratureResource** | Literature | Scientific references with DOI auto-fetch |
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
| `WhatsAppService` | GreenAPI WhatsApp phone validation with E.164 fallback |
| `CapService` | Proof-of-work CAPTCHA token verification via Cap Standalone |

### Project Structure

```
mamias/
├── apps/                        # Laravel application
│   ├── app/
│   │   ├── Enums/               # 11 enums (NisStatus, Subregion, CbdPathway*, etc.)
│   │   ├── Filament/
│   │   │   ├── Resources/       # 4 resources (Taxon, IntroEvent, Literature, User)
│   │   │   ├── Widgets/         # 7 dashboard widgets (stats, charts, WoRMS progress)
│   │   │   └── Pages/           # Dashboard, HealthCheck, BackupManager, Auth/*
│   │   ├── Models/              # 6 models (User, Taxon, Literature, IntroEventRecord, SubregionRecord, PathwayRecord)
│   │   └── Services/            # 7 services (Worms, Taxon*, Easin, DOI, WhatsApp)
│   ├── database/migrations/     # 23 migrations (PostGIS, taxas, literatures, intro events, imports/exports, RBAC, health)
│   ├── resources/               # Blade views & Filament theme
│   └── tests/                   # 13 test files (Pest PHP — unit + feature)
├── backups/                     # DB backup dumps
├── docker-compose.yml           # Development stack (8 services)
├── docker-compose.prod.yml      # Production stack
├── Dockerfile                   # FrankenPHP optimized image
├── Makefile                     # Dev/prod lifecycle commands
└── entrypoint.sh                # Container permissions & Filament cache
```

---

## Quick Start (Local Development)

### Prerequisites

- Docker & Docker Compose
- `make` available on your system
- Ports available: `443` (HTTPS), `54321` (PostGIS), `8026` (Mailpit), `6379` (Redis)

### Installation

1. **Clone the repository:**
   ```bash
   git clone <repo-url> mamias
   cd mamias
   ```

2. **Prepare environment:**
   ```bash
   cp .env.example .env && cp apps/.env.example apps/.env
   ```
   Then edit `.env` and `apps/.env` and replace all `CHANGE_ME_*` values.

3. **Start the stack:**
   ```bash
   make dev-up
   ```

4. **Configure Cap CAPTCHA (first run only):**
   Cap is a self-hosted proof-of-work CAPTCHA that protects login and registration forms.
   After starting the stack:
   ```bash
   # 1. Open the Cap dashboard
   open http://localhost:3000

    # 2. Log in with the ADMIN_KEY from your root .env (CAP_ADMIN_KEY)
    # 3. Create a site key, copy the site key and secret key
    # 4. Add them to your root .env:
    #      CAP_SITE_KEY=<from-dashboard>
    #      CAP_SECRET_KEY=<from-dashboard>
    # 5. Restart the app:
   docker compose --profile dev restart app
   ```
   Cap is optional — without it configured, authentication falls back to the existing honeypot protection only.

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

### Make Commands

| Command | Description |
|---------|-------------|
| `make dev-up` | Start dev stack (build + run) |
| `make dev-down` | Stop dev stack |
| `make dev-clean` | Hard reset (containers + volumes) |
| `make dev-cache` | Rebuild route/view/event/Filament caches |
| `make dev-clear` | Clear all caches |
| `make dev-queue` | Start manual queue worker |
| `make dev-ports` | Diagnose port conflicts |
| `make dev-db-heal` | Run DB self-heal guard (migrate + seed) |
| `make dev-db-backup` | Snapshot dev DB (keeps latest 5) |
| `make dev-db-restore` | Restore from latest snapshot |
| `make dev-test` | Run tests with automatic DB backup/restore |
| `make prod-up` | Start production stack |

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

1. **Environment files:**
   - Root: `.env` (Docker variables)
   - `apps/`: `.env.production` (Laravel config)

2. **Required secrets:**
   - `APP_KEY` — generate via `php artisan key:generate --show`
   - `DB_PASSWORD`, `DB_USERNAME` — PostgreSQL credentials
   - Redis password (if exposed)
   - SMTP and WoRMS API keys as needed

### Launch

```bash
make prod-up
# or manually:
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

### Maintenance

- **Update:** `docker compose -f docker-compose.prod.yml pull && docker compose -f docker-compose.prod.yml up -d`
- **Backups:** Automated via `db-backup` service (see `docker-compose.prod.yml`)
- **Security:** Keep `APP_DEBUG=false`, do not expose internal ports publicly

---

## Docker Services

| Service | Image | Ports | Purpose |
|---------|-------|-------|---------|
| **app** | Custom FrankenPHP | 443 (HTTPS + HTTP/3) | Laravel application server |
| **queue** | Same as app | Internal | Background job processing |
| **db** | kartoza/postgis | 54321 (dev only) | PostgreSQL + PostGIS |
| **db-backup** | kartoza/pg-backup | — | Automated database backups |
| **redis** | redis:alpine | 6379 (dev only) | Cache, sessions, queue broker |
| **mail** | axllent/mailpit | 8026 (dev only) | SMTP catcher + web UI |
| **cap** | tiago2/cap | 3000 (dev only) | Proof-of-work CAPTCHA standalone server (`ADMIN_KEY` required) |
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
  - Import/Export infrastructure
  - Dashboard analytics with ECharts
- **v2.x (Planned):**
  - Public REST API (OpenAPI documented)
  - Advanced spatial visualization and mapping
  - Automated CBD compliance reporting
  - Darwin Core archive export
  - Machine learning for invasion risk prediction

---

## License

This project is licensed under the **MIT License**. See [LICENSE](https://opensource.org/licenses/MIT) for details.

---

<p align="center"><em>Built with Laravel 13 &amp; FrankenPHP — for Mediterranean marine biodiversity science.</em></p>
