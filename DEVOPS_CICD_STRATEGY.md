# DevOps & CI/CD Strategy

Adapted from [acquaintsoft.com/blog/devops-and-cicd-strategy-in-laravel](https://acquaintsoft.com/blog/devops-and-cicd-strategy-in-laravel)
for MAMIAS's actual topology. The source article assumes a traditional
multi-server Laravel deploy (Deployer symlink releases, a secrets manager,
Horizon, MySQL). MAMIAS is a **single Plesk VPS running Docker Compose**
(`docker-compose.prod.yml`) with PostgreSQL/PostGIS, a plain `queue:work`
container (no Horizon), and Plesk terminating TLS in front of it. Where the
two disagree, this document follows what MAMIAS actually is.

## Current state (as of this document)

- **Repo:** GitHub (`toufa123/mamias`), no CI configured before this change.
- **Deploy:** fully manual — SSH to the VPS, `git pull`, `make prod-up`
  (`docker compose --env-file .env.production -f docker-compose.prod.yml up -d
  --build`). The image is built **on the production server**, not pulled from
  a registry.
- **Migrations:** `AUTORUN_LARAVEL_MIGRATION=false` and `STARTUP_DB_GUARD=false`
  in `docker-compose.prod.yml` — migrations do **not** run automatically on
  deploy. There's no documented step for this in `README.md` either; today
  it's an implicit manual step.
- **Health check:** `/up` (Laravel's default) already exists
  (`apps/bootstrap/app.php`), and the `app` service's own Docker healthcheck
  already curls it. The `queue` container's healthcheck instead checks the
  worker process + Redis reachability (it never starts Caddy).
- **Backups:** already automated via the `db-backup` service
  (`kartoza/pg-backup`) in `docker-compose.prod.yml` — out of scope here.
- **Tests:** Pest, against a dedicated `mamias_test` **PostgreSQL** database
  (`apps/phpunit.xml`) — not SQLite. The schema uses PostGIS types SQLite
  can't express, so the article's "SQLite for unit tests" advice doesn't
  apply to this app at all, not even for the fast/unit tier.
- **Lint:** Laravel Pint (`vendor/bin/pint --dirty --format agent`). No
  PHPStan/Larastan is installed — static analysis is a possible future
  addition, not added here (avoids an unapproved dependency change).
- **Queue:** a single `queue:work` container, not Horizon. The article's
  `horizon:terminate` recipe has no equivalent to run — a new image just
  needs the `queue` container recreated.

## Phase 1 — CI (shipped in this change)

`.github/workflows/ci.yml`, three jobs on every push/PR to `main`/`master`:

1. **`lint`** — `vendor/bin/pint --test`.
2. **`test`** — Pest against a `kartoza/postgis:17-3.5` service container
   (same image family as dev/prod), env vars matched to what
   `apps/phpunit.xml` already forces (`CACHE_STORE=array`,
   `QUEUE_CONNECTION=sync`, `CAP_SITE_KEY=""`,
   `SHIELD_SUPER_ADMIN_VIA_GATE=true`, etc.) — only the Postgres connection
   coordinates are supplied by the workflow.
3. **`docker-build`** — builds the real `Dockerfile` with
   `--build-arg APP_BUILD=prod`, the same args `docker-compose.prod.yml`
   uses. No push. This is the one net-new safety net: today a broken image
   (bad Composer/npm/Vite step) is only discovered during `make prod-up` on
   the live server; this catches it on every PR instead.

This phase touches nothing in production and needs no secrets — safe to run
as-is.

## Phase 2 — Build & publish a versioned image (proposed, not wired yet)

`docker-compose.prod.yml` already parameterizes the app image:
`image: "${APP_IMAGE:-mamias-app:prod}"` alongside its `build:` block. That
means the path to registry-based deploys is a small step, not a rewrite:

- On merge to `master`, build the image once in CI and push it to GHCR
  (`ghcr.io/toufa123/mamias:<short-sha>` + a `:latest`/`:prod` tag), using
  the repo's built-in `GITHUB_TOKEN` — no new secret needed for this part.
- Set `APP_IMAGE=ghcr.io/toufa123/mamias:<tag>` in `.env.production`.
- Deploy becomes `docker compose --env-file .env.production -f
  docker-compose.prod.yml pull && ... up -d` instead of `--build` on the
  server. Faster, and the exact image that passed CI is what runs in
  production — no drift between "what was tested" and "what's deployed."
- **Rollback** falls out of this for free: point `APP_IMAGE` at the previous
  tag and `up -d` again. No Deployer/symlink machinery needed or wanted here
  — one Docker Compose service, one image tag, one `up -d`.

## Phase 3 — Automated deploy trigger (proposed, needs a decision + secrets)

Two ways to get Phase 2's image onto the VPS automatically; recommendation
first:

- **Self-hosted GitHub Actions runner on the VPS itself (recommended).** The
  runner polls GitHub rather than GitHub reaching in, so nothing new needs to
  be opened on the Plesk box's firewall — the app's HTTP port is already
  loopback-only by design (`docker-compose.prod.yml`), and this keeps that
  posture. The deploy job runs `docker compose pull && up -d` locally on the
  runner.
- **SSH action from a GitHub-hosted runner** (`appleboy/ssh-action` or
  similar). Simpler to set up, but requires opening inbound SSH from GitHub's
  (wide, published) IP ranges to the VPS and storing a deploy SSH key as a
  GitHub secret.

Either way, the deploy step must explicitly run the migration that today
happens nowhere automatically:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml \
  exec -T app php artisan migrate --force
```

...run **after** the new container is healthy (the existing `/up`
healthcheck) and **before** treating the deploy as done — mirroring the
article's Stage 4 post-deploy check: poll `/up`, fail (and stop short of
declaring success) on anything but 200.

Backward-compatible migrations still matter here for the same reason the
article gives: a destructive column drop/rename should be a two-deploy
change (add/dual-write → deploy → remove old column → deploy), since there's
a window where the old and new containers' code could both be running
against the same schema during a restart.

**Not doing this yet** — it's the one part of this strategy that reaches a
shared production system and needs a real decision (which trigger, what
secrets) before it's wired up.

## Secrets

The article recommends AWS Secrets Manager/Vault/Doppler for production
secrets. That's sized for a multi-service org, not a single-VPS app —
GitHub's built-in encrypted secrets are proportionate here. `.env.production`
already lives only on the server, never in the repo; that doesn't change.
The one new secret Phase 3 would need is a deploy credential (an SSH key, or
a runner registration token) — scoped to deploy only, nothing else.

## Explicitly skipped

- **Laravel Pennant / feature flags** — no current feature needs a staged
  rollout; adding a flags system speculatively would be exactly the kind of
  unrequested abstraction worth avoiding. Revisit if a genuinely risky
  feature needs a kill switch.
- **A staging environment** — none exists today. Worth having before Phase 3
  ships (so `migrate:rollback` and the deploy job itself get a dry run
  somewhere that isn't production), but that's infrastructure the user needs
  to provision (a second Plesk subdomain/compose project), not something to
  invent silently here.
- **Static analysis (PHPStan/Larastan)** — not currently a dependency; adding
  one wasn't requested, so Phase 1's `lint` job is Pint only.

## Correction found along the way

`AGENTS.md` states tests use "in-memory SQLite" — that's stale. Tests run
against PostgreSQL (`mamias_test`, per `apps/phpunit.xml`) because of PostGIS
types SQLite can't represent; `apps/CLAUDE.md` already documents this
correctly. Worth a one-line fix in `AGENTS.md` if you want it — didn't touch
it here since it's outside this task's scope.
