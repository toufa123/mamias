<p align="center">
  <img src="/apps/public/images/Logoweb.png" alt="MAMIAS Logo" width="180">
</p>
<p align="center">
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT"></a>
  <a href="#"><img src="https://img.shields.io/badge/version-1.0.0-blue.svg" alt="Version: 1.0.0"></a>
</p>

<p align="center"><em>Marine Alien Species Database (MAMIAS) — Propulsée par Laravel 13, Filament 5 & PostGIS.</em></p>

---

## Vue d'ensemble

**MAMIAS** (Marine Alien Species Database) est une application web complète dédiée à la gestion, au catalogage et à la diffusion d'informations sur les espèces marines exotiques et envahissantes (*Non-Indigenous Species — NIS*) en région méditerranéenne.

Le système sert de base de données scientifique pour les chercheurs, taxonomistes et biologistes marins afin de suivre la taxonomie, la distribution géographique et les événements d'introduction des espèces non indigènes.

Ce dépôt contient le code source (**Laravel 13** + **Filament 5**) conçu pour s'exécuter dans un environnement **Dockerisé** avec **FrankenPHP**.

## Fonctionnalités clés

- **Gestion taxonomique** : CRUD des espèces marines avec synchronisation automatique via l'API **WoRMS** (World Register of Marine Species).
- **Suivi des introductions** : Enregistrement des événements d'introduction avec classification selon les **CBD pathways**.
- **Monitoring régional** : Suivi par sous-régions **EcAp** (WMED, CMED, ADRIA, EMED) et succès d'établissement.
- **Visualisation & Analytics** : Tableaux de bord statistiques et cartographie des distributions NIS.
- **Gestion bibliographique** : Intégration **Crossref DOI** pour la récupération automatique des métadonnées de littérature.
- **Export & Reporting** : Rapports conformes CBD et partage de données au format **Darwin Core**.

Pour plus de détails, consultez la **[Spécification des Besoins (SRS)](requirements.md)**.

## Stack technique

| Couche | Technologie | Badge |
|--------|-------------|-------|
| **Backend** | PHP 8.5 + Laravel 13 | [![PHP: 8.5](https://img.shields.io/badge/PHP-8.5-777BB4.svg?logo=php)](https://www.php.net) [![Laravel: 13.0](https://img.shields.io/badge/Laravel-13.0-FF2D20.svg?logo=laravel)](https://laravel.com) |
| **Panel admin** | Filament 5.0 | [![Filament: 5.0](https://img.shields.io/badge/Filament-5.0-F1B024.svg?logo=filament)](https://filamentphp.com) |
| **Base de données** | PostgreSQL + PostGIS | [![PostGIS: ^3.0](https://img.shields.io/badge/PostGIS-^3.0-336791.svg?logo=postgresql)](https://postgis.net) |
| **Cache / Files** | Redis | [![Redis: ^7.0](https://img.shields.io/badge/Redis-^7.0-DC382D.svg?logo=redis)](https://redis.io) |
| **Frontend** | Vite 8 + Tailwind CSS 4.2 | [![Vite: 8.0](https://img.shields.io/badge/Vite-8.0-646CFF.svg?logo=vite)](https://vitejs.dev) [![Tailwind CSS: 4.2.2](https://img.shields.io/badge/Tailwind_CSS-4.2.2-38B2AC.svg?logo=tailwind-css)](https://tailwindcss.com) |
| **Runtime** | Docker + FrankenPHP | — |

## Architecture du projet

Le point d'entrée principal de l'application est le panel Filament situé à l'adresse **/mamias**.

```
mamias/
├── apps/                       # Code source Laravel
│   ├── app/                    # Models, Services (Worms, WhatsApp), Filament...
│   ├── bootstrap/              # Configuration du boot (routes web/console/up)
│   ├── config/                 # Configurations (services, backup...)
│   ├── database/               # Migrations PostGIS, seeders
│   ├── public/                 # Assets (Vite)
│   ├── resources/              # Vues Blade & thèmes Filament
│   └── tests/                  # Tests (in-memory SQLite)
├── backup/                     # Dumps SQL pour l'initialisation de la DB
├── docker-compose.yml          # Stack de développement
├── docker-compose.prod.yml     # Stack de production
├── Dockerfile                  # Image FrankenPHP optimisée
└── entrypoint.sh               # Gestion des permissions & cache Filament
```

## Démarrage rapide (Développement local)

### 1. Préparer l'environnement

```bash
cp apps/.env.example apps/.env
```

### 2. Lancer la stack

```bash
make dev-up
```

### 3. Configurer le domaine

Ajouter `mamias.local` à votre fichier hosts :
- **Windows** : `Add-Content C:\Windows\System32\drivers\etc\hosts "127.0.0.1 mamias.local"`
- **Linux/macOS** : `echo "127.0.0.1 mamias.local" | sudo tee -a /etc/hosts`

### 4. Accès aux services

| Service | URL |
|---------|-----|
| Application (Panel) | **https://mamias.local/mamias** |
| Mailpit | http://localhost:8026 |

## Commandes utiles

| Action | Commande |
|--------|----------|
| Arrêter la stack | `make dev-down` |
| Logs applicatifs | `docker compose --profile dev logs -f app` |
| Exécuter les tests | `docker compose --profile dev exec app php artisan test` |
| Gérer les caches | `make dev-cache` / `make dev-clear` |
| Worker de queue | `make dev-queue` |

## Déploiement (Production)

Le déploiement utilise Docker Compose avec des configurations de production spécifiques.

### 1. Raccourcis (Commandes sécurisées)

- **Makefile** : `make prod-up`
- **PowerShell** : `.\compose.ps1 -Command prod-up`

### 2. Préparer l'environnement

1. Copiez `.env.production.example` vers `.env.production`.
2. Remplacez toutes les valeurs `CHANGE_ME_*`.
3. Gardez `.env.production` hors de git.

```bash
cp apps/.env.production.example .env.production
```

### 3. Valider et Lancer

**Validation de la config :**
```bash
docker compose --env-file .env.production -f docker-compose.prod.yml config
```

**Démarrage :**
```bash
make prod-up
# Ou manuellement
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

### 4. Vérifier la santé

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml ps
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f app
```

Endpoint de santé : `https://<votre-domaine>/up`

### 5. Mise à jour (Rollout)

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml pull
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

### Notes de sécurité

- Ne déployez **jamais** avec `docker-compose.yml`.
- Gardez `APP_DEBUG=false` et Debugbar désactivé en production.
- Effectuez une rotation des clés `APP_KEY`, DB, Redis, et SMTP si elles sont exposées.

---

<p align="center"><em>Propulsé par Laravel 13 & FrankenPHP</em></p>
