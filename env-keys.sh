#!/usr/bin/env bash
#  Interactive setup of the application key and the Cap CAPTCHA keys.
#
#    bash env-keys.sh dev    -> .env (+ apps/.env kept in sync)
#    bash env-keys.sh prod   -> .env.production
#
#  Reached from the menu as "make dev-keys" / "make prod-keys", and at the end
#  of "make dev-env" / "make prod-env". Safe to re-run: every value that is
#  already set is kept unless you choose to replace it.
#
#  Cap site keys are issued by the Cap server itself and only that server
#  accepts them, so they are provisioned through the Cap API of the stack being
#  configured (dev or prod) — a dev key is useless in production. The API is
#  called from inside the cap container (it runs Bun), because production Cap
#  publishes no port at all.
#
#  Secrets are written to the files only; this script never prints one.
set -euo pipefail
cd "$(dirname "$0")"

mode="${1:-}"
case "$mode" in
    dev)
        files=(.env apps/.env)
        compose_cmd=(docker compose --profile dev -f docker-compose.yml)
        ;;
    prod)
        files=(.env.production)
        compose_cmd=(docker compose --env-file .env.production -f docker-compose.prod.yml)
        # The Makefile exports every variable of the root (dev) .env, and
        # compose gives the shell environment precedence over --env-file:
        # left alone, dev secrets would override .env.production here.
        # Bash's own read-only variables (UID, EUID…) can't be unset and are
        # never compose inputs, so a failed unset is skipped, not fatal.
        if [ -f .env ]; then
            while IFS= read -r name; do unset "$name" 2>/dev/null || true; done \
                < <(grep -oE '^[A-Za-z_][A-Za-z0-9_]*=' .env | tr -d '=')
        fi
        ;;
    *)
        echo "usage: bash env-keys.sh dev|prod" >&2
        exit 2
        ;;
esac

primary="${files[0]}"

[ -t 0 ] || { echo "env-keys: needs an interactive terminal." >&2; exit 1; }
command -v openssl >/dev/null || { echo "env-keys: openssl is required." >&2; exit 1; }
[ -f "$primary" ] || {
    echo "env-keys: $primary not found. Run 'make ${mode}-env' first." >&2
    exit 1
}

# ── .env helpers ─────────────────────────────────────────────────────────────

get_kv() { # file key -> value ("" when missing)
    grep -E "^$2=" "$1" 2>/dev/null | tail -1 | cut -d= -f2- || true
}

# Replace KEY=... in place, or append it. The value travels through ENVIRON so
# awk never interprets backslashes, "&" or "|" in it (sed would).
set_kv() { # file key value
    local file="$1" tmp
    tmp="$(mktemp)"
    K="$2" V="$3" awk '
        BEGIN { k = ENVIRON["K"]; v = ENVIRON["V"]; done = 0 }
        index($0, k "=") == 1 { print k "=" v; done = 1; next }
        { print }
        END { if (!done) print k "=" v }
    ' "$file" > "$tmp"
    cat "$tmp" > "$file" # rewrite in place: keeps the file's owner
    rm -f "$tmp"
}

# Write to every env file this mode keeps in sync.
set_all() { # key value
    local f
    for f in "${files[@]}"; do
        if [ -f "$f" ]; then
            set_kv "$f" "$1" "$2"
        fi
    done
}

is_unset() { # value -> true when empty or still an example placeholder
    [ -z "$1" ] || [[ $1 == *CHANGE_ME* ]] || [[ $1 == pending-provisioning ]]
}

state() { is_unset "$1" && echo "not set" || echo "set"; }

ask_yes() { # prompt default(y|n)
    local answer hint="[y/N]"
    [ "$2" = y ] && hint="[Y/n]"
    read -r -p "$1 $hint " answer
    answer="${answer:-$2}"
    [[ $answer =~ ^[Yy] ]]
}

# docker compose with the CURRENT file values. The shell may hold stale ones
# (make exported them before this script changed the file), and compose's
# required-variable check would refuse to run at all while the site key is
# still empty — which is exactly when Cap must be started to issue one.
compose() {
    local site secret
    site="$(get_kv "$primary" CAP_SITE_KEY)"
    secret="$(get_kv "$primary" CAP_SECRET_KEY)"
    is_unset "$site" && site=pending-provisioning
    is_unset "$secret" && secret=pending-provisioning

    APP_KEY="$(get_kv "$primary" APP_KEY)" \
    CAP_ADMIN_KEY="$(get_kv "$primary" CAP_ADMIN_KEY)" \
    CAP_SITE_KEY="$site" \
    CAP_SECRET_KEY="$secret" \
        "${compose_cmd[@]}" "$@"
}

changed=0

echo
echo "Key setup for $mode ($(printf '%s ' "${files[@]}"))"
echo "Values are never shown. Press Enter to accept the [default]."

# ── 1. APP_KEY ───────────────────────────────────────────────────────────────

echo
app_key="$(get_kv "$primary" APP_KEY)"
echo "APP_KEY: $(state "$app_key")"
if is_unset "$app_key"; then
    set_all APP_KEY "base64:$(openssl rand -base64 32)"
    echo "  -> generated"
    changed=1
elif ask_yes "  Replace it? Everyone is logged out and links already sent (password reset, verification) stop working." n; then
    set_all APP_KEY "base64:$(openssl rand -base64 32)"
    echo "  -> replaced"
    changed=1
fi

# ── 2. CAP_ADMIN_KEY (Cap dashboard login) ──────────────────────────────────

echo
admin_key="$(get_kv "$primary" CAP_ADMIN_KEY)"
echo "CAP_ADMIN_KEY (Cap dashboard login): $(state "$admin_key")"
if is_unset "$admin_key" || ask_yes "  Replace it?" n; then
    read -r -s -p "  New value (hidden; blank = generate a strong one): " entered
    echo
    if [ -z "$entered" ]; then
        entered="$(openssl rand -hex 32)"
        echo "  -> generated"
    elif [[ $entered == *'$'* || ${#entered} -lt 12 ]]; then
        # "$" would be expanded by docker compose; short keys guard a dashboard
        # that can issue CAPTCHA keys.
        echo "  -> rejected (must be 12+ characters and contain no '\$'); generated one instead"
        entered="$(openssl rand -hex 32)"
    fi
    set_all CAP_ADMIN_KEY "$entered"
    unset entered
    changed=1
fi

# ── 3. CAP_SITE_KEY + CAP_SECRET_KEY (issued by this stack's Cap server) ────

provision() {
    # </dev/null on these docker calls: `compose exec` forwards the terminal's
    # input into the container, so it would swallow answers typed ahead for
    # the prompts that follow.
    echo "  Starting cap + cap-valkey..."
    compose up -d cap cap-valkey </dev/null >/dev/null

    local i
    for i in $(seq 1 30); do
        compose exec -T cap bun -e \
            'fetch("http://localhost:3000/").then(() => process.exit(0), () => process.exit(1))' \
            </dev/null >/dev/null 2>&1 && break
        [ "$i" -eq 30 ] && { echo "  Cap did not become ready - skipped." >&2; return 1; }
        sleep 2
    done

    # Runs inside the cap container, which already holds ADMIN_KEY in its
    # environment. Prints "siteKey<TAB>secretKey" on success; captured below,
    # never displayed.
    local keys
    keys="$(compose exec -T -e KEY_NAME="mamias-$mode-$(date +%Y%m%d-%H%M)" cap bun run - <<'JS'
const base = "http://localhost:3000";
const login = await (await fetch(`${base}/auth/login`, {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ admin_key: process.env.ADMIN_KEY }),
})).json();
if (!login.success) {
  console.error("Cap rejected the admin key.");
  process.exit(1);
}
const res = await fetch(`${base}/server/keys`, {
  method: "POST",
  headers: {
    Authorization: "Bearer " + btoa(JSON.stringify({ token: login.session_token, hash: login.hashed_token })),
    "Content-Type": "application/json",
  },
  body: JSON.stringify({ name: process.env.KEY_NAME }),
});
const key = await res.json();
if (!res.ok || !key.siteKey || !key.secretKey) {
  console.error(`Cap did not issue a key (HTTP ${res.status}).`);
  process.exit(1);
}
console.log(`${key.siteKey}\t${key.secretKey}`);
JS
    )" || { echo "  Provisioning failed - nothing was written." >&2; return 1; }

    set_all CAP_SITE_KEY "${keys%%$'\t'*}"
    set_all CAP_SECRET_KEY "${keys#*$'\t'}"
    unset keys
    echo "  -> new site key issued by the $mode Cap server and saved"
}

echo
site_key="$(get_kv "$primary" CAP_SITE_KEY)"
secret_key="$(get_kv "$primary" CAP_SECRET_KEY)"
if is_unset "$site_key" || is_unset "$secret_key"; then
    echo "CAP_SITE_KEY / CAP_SECRET_KEY: not set"
    want_keys=1
else
    echo "CAP_SITE_KEY / CAP_SECRET_KEY: set"
    want_keys=0
    ask_yes "  Replace them with a new site key?" n && want_keys=1
fi

if [ "$want_keys" -eq 1 ]; then
    echo "  1) Issue a new site key from this stack's Cap server (recommended)"
    echo "  2) Paste a site key and secret from the Cap dashboard"
    echo "  3) Skip for now"
    read -r -p "  Choice [1]: " choice
    case "${choice:-1}" in
        1) provision && changed=1 || true ;;
        2)
            read -r -p "  CAP_SITE_KEY: " site_key
            read -r -s -p "  CAP_SECRET_KEY (hidden): " secret_key
            echo
            if [ -n "$site_key" ] && [ -n "$secret_key" ]; then
                [[ $secret_key == sk-* ]] || echo "  (note: Cap secrets normally start with 'sk-')"
                set_all CAP_SITE_KEY "$site_key"
                set_all CAP_SECRET_KEY "$secret_key"
                changed=1
                echo "  -> saved"
            else
                echo "  -> both are needed; nothing written"
            fi
            unset secret_key
            ;;
        *) echo "  -> skipped" ;;
    esac
fi

# ── Apply ────────────────────────────────────────────────────────────────────

[ "$mode" = prod ] && chmod 600 "$primary"

echo
if [ "$changed" -eq 0 ]; then
    echo "Nothing changed."
    exit 0
fi

echo "Saved to $(printf '%s ' "${files[@]}")"
if [ "$mode" = dev ]; then
    if ask_yes "Recreate app, queue and cap now so they use the new values?" y; then
        compose up -d app queue cap </dev/null
    else
        echo "Apply later with: make dev-up"
    fi
else
    still="$(for k in APP_KEY CAP_ADMIN_KEY CAP_SITE_KEY CAP_SECRET_KEY; do
        is_unset "$(get_kv "$primary" "$k")" && printf '%s ' "$k"; done)"
    if [ -n "$still" ]; then
        echo "Still not set: $still— the production stack will refuse to start until they are."
    fi
    echo "Apply with: make prod-up"
fi
