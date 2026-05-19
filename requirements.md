# MAMIAS — Software Requirements Specification

> **Version:** 1.1.0
> **Date:** 2026-05-14
> **Status:** Active
> **Language:** Français / English (code & terms)

---

## 1. Introduction

### 1.1 Purpose

**MAMIAS** (Marine Mediterranean Alien Species Database) is a scientific web application for managing, cataloguing, and disseminating data on Non-Indigenous Species (NIS) in the Mediterranean Sea. The system serves as a scientific database for researchers, taxonomists, and marine biologists to track NIS taxonomy, geographic distribution, introduction events, invasion pathways, and associated scientific literature.

### 1.2 Scope

| Domain | Description | Status |
|--------|-------------|--------|
| **Taxonomy** | Species catalogue with **WoRMS** synchronization, normalization, and bulk fetch | Implemented |
| **Introduction Events** | NIS introduction tracking with year, country, establishment status | Implemented |
| **Pathways** | CBD pathway classification (category, subcategory, pathway type) | Implemented |
| **Subregions** | EcAp subregion monitoring (WMED, CMED, ADRIA, EMED) per event | Implemented |
| **Literature** | Bibliographic references with DOI auto-retrieval and auto-coded references | Implemented |
| **Users** | Registration, authentication, RBAC (Spatie + Filament Shield) | Implemented |
| **Dashboard** | Statistical widgets and ECharts visualizations | Implemented |
| **Import/Export** | Session-based import with error tracking; Filament export infrastructure | Implemented |
| **Health & Ops** | Health checks, backup manager, command runner | Implemented |
| **Notifications** | Laravel notification system | Implemented |
| **Spatial Data** | PostGIS extension enabled; spatial columns pending | Partial |
| **Reporting** | CBD compliance reports, Darwin Core export | Planned |

### 1.3 Key Features

| # | Feature | Description | Status |
|---|---------|-------------|--------|
| 1 | **Taxon Catalogue** | CRUD for marine species with WoRMS sync, soft deletes, and data normalization | Done |
| 2 | **NIS Introduction Events** | Track introduction year, country, establishment success per taxon | Done |
| 3 | **Subregion Records** | Record species arrival and NIS status per EcAp subregion | Done |
| 4 | **Pathway Records** | Classify introduction pathways by CBD category/subcategory/type | Done |
| 5 | **Literature Management** | Reference management with Crossref DOI integration and auto-coded entries | Done |
| 6 | **User Management** | Multi-role auth with profile fields (title, phone, WhatsApp, country, taxonomic area) | Done |
| 7 | **Dashboard Analytics** | Stats overview + 4 ECharts (kingdom, phylum, environment, phylum-by-kingdom) | Done |
| 8 | **Import/Export** | Session-based bulk import with column mapping and error tracking | Done |
| 9 | **System Health** | Spatie Health checks + backup manager + command runner | Done |
| 10 | **Spatial Visualization** | PostGIS-powered geographic distribution mapping | Planned |
| 11 | **CBD Reporting** | Compliance reports for Convention on Biological Diversity | Planned |

### 1.4 Definitions & Acronyms

| Term | Definition |
|------|------------|
| **NIS** | Non-Indigenous Species |
| **WoRMS** | World Register of Marine Species |
| **CBD** | Convention on Biological Diversity |
| **EcAp** | Ecosystem Approach — Mediterranean subregions |
| **WMED** | Western Mediterranean |
| **CMED** | Central Mediterranean |
| **ADRIA** | Adriatic Sea |
| **EMED** | Eastern Mediterranean |
| **EASIN** | European Alien Species Information Network |
| **DOI** | Digital Object Identifier |
| **RBAC** | Role-Based Access Control |
| **Darwin Core** | Biodiversity data sharing standard |
| **PostGIS** | Spatial extension for PostgreSQL |

### 1.5 References

- [README.md](README.md) — Technical overview & deployment guide
- `apps/composer.json` — PHP dependencies
- `apps/package.json` — Node.js dependencies

---

## 2. Overall Description

### 2.1 Product Perspective

MAMIAS is a monolithic **Laravel 13** application serving:
- A **Filament 5 admin panel** (`/mamias`) for scientific data management, user administration, and system operations
- A **welcome page** (`/`) as the public entry point
- A **health endpoint** (`/up`) for monitoring

There is no traditional REST API in v1.

### 2.2 User Classes & Characteristics

| Role | Description | Access |
|------|-------------|--------|
| **super_admin** | Full system management, all permissions | Filament panel + all resources |
| **panel_user** | Data entry and consultation | Filament panel (scoped permissions via Shield) |
| **user** | Basic registered user | Public pages only (redirected to `/` on login) |

### 2.3 Operating Environment

| Layer | Technology | Version |
|-------|------------|---------|
| Runtime | Docker + FrankenPHP | Latest |
| Language | PHP | 8.3+ |
| Framework | Laravel | 13.0 |
| Admin Panel | Filament | 5.0 |
| Realtime UI | Livewire | 4.3+ |
| Database | PostgreSQL + PostGIS | Latest |
| Cache/Session/Queue | Redis | 7+ (alpine) |
| Mail (dev) | Mailpit | Latest |
| Frontend Build | Vite | 8.0 |
| CSS Framework | Tailwind CSS + DaisyUI | 4.2+ / 5.5+ |
| Testing | Pest PHP | 4.6 |

### 2.4 Design & Implementation Constraints

- Dockerized runtime for both dev and production
- PostGIS required from initialization (spatial data support)
- Self-signed HTTPS certificate in development
- Base image: `serversideup/php:8.5-frankenphp`
- Container UID/GID remapping via `entrypoint.sh`
- Test database uses in-memory SQLite (not PostGIS) — dialect differences must be handled

---

## 3. Functional Requirements

### 3.1 User Management (FR-USER)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-USER-01 | User registration with email and password | Must | Done |
| FR-USER-02 | Email verification required (`MustVerifyEmail`) | Must | Done |
| FR-USER-03 | Full profile: first_name, last_name, title, phone, has_whatsapp, country, taxonomic_area (JSON), subregions (JSON), countries (JSON), bio | Must | Done |
| FR-USER-04 | Full name auto-derived from first_name + last_name (`booted()`) | Must | Done |
| FR-USER-05 | Dynamic avatar via UI Avatars API | Should | Done |
| FR-USER-06 | RBAC via Spatie Permission + Filament Shield (super_admin, panel_user, user) | Must | Done |
| FR-USER-07 | Developer logins in dev environment | Could | Done |
| FR-USER-08 | WhatsApp number validation via GreenAPI (WhatsAppService) | Should | Done |

### 3.2 Taxonomic Catalogue (FR-TAXO)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-TAXO-01 | CRUD for marine species (Taxon model, `taxas` table) | Must | Done |
| FR-TAXO-02 | WoRMS API integration: lookup by AphiaID, bulk phyla fetch by kingdom | Must | Done |
| FR-TAXO-03 | Full taxonomic hierarchy: Kingdom, Phylum, Class, Order, Family, Genus | Must | Done |
| FR-TAXO-04 | Track WoRMS status (enum) and catalogue status (enum) per taxon | Must | Done |
| FR-TAXO-05 | Store synonyms data (JSON array) and proposed accepted name | Should | Done |
| FR-TAXO-06 | Soft delete support with recycle bin | Should | Done |
| FR-TAXO-07 | Data normalization via TaxonNormalizer service on save | Must | Done |
| FR-TAXO-08 | EASIN integration via EasinService (Easin_id field) | Should | Done |
| FR-TAXO-09 | Track extinction status and environments (marine, freshwater, terrestrial, brackish) | Should | Done |
| FR-TAXO-10 | Userstamps (created_by, updated_by) for audit trail | Must | Done |
| FR-TAXO-11 | WoRMS fetch progress widget on dashboard | Should | Done |

### 3.3 NIS Introduction Events (FR-NIS)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-NIS-01 | Record introduction events linking Taxon + Literature with year, country, NIS status, establishment status | Must | Done |
| FR-NIS-02 | NIS status classification via enum (NisStatus) | Must | Done |
| FR-NIS-03 | Establishment status tracking via enum (EstablishmentStatus) | Must | Done |
| FR-NIS-04 | Link each event to its source Literature reference | Must | Done |
| FR-NIS-05 | Userstamps for audit trail | Must | Done |

### 3.4 Subregion Records (FR-SUB)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-SUB-01 | Record species arrival per EcAp subregion (WMED, CMED, ADRIA, EMED) | Must | Done |
| FR-SUB-02 | Track NIS status and first arrival year per subregion | Must | Done |
| FR-SUB-03 | Link subregion records to parent IntroEventRecord | Must | Done |

### 3.5 Pathway Records (FR-PATH)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-PATH-01 | Classify pathways by CBD category (enum CbdPathwayCategory) | Must | Done |
| FR-PATH-02 | Classify pathways by CBD subcategory (enum CbdPathwaySubcategory) | Must | Done |
| FR-PATH-03 | Track pathway type (enum PathwayType) | Must | Done |
| FR-PATH-04 | Link pathway records to parent IntroEventRecord | Must | Done |
| FR-PATH-05 | Free-text description and notes per pathway record | Should | Done |

### 3.6 Literature Management (FR-LIT)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-LIT-01 | CRUD for scientific references (Literature model) | Must | Done |
| FR-LIT-02 | Auto-generate sequential reference codes (mamias000001 format) with DB locking | Must | Done |
| FR-LIT-03 | DOI metadata auto-retrieval via DoiMetadataService (Crossref) | Should | Done |
| FR-LIT-04 | Literature type classification (enum LiteratureType) | Must | Done |
| FR-LIT-05 | Userstamps for audit trail | Must | Done |
| FR-LIT-06 | LiteratureObserver for lifecycle hooks | Should | Done |

### 3.7 Dashboard & Visualization (FR-DASH)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-DASH-01 | Stats overview widget: total species, accepted/not accepted/not checked percentages | Must | Done |
| FR-DASH-02 | Species by Kingdom chart (ECharts) | Must | Done |
| FR-DASH-03 | Species by Phylum chart (ECharts) | Must | Done |
| FR-DASH-04 | Phylum by Kingdom chart (ECharts) | Should | Done |
| FR-DASH-05 | Catalogue environment distribution chart (ECharts) | Should | Done |
| FR-DASH-06 | WoRMS fetch progress tracking widget | Should | Done |
| FR-DASH-07 | System information widget (MamiasInfoWidget) | Could | Done |
| FR-DASH-08 | Tabbed dashboard layout (MAMIAS Catalogue / MAMIAS Data) | Should | Done |

### 3.8 Import / Export (FR-IO)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-IO-01 | Session-based import with file upload, column mapping, and step-by-step wizard | Must | Done |
| FR-IO-02 | Failed import row tracking with error details | Must | Done |
| FR-IO-03 | Export infrastructure via Filament (exporter class, file disk) | Must | Done |
| FR-IO-04 | Excel/CSV support via PHPSpreadsheet | Must | Done |
| FR-IO-05 | Darwin Core format export | Should | Planned |

### 3.9 System Operations (FR-OPS)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-OPS-01 | Health monitoring via Spatie Laravel Health | Must | Done |
| FR-OPS-02 | Health check results page in Filament panel | Must | Done |
| FR-OPS-03 | Backup management via Filament Spatie Backup plugin | Should | Done |
| FR-OPS-04 | Command execution from Filament panel (Filament Command Runner) | Could | Done |
| FR-OPS-05 | Resource locking to prevent edit conflicts | Should | Done |
| FR-OPS-06 | Recycle bin for soft-deleted items | Should | Done |
| FR-OPS-07 | Cache management from panel (Filament Clear Cache) | Could | Done |
| FR-OPS-08 | Environment indicator in panel header | Could | Done |
| FR-OPS-09 | Unsaved changes modal on navigation | Should | Done |
| FR-OPS-10 | Session lockscreen | Could | Done |

### 3.10 Notifications (FR-NOTIF)

| ID | Requirement | Priority | Status |
|----|-------------|----------|--------|
| FR-NOTIF-01 | Laravel notification system for users | Must | Done |
| FR-NOTIF-02 | Email notifications for critical events | Should | Partial |

---

## 4. Non-Functional Requirements

### 4.1 Performance (NFR-PERF)

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-PERF-01 | Panel page response time | < 500 ms |
| NFR-PERF-02 | Dashboard initial load | < 2 s |
| NFR-PERF-03 | WoRMS API calls with caching (86400s TTL) | < 30 s timeout |
| NFR-PERF-04 | Opcache enabled in container | Must |
| NFR-PERF-05 | Redis-backed sessions, cache, and queue | Must |

### 4.2 Security (NFR-SEC)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-SEC-01 | HTTPS everywhere (self-signed in dev, valid cert in prod) | Must |
| NFR-SEC-02 | HSTS, X-Frame-Options, CSP headers | Must |
| NFR-SEC-03 | Passwords hashed via bcrypt | Must |
| NFR-SEC-04 | `APP_DEBUG=false` in production | Must |
| NFR-SEC-05 | Debugbar disabled in production | Must |
| NFR-SEC-06 | `.env.production` never committed to version control | Must |
| NFR-SEC-07 | Resource locking to prevent concurrent edit conflicts | Should |

### 4.3 Reliability (NFR-AVAIL)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-AVAIL-01 | Docker Compose orchestration with health checks | Must |
| NFR-AVAIL-02 | Automated DB backups via db-backup service | Must |
| NFR-AVAIL-03 | Dev DB snapshot/restore workflow (keeps latest 5) | Should |
| NFR-AVAIL-04 | Health check endpoint at `/up` | Must |
| NFR-AVAIL-05 | Queue worker as dedicated container service | Must |

### 4.4 Maintainability (NFR-MAINT)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-MAINT-01 | PHP code formatted via Laravel Pint (PSR) | Must |
| NFR-MAINT-02 | Automated tests via Pest PHP (13 test files, unit + feature) | Must |
| NFR-MAINT-03 | Versioned Laravel migrations (23 migrations) | Must |
| NFR-MAINT-04 | Filament resources follow static configurator pattern (Resource + Schemas + Tables + Pages) | Must |
| NFR-MAINT-05 | Userstamps on domain models for audit trail | Must |
| NFR-MAINT-06 | Schema Sentinel for migration safety | Should |

### 4.5 Usability (NFR-UX)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-UX-01 | Responsive admin panel (Filament + Tailwind CSS) | Must |
| NFR-UX-02 | Tabler Icons for visual consistency | Should |
| NFR-UX-03 | Unsaved changes confirmation modal | Should |
| NFR-UX-04 | Session lockscreen | Could |
| NFR-UX-05 | Custom panel footer | Could |
| NFR-UX-06 | Environment indicator badge | Could |
| NFR-UX-07 | Auth UI enhancements (login/register styling) | Should |

---

## 5. Technical Requirements

### 5.1 Backend Dependencies (Production)

| Package | Version | Usage |
|---------|---------|-------|
| `laravel/framework` | ^13.0 | Application framework |
| `filament/filament` | ^5.0 | Admin panel |
| `livewire/livewire` | ^4.3 | Reactive UI components |
| `bezhansalleh/filament-shield` | ^4.2 | RBAC for Filament |
| `spatie/laravel-health` | *(via plugin)* | Health monitoring |
| `shuvroroy/filament-spatie-laravel-health` | ^3.3 | Health UI in Filament |
| `shuvroroy/filament-spatie-laravel-backup` | ^3.4 | Backup management UI |
| `binarybuilds/filament-command-runner` | ^1.2 | Artisan command execution |
| `elemind/filament-echarts` | * | Dashboard charts |
| `phpoffice/phpspreadsheet` | ^5.7 | Excel/CSV import/export |
| `clickbar/laravel-magellan` | * | PostGIS/spatial queries |
| `laravel-schema-sentinel` | ^1.6 | Migration safety |
| `filament-resource-lock` | ^2.1 | Edit conflict prevention |
| `nakanakaii/filament-countries` | ^1.0 | Country selector |
| `daljo25/filament-tabler-icons` | ^3.41 | Tabler icon set |
| `secondnetwork/blade-tabler-icons` | ^3.41 | Tabler icons for Blade |
| `azgasim/filament-unsaved-changes-modal` | ^1.0 | Unsaved changes prompt |
| `diogogpinto/filament-auth-ui-enhancer` | ^2.0 | Auth page styling |
| `devonab/filament-easy-footer` | ^2.2 | Custom panel footer |
| `pxlrbt/filament-environment-indicator` | ^3.5 | Environment badge |
| `dutchcodingcompany/filament-developer-logins` | ^2.1 | Dev-only login shortcuts |
| `marjose123/filament-lockscreen` | * | Session lockscreen |
| `cms-multi/filament-clear-cache` | ^3.0 | Cache clearing from panel |
| `daljo25/filament-dependency-manager` | ^5.0 | Dependency management |
| `hasnayeen/filament-pretty-json` | ^4.2 | JSON field display |
| `rector/rector` | ^2.4 | Code modernization |
| `mattiverse/userstamps` | * | created_by / updated_by audit |
| `laravel/tinker` | ^3.0 | Interactive REPL |

### 5.2 Backend Dependencies (Development)

| Package | Version | Usage |
|---------|---------|-------|
| `pestphp/pest` | ^4.6 | Testing framework |
| `pestphp/pest-plugin-laravel` | ^4.1 | Laravel integration |
| `pestphp/pest-plugin-livewire` | ^4.1 | Livewire component testing |
| `laravel/pint` | ^1.27 | PHP code formatting |
| `laravel/pail` | ^1.2.5 | Real-time log tailing |
| `laravel/boost` | ^2.4 | Agentic dev tools |
| `fruitcake/laravel-debugbar` | ^4.2 | Debug bar (dev only) |
| `fakerphp/faker` | ^1.23 | Test data generation |
| `nunomaduro/collision` | ^8.6 | Error reporting |
| `mockery/mockery` | ^1.6 | Test mocking |

### 5.3 Frontend Dependencies

| Package | Version | Usage |
|---------|---------|-------|
| `vite` | ^8.0.0 | Build tool |
| `tailwindcss` | ^4.2.4 | CSS framework |
| `@tailwindcss/vite` | ^4.2.4 | Vite integration |
| `daisyui` | ^5.5.19 | UI component library |
| `laravel-vite-plugin` | ^3.0.0 | Laravel/Vite bridge |
| `concurrently` | ^9.0.1 | Parallel dev processes |

### 5.4 Domain Enums

| Enum | Values |
|------|--------|
| `NisStatus` | NIS status classifications |
| `EstablishmentStatus` | Establishment success levels |
| `Worms_Status` | WoRMS taxonomic status |
| `Catalogue_Status` | MAMIAS catalogue status |
| `Subregion` | WMED, CMED, ADRIA, EMED |
| `CbdPathwayCategory` | CBD pathway categories |
| `CbdPathwaySubcategory` | CBD pathway subcategories |
| `PathwayType` | Pathway type classification |
| `LiteratureType` | Literature reference types |
| `Environment` | Marine, freshwater, terrestrial, brackish |
| `DataQuality` | Data quality levels |

### 5.5 Infrastructure

#### Development (docker-compose.yml, `--profile dev`)

| Service | Image | Ports | Purpose |
|---------|-------|-------|---------|
| **app** | Custom FrankenPHP (serversideup/php:8.5-frankenphp) | 443 (HTTPS + HTTP/3) | Application server |
| **queue** | Same as app | Internal | Background job worker |
| **db** | kartoza/postgis:latest | 54321 | PostgreSQL + PostGIS |
| **db-backup** | kartoza/pg-backup:latest | — | Automated DB dumps |
| **redis** | redis:alpine | 6379 | Cache, sessions, queue |
| **mail** | axllent/mailpit:latest | 8026 (Web UI) | SMTP capture |

#### Production (docker-compose.prod.yml)

| Service | Notes |
|---------|-------|
| **app** | FrankenPHP with production PHP config |
| **db** | PostGIS with persistent volume |
| **redis** | Cache + sessions, password-protected |

---

## 6. Database Schema

### 6.1 Implemented Tables (23 migrations)

**Core Domain:**

| Table | Description | Key Columns |
|-------|-------------|-------------|
| `taxas` | Marine species catalogue | aphia_id, scientificname, authority, worms_status, catalogue_status, rank, kingdom→genus hierarchy, lsid, environments (JSON), synonyms_data (JSON), Easin_id, fetched_at |
| `literatures` | Scientific references | code (auto-generated), doi, type, short_ref, full_ref, link |
| `intro_event_records` | NIS introduction events | taxon_id (FK), first_introduction_year, first_country, nis_status, establishment_status, literature_id (FK) |
| `subregion_records` | Per-subregion event data | intro_event_id (FK), subregion (enum), nis_status, first_arrival_year |
| `pathway_records` | CBD pathway classification | intro_event_id (FK), category, subcategory, pathway_type, description |

**User & Auth:**

| Table | Description |
|-------|-------------|
| `users` | Users with profile fields (first_name, last_name, title, phone, has_whatsapp, country, taxonomic_area, subregions, countries, bio) |
| `permissions` / `roles` / `model_has_*` / `role_has_*` | Spatie RBAC tables |
| `password_reset_tokens` | Password reset tokens |
| `sessions` | User sessions |

**Infrastructure:**

| Table | Description |
|-------|-------------|
| `cache` / `cache_locks` | Laravel cache |
| `jobs` / `job_batches` / `failed_jobs` | Queue infrastructure |
| `notifications` | Laravel notifications |
| `health_check_result_history_items` | Spatie Health history |
| `command_runs` | Artisan command execution log |
| `import_sessions` | Multi-step import wizard state |
| `imports` / `exports` / `failed_import_rows` | Filament import/export tracking |
| `resource_locks` / `resource_lock_audit` | Edit conflict prevention |
| `recycle_bin_items` | Soft-deleted item recovery |

### 6.2 Spatial Data

- PostGIS extension enabled via migration
- Spatial columns and indices to be added for geographic distribution data

---

## 7. Interface Requirements

### 7.1 Filament Admin Panel (`/mamias`)

| Page/Resource | Description |
|---------------|-------------|
| **Dashboard** | Tabbed layout with stats + 4 ECharts + WoRMS progress + system info |
| **TaxonResource** | Species CRUD with WoRMS sync, soft deletes, infolist |
| **IntroEventRecordResource** | Introduction events with nested subregion + pathway records |
| **LiteratureResource** | References with DOI auto-fetch |
| **UserResource** | User management with role assignment |
| **HealthCheckResults** | System health monitoring page |
| **BackupManager** | Database/file backup management |

### 7.2 External System Interfaces

| System | Type | Integration |
|--------|------|-------------|
| **WoRMS** | REST API | Taxonomy validation, AphiaID lookup, bulk phyla fetch (WormsService) |
| **EASIN** | API | European alien species data (EasinService) |
| **Crossref** | REST API | DOI metadata resolution (DoiMetadataService) |
| **GreenAPI** | REST API | WhatsApp number validation (WhatsAppService) |
| **UI Avatars** | HTTP | Dynamic avatar generation for users |

---

## 8. Testing

### 8.1 Test Infrastructure

- **Framework:** Pest PHP 4.6 with Laravel + Livewire plugins
- **Database:** In-memory SQLite (not PostGIS — dialect differences must be managed)
- **Pattern:** `actingAs(User::factory()->create())` for panel page tests
- **Execution:** `make dev-test` (with automatic DB backup/restore) or `php artisan test --compact`

### 8.2 Test Coverage (13 files)

**Unit Tests:**
- `DoiMetadataServiceTest` — DOI metadata resolution
- `TaxonServiceTest` — Core taxon operations
- `TaxonTest` — Taxon model behavior
- `LiteratureTest` — Literature model behavior

**Feature Tests:**
- `TaxonFormTest` — Filament taxon form
- `TaxonSoftDeleteTest` — Soft delete functionality
- `FetchTaxaFromWormsJobTest` — WoRMS API integration
- `UserManagementTest` — User CRUD and auth
- `UserManagementExtendedTest` — Extended user features

---

## 9. Acceptance Criteria

### 9.1 General

- [x] Application starts via `make dev-up`
- [x] `https://mamias.local` accessible (self-signed cert)
- [x] Filament panel functional at `/mamias`
- [x] Migrations run without error (23 migrations)
- [x] Pest tests pass
- [x] PHP linting clean (Pint)

### 9.2 Functional

- [x] User registration, email verification, and login
- [x] Admin can manage users and assign roles
- [x] Taxon CRUD with WoRMS synchronization
- [x] Introduction events with subregion and pathway records
- [x] Literature management with DOI auto-retrieval
- [x] Dashboard displays statistics and charts
- [x] Import/export infrastructure operational
- [x] Health checks and backup management accessible
- [ ] PostGIS spatial queries for geographic filtering
- [ ] Darwin Core format export
- [ ] CBD compliance report generation

### 9.3 Non-Functional

- [x] Panel response time < 500ms
- [x] `APP_DEBUG=false` in production
- [x] Security headers present
- [x] Resource locking prevents edit conflicts
- [x] Automated DB backups configured

---

## 10. Future Enhancements (Out of Scope v1)

| Feature | Description |
|---------|-------------|
| Public REST API (OpenAPI) | Expose NIS data via documented API |
| Spatial Visualization | Interactive maps for species distribution |
| CBD Compliance Reports | Automated report generation |
| Darwin Core Archive | Full DwC-A export capability |
| Mobile App | Field data entry application |
| Machine Learning | Invasion risk prediction |
| Open Data Portal | Public data access interface |
| Advanced Workflow | Multi-step validation workflow with notifications |

---

## 11. Appendix

### 11.1 Development Commands

| Action | Command |
|--------|---------|
| Start dev stack | `make dev-up` |
| Stop dev stack | `make dev-down` |
| Hard reset | `make dev-clean` |
| Run tests | `make dev-test` or `docker compose --profile dev exec app php artisan test --compact` |
| Lint PHP | `docker compose --profile dev exec app vendor/bin/pint --dirty --format agent` |
| Real-time logs | `docker compose --profile dev exec app php artisan pail` |
| Container shell | `docker compose --profile dev exec app bash` |
| Build assets | `docker compose --profile dev exec app npm run build` |
| DB backup | `make dev-db-backup` |
| DB restore | `make dev-db-restore` |
| DB self-heal | `make dev-db-heal` |
| Rebuild caches | `make dev-cache` |
| Clear caches | `make dev-clear` |

### 11.2 Production Commands

| Action | Command |
|--------|---------|
| Deploy | `make prod-up` |
| Deploy (manual) | `docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build` |

---

*Document updated 2026-05-14 — MAMIAS v1.1.0*
