<p align="center">
  <img src="/apps/public/images/Logoweb.png" alt="MAMIAS Logo" width="180">
</p>
<p align="center">
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT"></a>
  <a href="#"><img src="https://img.shields.io/badge/version-1.0.0-blue.svg" alt="Version: 1.0.0"></a>
</p>

<p align="center"><em>Application web propulsée par Laravel 13, Filament 5 & PostGIS — servie par FrankenPHP sous Docker.</em></p>

---

## Vue d'ensemble

Ce dépôt contient une application **Laravel 13** intégrant **Filament 5.0** et **PostGIS**.  
Le code source se trouve dans le répertoire `apps/` et est conçu pour s'exécuter dans un environnement **Dockerisé** avec **FrankenPHP**.

L'URL de développement local par défaut est **https://mamias.local** (certificat auto-signé à accepter au premier accès).

## Stack technique

| Couche | Technologie | Badge |
|--------|-------------|-------|
| **Backend** | PHP 8.5 + Laravel 13 | [![PHP: 8.5](https://img.shields.io/badge/PHP-8.5-777BB4.svg?logo=php)](https://www.php.net) [![Laravel: 13.0](https://img.shields.io/badge/Laravel-13.0-FF2D20.svg?logo=laravel)](https://laravel.com) |
| **Panel admin** | Filament 5.0 (Shield, Tabler Icons, Auth UI Enhancer…) | [![Filament: 5.0](https://img.shields.io/badge/Filament-5.0-F1B024.svg?logo=filament)](https://filamentphp.com) |
| **Base de données** | PostgreSQL + PostGIS | [![PostGIS: ^3.0](https://img.shields.io/badge/PostGIS-^3.0-336791.svg?logo=postgresql)](https://postgis.net) |
| **Cache / Sessions / Files** | Redis | [![Redis: ^7.0](https://img.shields.io/badge/Redis-^7.0-DC382D.svg?logo=redis)](https://redis.io) |
| **Mail (dev)** | Mailpit (SMTP catcher + Web UI) | — |
| **Frontend** | Vite 8 + Tailwind CSS 4.2 | [![Vite: 8.0](https://img.shields.io/badge/Vite-8.0-646CFF.svg?logo=vite)](https://vitejs.dev) [![Tailwind CSS: 4.2.2](https://img.shields.io/badge/Tailwind_CSS-4.2.2-38B2AC.svg?logo=tailwind-css)](https://tailwindcss.com) |
| **Tests** | Pest PHP 4.4 | — |
| **Runtime** | Docker + Docker Compose + FrankenPHP | — |

## Architecture du projet

```
mamias/
├── apps/                       # Code source Laravel (monté sur /var/www/html)
│   ├── app/                    # Models, Controllers, Providers, Filament...
│   ├── bootstrap/              # Bootstrapping de l'application
│   ├── config/                 # Fichiers de configuration
│   ├── database/               # Migrations, seeders, factories
│   ├── public/                 # Point d'entrée (index.php) + assets compilés
│   ├── resources/              # Vues Blade, composants Livewire, assets CSS/JS
│   ├── routes/                 # web.php, api.php, console.php
│   ├── storage/                # Logs, cache, sessions, fichiers uploadés
│   └── tests/                  # Tests Pest/PHPUnit
│
├── backup/                     # Dumps SQL pour l'initialisation PostGIS
├── .env                        # Variables d'environnement Docker (dev)
├── .env.production.example     # Template pour le déploiement production
├── docker-compose.yml          # Stack de développement local (app + queue + db + redis + mail)
├── docker-compose.prod.yml     # Stack de production
├── Dockerfile                  # Image custom FrankenPHP + extensions PHP
├── entrypoint.sh               # Wrapper d'entrée (permissions + UID/GID + cache Filament + queue worker)
└── Makefile                    # Raccourcis : dev-up, dev-down, dev-cache, dev-clear, dev-queue, prod-up
```

## Démarrage rapide (Développement local)

### Pré-requis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (ou Docker Engine + Compose)
- [Make](https://www.gnu.org/software/make/) (optionnel, pour les raccourcis)

### 1. Créer le fichier d'environnement Laravel

```bash
cp apps/.env.example apps/.env
```

> Le fichier racine `.env` est déjà versionné avec les valeurs de développement local.

### 2. Lancer la stack

Avec **Make** :

```bash
make dev-up
```

Sans Make :

```bash
docker compose --profile dev up -d --build
```

### 3. Configurer le domaine local

Ajouter `mamias.local` à votre fichier hosts :

**Linux / macOS :**
```bash
echo "127.0.0.1 mamias.local" | sudo tee -a /etc/hosts
```

**Windows (PowerShell admin) :**
```powershell
Add-Content C:\Windows\System32\drivers\etc\hosts "127.0.0.1 mamias.local"
```

### 4. Accéder aux services

| Service | URL |
|---------|-----|
| Application | https://mamias.local *(accepter le certificat auto-signé)* |
| Mailpit (courriels capturés) | http://localhost:8026 |

### 5. Commandes utiles en dev

| Action | Commande |
|--------|----------|
| Arrêter la stack | `make dev-down` |
| Reconstruire l'image | `docker compose --profile dev up -d --build` |
| Logs de l'application | `docker compose --profile dev logs -f app` |
| Logs du worker de queue | `docker compose --profile dev logs -f queue` |
| Shell dans le conteneur app | `docker compose --profile dev exec app bash` |
| Exécuter une commande Artisan | `docker compose --profile dev exec app php artisan <command>` |
| Exécuter les tests | `docker compose --profile dev exec app php artisan test` |
| Compiler les assets | `docker compose --profile dev exec app npm run build` |
| Reconstruire les caches | `make dev-cache` |
| Vider tous les caches | `make dev-clear` |
| Lancer un worker queue (manuel) | `make dev-queue` |

### Accès direct à la base de données

- **Host** : `localhost`
- **Port** : `54321` (forwardé vers le conteneur PostGIS)
- **Base de données** : `mamias_db`
- **Utilisateur** : `admin_mamias`
- **Mot de passe** : `admin_251205`

## Déploiement en production

Voir le fichier **[DEPLOYMENT.md](DEPLOYMENT.md)** pour le guide complet.

Résumé :

1. Copier et renseigner `.env.production` :
   ```bash
   cp .env.production.example .env.production
   # Éditer et remplacer tous les CHANGE_ME_*
   ```

2. Déployer avec Make :
   ```bash
   make prod-up
   ```

   Ou manuellement :
   ```bash
   docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
   ```

> **⚠️ Attention** : Ne jamais utiliser `docker-compose.yml` en production. Utiliser uniquement `docker-compose.prod.yml`.

## Caractéristiques de l'image Docker

L'image est basée sur [`serversideup/php:8.5-frankenphp`](https://serversideup.net/open-source/docker-php/docs/) et inclut :

- Extensions PHP supplémentaires : `soap`, `intl`, `bcmath`, `redis`
- Support PostGIS (via `pdo_pgsql` + librairies système)
- Gestion automatique des permissions via `entrypoint.sh`
- Remappage UID/GID pour éviter les fichiers root sur le host
- **OPcache activé** en développement avec validation des timestamps
- **Cache des composants Filament** au démarrage (`filament:cache-components`)
- **Détection automatique du mode queue worker** dans `entrypoint.sh`
- Compression `zstd` + `gzip`
- Headers de sécurité renforcés (HSTS, X-Frame-Options, CSP, etc.)

## Tests

```bash
# Dans le conteneur
docker compose --profile dev exec app php artisan test

# Ou via Pest directement
docker compose --profile dev exec app ./vendor/bin/pest
```

## Outils de développement

| Outil | Commande / Accès |
|-------|------------------|
| Debugbar | Activé automatiquement en `APP_DEBUG=true` |
| Laravel Pail (logs temps réel) | `docker compose --profile dev exec app php artisan pail` |
| Laravel Tinker (REPL) | `docker compose --profile dev exec app php artisan tinker` |
| Pint (linting PHP) | `docker compose --profile dev exec app ./vendor/bin/pint` |
| Filament Upgrade | `docker compose --profile dev exec app php artisan filament:upgrade` |

## Sécurité

- `APP_KEY`, identifiants DB, Redis et SMTP doivent être rotés immédiatement en cas d'exposition.
- `.env.production` ne doit **jamais** être versionné.
- En production : `APP_DEBUG=false`, Debugbar désactivé, et caches Laravel activés.

## Licence

Ce projet est sous licence [MIT](https://opensource.org/licenses/MIT).

---

<p align="center"><em>Propulsé par Laravel 13 & FrankenPHP</em></p>
