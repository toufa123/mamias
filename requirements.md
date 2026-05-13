# MAMIAS — Software Requirements Specification

> **Version:** 1.0.0  
> **Date:** 2026-04-25  
> **Statut:** Draft  
> **Langue:** Français / English (code & terms)

---

## 1. Introduction

### 1.1 Purpose

**MAMIAS** (Marine Alien Species Database) est une application web complète dédiée à la gestion, au catalogage et à la diffusion d'informations sur les espèces marines exotiques et envahissantes (*Non-Indigenous Species — NIS*) en région méditerranéenne. Le système sert de base de données scientifique pour les chercheurs, taxonomistes et biologistes marins afin de suivre les espèces non indigènes, leur taxonomie, leur distribution géographique et la littérature associée.

### 1.2 Scope

| Domaine | Description |
|---------|-------------|
| **Taxonomie** | Gestion des données taxonomiques avec intégration **WoRMS** (World Register of Marine Species) |
| **Événements d'introduction** | Suivi des introductions de NIS avec classification **CBD pathway** |
| **Utilisateurs** | Inscription, authentification et contrôle d'accès basé sur les rôles (RBAC) |
| **Sub-régions** | Monitoring des sous-régions **EcAp** (WMED, CMED, ADRIA, EMED) |
| **Succès d'établissement** | Suivi du succès d'établissement et analyse spatio-temporelle |
| **Visualisation** | Tableaux de bord, analytics et cartographie des distributions |
| **Mises à jour temps réel** | Dashboard avec mises à jour de données en temps réel |
| **Littérature** | Gestion des références bibliographiques avec récupération des métadonnées DOI |
| **Import/Export** | Capacités d'import/export pour opérations de données en masse |
| **Reporting** | Rapports conformes **CBD** et partage de données **Darwin Core** |
| **Monitoring** | Health checks et diagnostics système |

### 1.3 Key Features

| # | Feature | Description |
|---|---------|-------------|
| 1 | **Taxon Management** | CRUD operations for marine species with automatic **WoRMS** synchronization |
| 2 | **NIS Introduction Events** | Track introduction year, country, establishment success, and **CBD pathways** |
| 3 | **Mediterranean Subregion Monitoring** | Record species arrival and establishment per **EcAp** subregion (WMED, CMED, ADRIA, EMED) |
| 4 | **Pathway Analysis** | Classify and report introduction pathways per **CBD/IAS Regulation** guidelines |
| 5 | **Literature Management** | Reference management with **Crossref DOI** integration |
| 6 | **User Management** | Multi-role authentication and authorization system |
| 7 | **Dashboard Analytics** | Statistical widgets and charts for species distribution |
| 8 | **Temporal-Spatial Visualization** | Track species spread over time across Mediterranean regions |
| 9 | **Data Import/Export** | Bulk data operations via **Excel** and **Darwin Core** format |
| 10 | **CBD Reporting** | Generate compliance reports for **Convention on Biological Diversity** |
| 11 | **System Health Monitoring** | Real-time application health checks |

### 1.4 Definitions & Acronyms

| Terme | Définition |
|-------|------------|
| **NIS** | Non-Indigenous Species (espèce non indigène) |
| **WoRMS** | World Register of Marine Species |
| **CBD** | Convention on Biological Diversity |
| **EcAp** | Ecosystem Approach (approche écosystémique) — sous-régions méditerranéennes |
| **WMED** | Western Mediterranean |
| **CMED** | Central Mediterranean |
| **ADRIA** | Adriatic Sea |
| **EMED** | Eastern Mediterranean |
| **DOI** | Digital Object Identifier |
| **RBAC** | Role-Based Access Control |
| **Darwin Core** | Standard de partage de données de biodiversité |
| **PostGIS** | Extension spatiale pour PostgreSQL |

### 1.5 References

- [README.md](README.md) — Vue d'ensemble technique/Guide de déploiement production
- `composer.json` — Dépendances PHP
- `package.json` — Dépendances Node.js

---

## 2. Overall Description

### 2.1 Product Perspective

MAMIAS est une application monolithique **Laravel 13** servant à la fois :
- Un **back-office administratif** (Filament 5) pour la saisie, la validation et la gestion des données scientifiques
- Une **API / interface publique** pour la consultation et l'exploitation des données NIS
- Un **système de monitoring** pour la santé de la plateforme

### 2.2 User Classes & Characteristics

| Rôle | Description | Accès |
|------|-------------|-------|
| **Administrateur** | Gestion complète du système, utilisateurs, configuration | Back-office Filament + tous les privilèges |
| **Chercheur / Taxonomiste** | Saisie et consultation des données NIS, taxonomie, littérature | Front/public (permissions modulaires) |
| **Validateur** | Validation des soumissions de données, modération | Back-office Filament (permissions de validation) |
| **Visiteur public** | Consultation des données (lecture seule) | Front/public |

### 2.3 Operating Environment

| Couche | Technologie |
|--------|-------------|
| Runtime | Docker + Docker Compose + FrankenPHP |
| Serveur Web | FrankenPHP (Caddy intégré) |
| Langage | PHP 8.5 |
| Framework | Laravel 13 |
| Panel Admin | Filament 5.0 |
| Base de données | PostgreSQL + PostGIS |
| Cache / Sessions / Files | Redis |
| Mail (dev) | Mailpit (SMTP catcher + Web UI) |
| Frontend | Vite 8 + Tailwind CSS 4.2 |
| Tests | Pest PHP 4.4 |

### 2.4 Design & Implementation Constraints

- L'application **doit** s'exécuter dans un environnement Dockerisé (dev & prod)
- PostGIS est requis dès l'initialisation (données géospatiales)
- Le certificat HTTPS en dev est auto-signé (à accepter manuellement)
- L'image Docker est basée sur `serversideup/php:8.5-frankenphp`
- UID/GID sont remappés via `entrypoint.sh` pour éviter les fichiers root sur le host

---

## 3. Functional Requirements

### 3.1 User Management (FR-USER)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-USER-01 | Le système doit permettre l'inscription des utilisateurs avec email et mot de passe | Must |
| FR-USER-02 | L'authentification doit requérir une vérification d'email (`MustVerifyEmail`) | Must |
| FR-USER-03 | Chaque utilisateur dispose d'un profil complet : nom, prénom, titre, téléphone, WhatsApp, pays, zone taxonomique, sous-régions, pays d'intérêt, bio | Must |
| FR-USER-04 | Le nom complet doit être généré automatiquement à partir du prénom + nom | Must |
| FR-USER-05 | L'avatar doit être généré dynamiquement via UI Avatars API | Should |
| FR-USER-06 | Le système doit supporter le contrôle d'accès basé sur les rôles (via Spatie Permission / Filament Shield) | Must |
| FR-USER-07 | Des logins développeur doivent être disponibles en environnement de développement | Could |

The application uses **Filament Shield** for role-based access control (RBAC).

```text
/admin/login  ──→  Filament Login Page
                        │
              ┌─────────┴─────────┐
              ▼                   ▼
         Role: admin          Role: user
              │                   │
              ▼                   ▼
       /admin (backend)     / (frontend only)
    Filament Dashboard     Laravel Blade/Livewire
    Resources, CRUD        Public-facing pages
    Shield Permissions     No /admin access
```

### 3.2 Taxonomic Data Management (FR-TAXO)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-TAXO-01 | Le système doit gérer les données taxonomiques des espèces marines | Must |
| FR-TAXO-02 | Intégration avec **WoRMS** pour la synchronisation automatique, la validation et l'enrichissement des données taxonomiques | Must |
| FR-TAXO-03 | Stockage hiérarchique : Règne, Phylum, Classe, Ordre, Famille, Genre, Espèce | Must |
| FR-TAXO-04 | Support des synonymes et noms acceptés | Should |

### 3.3 NIS Introduction Events (FR-NIS)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-NIS-01 | Le système doit permettre l'enregistrement des événements d'introduction d'espèces non indigènes (année, pays, espèce) | Must |
| FR-NIS-02 | Suivi du succès d'établissement des espèces introduites | Must |
| FR-NIS-03 | Analyse spatio-temporelle des distributions | Should |

### 3.4 Pathway Analysis (FR-PATHWAY)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-PATHWAY-01 | Classification des introduction pathways selon le standard **CBD/IAS Regulation** | Must |
| FR-PATHWAY-02 | Reporting et analyse des pathways par espèce, région et période | Must |
| FR-PATHWAY-03 | Corrélation entre pathways et succès d'établissement | Should |

### 3.5 Geographic Coverage & Monitoring (FR-GEO)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-GEO-01 | Le système doit couvrir les sous-régions méditerranéennes **EcAp** : WMED, CMED, ADRIA, EMED | Must |
| FR-GEO-02 | Enregistrement de l'arrivée et de l'établissement des espèces par sous-région EcAp | Must |
| FR-GEO-03 | Stockage des données géographiques via **PostGIS** (points, polygones, géométries) | Must |
| FR-GEO-04 | Visualisation cartographique des distributions d'espèces | Should |
| FR-GEO-05 | Filtrage des données par sous-région | Must |
| FR-GEO-06 | Visualisation spatio-temporelle de la propagation des espèces à travers les régions méditerranéennes | Should |

### 3.6 Data Visualization & Dashboard (FR-DASH)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-DASH-01 | Tableau de bord avec widgets statistiques (nombre d'espèces, introductions récentes, etc.) | Must |
| FR-DASH-02 | Graphiques et analytics de distribution des espèces via **Filament ECharts** | Must |
| FR-DASH-03 | Mises à jour de données en temps réel sur le dashboard | Should |
| FR-DASH-04 | Visualisation spatio-temporelle de la propagation des espèces à travers les régions méditerranéennes | Should |
| FR-DASH-05 | Widget d'information système (MamiasInfoWidget) | Could |

### 3.7 Literature Reference Management (FR-LIT)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-LIT-01 | Gestion des références bibliographiques associées aux espèces et événements | Must |
| FR-LIT-02 | Récupération automatique des métadonnées à partir d'un **DOI** | Should |
| FR-LIT-03 | Liens entre espèces, événements et publications | Must |

### 3.7 Import / Export (FR-IO)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-IO-01 | Import de données en masse (CSV, Excel, Darwin Core) | Must |
| FR-IO-02 | Export des données (CSV, Excel, Darwin Core Archive) | Must |
| FR-IO-03 | Validation des données importées avant insertion | Must |

### 3.8 Reporting & Compliance (FR-REPORT)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-REPORT-01 | Génération de rapports conformes **CBD** | Should |
| FR-REPORT-02 | Partage de données au format **Darwin Core** | Should |
| FR-REPORT-03 | Rapports par sous-région, période, ou espèce | Should |

### 3.9 Notifications (FR-NOTIF)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-NOTIF-01 | Système de notifications Laravel pour les utilisateurs | Must |
| FR-NOTIF-02 | Notifications par email pour les événements critiques (validation, alertes) | Should |

### 3.10 System Health & Diagnostics (FR-HEALTH)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-HEALTH-01 | Monitoring de la santé du système via **Spatie Laravel Health** | Must |
| FR-HEALTH-02 | Page de résultats des health checks accessible depuis le back-office | Must |
| FR-HEALTH-03 | Exécution et historisation des commandes via **Filament Command Runner** | Could |

---

## 4. Non-Functional Requirements

### 4.1 Performance Requirements (NFR-PERF)

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-PERF-01 | Temps de réponse moyen des pages du back-office | < 500 ms |
| NFR-PERF-02 | Temps de réponse des requêtes API | < 200 ms |
| NFR-PERF-03 | Temps de chargement initial du dashboard | < 2 s |
| NFR-PERF-04 | Support de la compression zstd + gzip | Must |

### 4.2 Security Requirements (NFR-SEC)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-SEC-01 | Toutes les communications doivent être en HTTPS (même en dev, certificat auto-signé) | Must |
| NFR-SEC-02 | HSTS, X-Frame-Options, CSP headers renforcés | Must |
| NFR-SEC-03 | Mots de passe hashés via bcrypt (12 rounds) | Must |
| NFR-SEC-04 | Rotation immédiate de `APP_KEY`, identifiants DB, Redis et SMTP en cas d'exposition | Must |
| NFR-SEC-05 | `APP_DEBUG=false` en production | Must |
| NFR-SEC-06 | Debugbar désactivée en production | Must |
| NFR-SEC-07 | Caches Laravel activés en production | Must |
| NFR-SEC-08 | Fichier `.env.production` ne doit jamais être versionné | Must |

### 4.3 Availability & Reliability (NFR-AVAIL)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-AVAIL-01 | Docker Compose pour la haute disponibilité des services | Must |
| NFR-AVAIL-02 | Redis pour sessions, cache et files — tolérance aux pannes | Should |
| NFR-AVAIL-03 | Health checks automatisés pour détecter les défaillances | Must |

### 4.4 Scalability (NFR-SCALE)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-SCALE-01 | Architecture conteneurisée permettant le scaling horizontal | Should |
| NFR-SCALE-02 | Cache Redis distribué pour sessions et données fréquentes | Should |

### 4.5 Maintainability (NFR-MAINT)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-MAINT-01 | Code PHP conforme à PSR (linting via Laravel Pint) | Must |
| NFR-MAINT-02 | Tests automatisés via Pest PHP | Must |
| NFR-MAINT-03 | Documentation inline et PHPDoc | Should |
| NFR-MAINT-04 | Migrations Laravel versionnées | Must |

### 4.6 Usability (NFR-UX)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-UX-01 | Interface back-office responsive (Filament + Tailwind CSS) | Must |
| NFR-UX-02 | Icônes Tabler Icons pour une cohérence visuelle | Should |
| NFR-UX-03 | Modal de confirmation pour les changements non sauvegardés | Should |
| NFR-UX-04 | Écran de verrouillage de session (Filament Lockscreen) | Could |
| NFR-UX-05 | Footer personnalisé dans le back-office | Could |

### 4.7 Internationalization (NFR-I18N)

| ID | Requirement | Priority |
|----|-------------|----------|
| NFR-I18N-01 | Support multilingue via le système de langues Laravel | Should |
| NFR-I18N-02 | Locale par défaut configurable | Should |

---

## 5. Technical Requirements

### 5.1 Backend Stack

| Composant | Version | Rôle |
|-----------|---------|------|
| PHP | 8.5 | Langage runtime |
| Laravel | 13.0 | Framework applicatif |
| Filament | 5.0 | Panel administration |
| PostgreSQL | Latest + PostGIS | Base de données relationnelle + spatiale |
| Redis | 7.0+ | Cache, sessions, files |
| FrankenPHP | Latest | Serveur HTTP + PHP SAPI |

### 5.2 Frontend Stack

| Composant | Version | Rôle |
|-----------|---------|------|
| Vite | 8.0 | Build tool |
| Tailwind CSS | 4.2.2 | Framework CSS |
| Laravel Vite Plugin | 3.0 | Intégration Laravel/Vite |

### 5.3 PHP Dependencies

#### Production

| Package | Version | Usage |
|---------|---------|-------|
| `filament/filament` | ^5.0 | Panel admin |
| `bezhansalleh/filament-shield` | ^4.2 | RBAC pour Filament |
| `spatie/laravel-permission` | *(via shield)* | Gestion des rôles/permissions |
| `spatie/laravel-health` | *(via plugin)* | Health monitoring |
| `shuvroroy/filament-spatie-laravel-health` | ^3.3 | Intégration health dans Filament |
| `binarybuilds/filament-command-runner` | ^1.2 | Exécution de commandes depuis Filament |
| `elemind/filament-echarts` | ^1.1 | Graphiques et visualisations |
| `nakanakaii/filament-countries` | ^1.0 | Sélection de pays |
| `daljo25/filament-tabler-icons` | ^3.41 | Icônes Tabler |
| `secondnetwork/blade-tabler-icons` | ^3.41 | Icônes Tabler pour Blade |
| `azgasim/filament-unsaved-changes-modal` | ^1.0 | Modal changements non sauvegardés |
| `diogogpinto/filament-auth-ui-enhancer` | ^2.0 | Amélioration UI auth |
| `devonab/filament-easy-footer` | ^2.2 | Footer personnalisé |
| `pxlrbt/filament-environment-indicator` | ^3.5 | Indicateur d'environnement |
| `dutchcodingcompany/filament-developer-logins` | ^2.1 | Logins développeur |
| `marjose123/filament-lockscreen` | * | Écran de verrouillage |
| `cms-multi/filament-clear-cache` | ^3.0 | Nettoyage du cache |
| `daljo25/filament-dependency-manager` | ^5.0 | Gestion des dépendances |
| `laravel/framework` | ^13.0 | Framework Laravel |
| `laravel/tinker` | ^3.0 | REPL interactif |

#### Développement

| Package | Version | Usage |
|---------|---------|-------|
| `pestphp/pest` | ^4.6 | Framework de tests |
| `pestphp/pest-plugin-laravel` | ^4.1 | Plugin Pest pour Laravel |
| `laravel/pint` | ^1.27 | Linting PHP (PSR) |
| `laravel/debugbar` | ^4.2 | Debug bar (dev uniquement) |
| `laravel/pail` | ^1.2.5 | Logs temps réel |
| `laravel/boost` | ^2.4 | Agentic development tools |
| `fakerphp/faker` | ^1.23 | Données de test |
| `nunomaduro/collision` | ^8.6 | Rapports d'erreurs détaillés |
| `mockery/mockery` | ^1.6 | Mocking pour tests |

### 5.4 Infrastructure Requirements

#### Développement Local

| Service | Image / Config | Port exposé |
|---------|---------------|-------------|
| Application | Custom FrankenPHP (Dockerfile) | 443 (HTTPS) |
| PostgreSQL + PostGIS | `postgis/postgis:latest` | 54321 (forwardé) |
| Redis | `redis:7-alpine` | Interne |
| Mailpit | `axllent/mailpit:latest` | 8026 (Web UI) |

#### Production

| Service | Image / Config | Notes |
|---------|---------------|-------|
| Application | Custom FrankenPHP | `docker-compose.prod.yml` |
| PostgreSQL + PostGIS | `postgis/postgis:latest` | Données persistées via volume |
| Redis | `redis:7-alpine` | Cache + sessions |

### 5.5 Environment Configuration

#### Variables critiques (dev)

```env
APP_NAME=MAMIAS
APP_ENV=local
APP_DEBUG=true
APP_URL=https://mamias.local

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=mamias_db
DB_USERNAME=admin_mamias
DB_PASSWORD=admin_251205

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database

REDIS_HOST=redis
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

#### Exigences production

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` généré et sécurisé
- Certificat SSL valide (non auto-signé)
- Base de données avec backups automatisés
- Redis sécurisé (mot de passe si exposé)

---

## 6. Database Requirements

### 6.1 Core Tables

| Table | Description |
|-------|-------------|
| `users` | Utilisateurs avec profils chercheurs |
| `cache` / `cache_locks` | Cache Laravel |
| `jobs` / `job_batches` / `failed_jobs` | File d'attente Laravel |
| `notifications` | Notifications utilisateurs |
| `roles` / `permissions` | RBAC (Spatie Permission) |
| `health_check_result_history_items` | Historique health checks (Spatie Health) |
| `command_runs` | Historique commandes exécutées |

### 6.2 Future Tables (NIS Domain)

| Table | Description |
|-------|-------------|
| `species` | Espèces marines (taxonomie WoRMS) |
| `introduction_events` | Événements d'introduction NIS |
| `pathways` | Classifications CBD pathways |
| `distributions` | Distributions géographiques (PostGIS) |
| `regions` | Sous-régions EcAp (WMED, CMED, ADRIA, EMED) |
| `literature_references` | Références bibliographiques |
| `establishment_records` | Suivi du succès d'établissement |

### 6.3 Spatial Data Requirements

- Utilisation de types géométriques PostGIS : `POINT`, `POLYGON`, `GEOMETRY`
- Index spatiaux (`GIST`) sur les colonnes géographiques
- Requêtes spatiales pour le filtrage par région

---

## 7. Interface Requirements

### 7.1 User Interfaces

| Interface | Technologie | Description |
|-----------|-------------|-------------|
| Back-office Admin | Filament 5 + Tailwind CSS | CRUD, dashboards, analytics |
| Front/public | Blade + Livewire + Vite | Consultation des données NIS |
| Mailpit UI | Web intégré | Capture et inspection des emails en dev |

### 7.2 API Interfaces

- API REST Laravel (prévue pour le partage Darwin Core)
- Format de réponse JSON standardisé
- Authentification API via Sanctum (si implémenté)

### 7.3 External System Interfaces

| Système | Type | Usage |
|---------|------|-------|
| WoRMS | API REST | Validation et enrichissement taxonomique |
| DOI Resolver | API REST | Récupération des métadonnées de littérature |
| CBD Standards | Spécifications | Classification des pathways |
| Darwin Core | Standard | Partage et export de données |

---

## 8. Acceptance Criteria

### 8.1 General Criteria

- [ ] L'application démarre correctement via `make dev-up`
- [ ] L'accès à `https://mamias.local` fonctionne (certificat auto-signé accepté)
- [ ] Le back-office Filament est accessible et fonctionnel
- [ ] Les migrations s'exécutent sans erreur
- [ ] Les seeds (si fournies) peuplent la base correctement
- [ ] Les tests Pest passent à 100%
- [ ] Le linting PHP (Pint) ne signale aucune erreur

### 8.2 Functional Criteria

- [ ] Un utilisateur peut s'inscrire, vérifier son email et se connecter
- [ ] Un administrateur peut créer/éditer/supprimer des utilisateurs et leurs rôles
- [ ] Les données taxonomiques peuvent être saisies et consultées
- [ ] Les événements d'introduction NIS peuvent être enregistrés avec pathway CBD
- [ ] Les données géographiques sont stockées et requêtables via PostGIS
- [ ] Les références littéraires sont associables aux espèces et événements
- [ ] L'import/export de données fonctionne correctement
- [ ] Le dashboard affiche des statistiques pertinentes

### 8.3 Non-Functional Criteria

- [ ] Temps de réponse < 500ms sur le back-office
- [ ] Aucune fuite d'information sensible en production (`APP_DEBUG=false`)
- [ ] Les headers de sécurité sont présents (HSTS, CSP, X-Frame-Options)
- [ ] La stack Docker production démarre sans erreur via `make prod-up`

---

## 9. Future Enhancements (Out of Scope v1)

| Feature | Description |
|---------|-------------|
| API publique documentée (OpenAPI/Swagger) | Exposition des données NIS via API REST |
| Mobile App | Application mobile pour la saisie sur le terrain |
| Machine Learning | Prédiction des risques d'invasion |
| Open Data Portal | Portail de données ouvertes pour le grand public |
| Multi-tenant | Support de plusieurs instances régionales |
| Workflow avancé | Workflow de validation multi-étapes avec notifications |

---

## 10. Appendix

### 10.1 Development Commands

| Action | Commande |
|--------|----------|
| Démarrer la stack dev | `make dev-up` |
| Arrêter la stack dev | `make dev-down` |
| Lancer les tests | `docker compose --profile dev exec app php artisan test` |
| Linting PHP | `docker compose --profile dev exec app ./vendor/bin/pint` |
| Logs temps réel | `docker compose --profile dev exec app php artisan pail` |
| Shell conteneur | `docker compose --profile dev exec app bash` |
| Compiler les assets | `docker compose --profile dev exec app npm run build` |

### 10.2 Production Commands

| Action | Commande |
|--------|----------|
| Déployer | `make prod-up` |
| Déployer (manuel) | `docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build` |

### 10.3 Database Access (Dev)

| Paramètre | Valeur |
|-----------|--------|
| Host | `localhost` |
| Port | `54321` |
| Database | `mamias_db` |
| Username | `admin_mamias` |
| Password | `admin_251205` |

---

*Document généré le 25/04/2026 — MAMIAS v1.0.0*
