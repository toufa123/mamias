<p align="center">
  <img src="/apps/public/images/Logoweb.png" alt="MAMIAS Logo" width="180">
</p>
<p align="center">
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-yellow.svg" alt="License: MIT"></a>
  <a href="#"><img src="https://img.shields.io/badge/version-1.0.0-blue.svg" alt="Version: 1.0.0"></a>
</p>

<p align="center"><em>Marine Alien Species Database (MAMIAS) — propulsée par Laravel 13, Filament 5 &amp; PostGIS.</em></p>

---

**MAMIAS** (Marine Alien Species Database) est une application web complète dédiée à la gestion, au catalogage et à la diffusion d'informations sur les espèces marines exotiques et envahissantes (*Non-Indigenous Species — NIS*) en région méditerranéenne.

Le système sert de base de données scientifique pour les chercheurs, taxonomistes et biologistes marins afin de suivre la taxonomie, la distribution géographique et les événements d'introduction des espèces non indigènes.

## Fonctionnalités clés

- **Gestion taxonomique** : CRUD des espèces marines avec synchronisation automatique via l'API **WoRMS** (World Register of Marine Species).
- **Suivi des introductions** : Enregistrement des événements d'introduction avec classification selon les **CBD pathways**.
- **Monitoring régional** : Suivi par sous-régions **EcAp** (WMED, CMED, ADRIA, EMED) et succès d'établissement.
- **Visualisation & analytics** : Tableaux de bord statistiques et cartographie des distributions NIS.
- **Gestion bibliographique** : Intégration **Crossref DOI** pour la récupération automatique des métadonnées de littérature.
- **Export & reporting** : Rapports conformes CBD et partage de données au format **Darwin Core**.

Pour plus de détails fonctionnels, consultez la **[Spécification des Besoins (SRS)](requirements.md)**.

## Stack technique

| Couche           | Technologie            | Badge |
|------------------|------------------------|-------|
| **Backend**      | PHP 8.5 + Laravel 13   | [![PHP: 8.5](https://img.shields.io/badge/PHP-8.5-777BB4.svg?logo=php)](https://www.php.net) [![Laravel: 13.0](https://img.shields.io/badge/Laravel-13.0-FF2D20.svg?logo=laravel)](https://laravel.com) |
| **Panel admin**  | Filament 5.0           | [![Filament: 5.0](https://img.shields.io/badge/Filament-5.0-F1B024.svg?logo=filament)](https://filamentphp.com) |
| **Base de données** | PostgreSQL + PostGIS | [![PostGIS: ^3.0](https://img.shields.io/badge/PostGIS-^3.0-336791.svg?logo=postgresql)](https://postgis.net) |
| **Cache / Files**| Redis                  | [![Redis: ^7.0](https://img.shields.io/badge/Redis-^7.0-DC382D.svg?logo=redis)](https://redis.io) |
| **Frontend**     | Vite 8 + Tailwind CSS 4.2 | [![Vite: 8.0](https://img.shields.io/badge/Vite-8.0-646CFF.svg?logo=vite)](https://vitejs.dev) [![Tailwind CSS: 4.2.2](https://img.shields.io/badge/Tailwind_CSS-4.2.2-38B2AC.svg?logo=tailwind-css)](https://tailwindcss.com) |
| **Runtime**      | Docker + FrankenPHP    | — |

## Architecture du projet

Le point d'entrée principal de l'application est le **panel Filament** situé à l'adresse **`/mamias`**.

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

---

## Démarrage rapide (Développement local)

### 0. Prérequis

- Docker & Docker Compose
- `make` disponible sur votre machine
- Accès en local aux ports `443` (HTTPS), `54321` (PostGIS dev), `8026` (Mailpit web)

### 1. Installation

1. **Cloner le dépôt** :
   ```bash
   git clone <url-du-depot> mamias
   cd mamias
   ```

2. **Préparer l'environnement** :
   ```bash
   cp apps/.env.example apps/.env
   ```

3. **Lancer la stack** :
   ```bash
   make dev-up
   ```

4. **Configurer le domaine local** :
   Ajoutez `mamias.local` à votre fichier hosts :
   - **Windows (PowerShell admin)** : `Add-Content C:\Windows\System32\drivers\etc\hosts "127.0.0.1 mamias.local"`
   - **Linux/macOS** : `echo "127.0.0.1 mamias.local" | sudo tee -a /etc/hosts`

### 2. Accès aux services

| Service                | URL                             |
|------------------------|---------------------------------|
| Application (panel)    | [https://mamias.local/mamias](https://mamias.local/mamias) |
| Healthcheck applicatif | [https://mamias.local/up](https://mamias.local/up)         |
| Mailpit (web UI)       | [http://localhost:8026](http://localhost:8026)           |

*Note : Lors de la première visite, acceptez le certificat auto-signé.*

### 3. Commandes utiles

| Action                        | Commande |
|-------------------------------|----------|
| Arrêter la stack              | `make dev-down` |
| Nettoyage complet             | `make dev-clean` |
| Logs applicatifs              | `docker compose --profile dev logs -f app` |
| Exécuter les tests            | `docker compose --profile dev exec app php artisan test` |
| Reconstruire les caches       | `make dev-cache` |
| Vider tous les caches         | `make dev-clear` |
| Démarrer un worker de queue   | `make dev-queue` |

---

## Déploiement (Production)

### 1. Configuration

En production, utilisez exclusivement `docker-compose.prod.yml` et un fichier `.env.production`.

1. **Fichiers d'environnement** :
   - À la racine : `.env` (pour les variables Docker).
   - Dans `apps/` : `.env.production` (pour Laravel).

2. **Secrets indispensables** :
   Assurez-vous de modifier :
   - `APP_KEY` (générez-le via `php artisan key:generate --show`)
   - Identifiants **DB** (`DB_PASSWORD`, `DB_USERNAME`)
   - Mot de passe **Redis**
   - Paramètres **SMTP** et **Worms API keys** (le cas échéant).

### 2. Lancement

```bash
docker compose -f docker-compose.prod.yml --env-file .env.production up -d
```

### 3. Maintenance & Sécurité

- **Mise à jour** : `docker compose -f docker-compose.prod.yml pull && docker compose -f docker-compose.prod.yml up -d`
- **Backups** : Les backups sont gérés via le service `db-backup` (consultez `docker-compose.prod.yml`).
- **Sécurité** : Gardez `APP_DEBUG=false` et assurez-vous que les ports sensibles ne sont pas exposés publiquement sans pare-feu.

---

## Captures d'écran (Aperçu)

> *Cette section est indicative : ajoutez vos propres captures une fois disponibles.*

- **Dashboard Filament** : Vue d’ensemble des indicateurs clés.
- **Fiche espèce NIS** : Détails taxonomiques et distribution.
- **Carte PostGIS** : Visualisation spatiale des introductions.

---

## Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. **Forkez** le projet.
2. Créez une **branche** pour votre fonctionnalité (`git checkout -b feature/ma-feature`).
3. Vérifiez la conformité du code (`make dev-cache` et tests).
4. Soumettez une **Pull Request** détaillée.

**Bonnes pratiques :**
- Suivez les standards PSR et utilisez Laravel Pint pour le formatage.
- Ajoutez des tests pour toute nouvelle logique complexe.
- Documentez vos changements dans le code (PHPDoc).

---

## Roadmap

- **v1.x (En cours)** :
  - Stabilisation du noyau NIS et synchronisation WoRMS.
  - Cartographie de base via PostGIS.
  - Export Darwin Core.
- **v2.x (Prévu)** :
  - API publique documentée (OpenAPI).
  - Tableaux de bord analytiques avancés.
  - Module de reporting automatique pour les autorités.

---

## Licence

Ce projet est distribué sous licence **MIT**. Voir le fichier [LICENSE](https://opensource.org/licenses/MIT) pour plus de détails.

---

<p align="center"><em>Propulsé par Laravel 13 &amp; FrankenPHP — conçu pour la science des espèces marines non indigènes.</em></p>
