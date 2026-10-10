#!/usr/bin/env bash
#  Chooses how a newly registered account is activated (AUTH_REGISTRATION_APPROVAL).
#
#    bash env-approval.sh dev    -> .env (+ apps/.env kept in sync)
#    bash env-approval.sh prod   -> .env.production
#
#  Reached from the menu as "make dev-approval" / "make prod-approval", and at
#  the end of "make dev-env" / "make prod-env". Safe to re-run: Enter keeps the
#  current choice.
#
#    email link     (false) the account verifies itself from an emailed link.
#                           Needs working outgoing mail.
#    admin approval (true)  no link is sent; super_admins are notified and
#                           verify the account from the Users table. For when
#                           outgoing mail is down or registrations need vetting.
#
#  Either way an unverified account cannot use the site until it is verified.
#  The setting is read when the containers start (production caches config), so
#  the change is applied by recreating app, queue and scheduler.
set -euo pipefail
cd "$(dirname "$0")"

key=AUTH_REGISTRATION_APPROVAL

mode="${1:-}"
case "$mode" in
    dev)
        files=(.env apps/.env)
        compose_cmd=(docker compose --profile dev -f docker-compose.yml)
        ;;
    prod)
        files=(.env.production)
        compose_cmd=(docker compose --env-file .env.production -f docker-compose.prod.yml)
        # Same reason as env-keys.sh: the Makefile exports the dev .env, and
        # compose would let those shell values override .env.production.
        if [ -f .env ]; then
            while IFS= read -r name; do unset "$name" 2>/dev/null || true; done \
                < <(grep -oE '^[A-Za-z_][A-Za-z0-9_]*=' .env | tr -d '=')
        fi
        ;;
    *)
        echo "usage: bash env-approval.sh dev|prod" >&2
        exit 1
        ;;
esac

[ -t 0 ] || { echo "env-approval: needs an interactive terminal." >&2; exit 1; }
[ -f "${files[0]}" ] || { echo "env-approval: ${files[0]} not found. Run 'make $mode-env' first." >&2; exit 1; }

# ── .env helpers (same as env-mail.sh) ───────────────────────────────────────

get_kv() { # file key -> value without surrounding quotes ("" when missing)
    local v
    v="$(grep -E "^$2=" "$1" 2>/dev/null | tail -1 | cut -d= -f2- || true)"
    v="${v#\'}"; v="${v%\'}"; v="${v#\"}"; v="${v%\"}"
    printf '%s' "$v"
}

set_kv() { # file key value — replace KEY=... in place, or append it
    local tmp
    tmp="$(mktemp)"
    K="$2" V="$3" awk '
        BEGIN { k = ENVIRON["K"]; v = ENVIRON["V"]; done = 0 }
        index($0, k "=") == 1 { print k "=" v; done = 1; next }
        { print }
        END { if (!done) print k "=" v }
    ' "$1" > "$tmp"
    cat "$tmp" > "$1" # rewrite in place: keeps the file's owner
    rm -f "$tmp"
}

# ── 1. Choose ────────────────────────────────────────────────────────────────

current="$(get_kv "${files[0]}" "$key")"
case "$current" in
    true | 1) current=true; default=2 ;;
    *) current=false; default=1 ;;
esac

echo
echo "New account activation for ${files[0]}"
if [ "$current" = true ]; then echo "  current: admin approval"; else echo "  current: email link"; fi
echo "  1) Email link      the user verifies their own address (needs outgoing mail)"
echo "  2) Admin approval  super_admins verify new accounts from the Users table"
read -r -p "  Choice [$default]: " choice
case "${choice:-$default}" in
    1) value=false ;;
    2) value=true ;;
    *) echo "  -> not 1 or 2; nothing written"; exit 1 ;;
esac

if [ "$value" = "$current" ]; then
    echo "  -> unchanged"
    exit 0
fi

for file in "${files[@]}"; do
    [ -f "$file" ] && set_kv "$file" "$key" "$value"
done
echo "  -> $key=$value saved"

# ── 2. Apply ─────────────────────────────────────────────────────────────────

if [ -z "$("${compose_cmd[@]}" ps --status running -q app 2>/dev/null)" ]; then
    echo "  The stack is not running; it takes effect on the next 'make $mode-up'."
    exit 0
fi

read -r -p "Apply now (recreates app, queue, scheduler)? [Y/n] " answer
if [[ ${answer:-y} =~ ^[Nn] ]]; then
    echo "Apply later with: make $mode-up"
    exit 0
fi

echo "  Recreating app, queue and scheduler (up to 3 minutes)..."
"${compose_cmd[@]}" up -d --force-recreate --wait --wait-timeout 180 app queue scheduler </dev/null >/dev/null \
    || { echo "  -> the containers did not become healthy; check: docker logs mamias_app" >&2; exit 1; }

applied="$("${compose_cmd[@]}" exec -T app php artisan tinker --execute 'echo var_export(config("auth.registration_approval"), true);' </dev/null 2>/dev/null | tail -1 || true)"
if [ "$applied" = "$value" ]; then
    echo "  -> applied: the app now uses $([ "$value" = true ] && echo "admin approval" || echo "the email link")"
else
    echo "  -> recreated, but the app reports '$applied'; check ${files[0]}" >&2
    exit 1
fi
