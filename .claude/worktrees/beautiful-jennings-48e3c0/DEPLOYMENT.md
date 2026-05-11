# Deployment (Production)

This project has two compose targets:

- `docker-compose.yml`: local development only
- `docker-compose.prod.yml`: production deployment

## Shortcuts (safer commands)

- `Makefile` targets: `dev-up`, `dev-down`, `prod-up`
- PowerShell script: `compose.ps1` with the same commands

Examples:

```bash
make dev-up
make dev-down
make prod-up
```

```powershell
.\compose.ps1 -Command dev-up
.\compose.ps1 -Command dev-down
.\compose.ps1 -Command prod-up
```

## 1) Prepare production env

1. Copy `.env.production.example` to `.env.production`.
2. Replace every `CHANGE_ME_*` value.
3. Keep `.env.production` out of git.

## 2) Validate config

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml config
```

## 3) Build and start

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml build
docker compose --env-file .env.production -f docker-compose.prod.yml up -d
```

## 4) Verify health

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml ps
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f app
```

Check app health endpoint:

- `https://<your-domain>/up`

## 5) Update rollout

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml pull
docker compose --env-file .env.production -f docker-compose.prod.yml up -d --build
```

## Notes

- Do not deploy with `docker-compose.yml`.
- Keep `APP_DEBUG=false` and Debugbar disabled in production.
- Rotate `APP_KEY`, DB, Redis, and SMTP credentials if exposed.

