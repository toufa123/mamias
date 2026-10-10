#!/usr/bin/env bash
#  Interactive SMTP setup for production (.env.production).
#
#    bash env-mail.sh
#
#  Reached from the menu as "make prod-mail", and at the end of "make prod-env".
#  Safe to re-run: Enter keeps every current value. The password is read
#  hidden, typed twice, and never printed. Values are written single-quoted, so
#  characters such as ! # $ and spaces reach Laravel unchanged.
#
#  When the production app container is running, it can recreate the PHP
#  containers with the new settings and send a test message.
set -euo pipefail
cd "$(dirname "$0")"

file=.env.production
compose_cmd=(docker compose --env-file .env.production -f docker-compose.prod.yml)

[ -t 0 ] || { echo "env-mail: needs an interactive terminal." >&2; exit 1; }
[ -f "$file" ] || { echo "env-mail: $file not found. Run 'make prod-env' first." >&2; exit 1; }

# Same reason as env-keys.sh: the Makefile exports the dev .env, and compose
# would let those shell values override .env.production.
if [ -f .env ]; then
    while IFS= read -r name; do unset "$name" 2>/dev/null || true; done \
        < <(grep -oE '^[A-Za-z_][A-Za-z0-9_]*=' .env | tr -d '=')
fi

# ── .env helpers ─────────────────────────────────────────────────────────────

get_kv() { # key -> value without surrounding quotes ("" when missing)
    local v
    v="$(grep -E "^$1=" "$file" 2>/dev/null | tail -1 | cut -d= -f2- || true)"
    v="${v#\'}"; v="${v%\'}"; v="${v#\"}"; v="${v%\"}"
    printf '%s' "$v"
}

# Replace KEY=... in place, or append it. The value travels through ENVIRON so
# awk never interprets backslashes, "&" or "|" in it.
set_kv() { # key value
    local tmp
    tmp="$(mktemp)"
    K="$1" V="$2" awk '
        BEGIN { k = ENVIRON["K"]; v = ENVIRON["V"]; done = 0 }
        index($0, k "=") == 1 { print k "=" v; done = 1; next }
        { print }
        END { if (!done) print k "=" v }
    ' "$file" > "$tmp"
    cat "$tmp" > "$file" # rewrite in place: keeps the file's owner
    rm -f "$tmp"
}

quoted() { printf "'%s'" "$1"; } # single quotes: literal for compose and dotenv

is_unset() { [ -z "$1" ] || [[ $1 == *CHANGE_ME* ]] || [[ $1 == *example.com* ]]; }

ask() { # prompt default -> answer (default on Enter)
    local answer
    if [ -n "$2" ]; then read -r -p "$1 [$2]: " answer; else read -r -p "$1: " answer; fi
    printf '%s' "${answer:-$2}"
}

ask_yes() { # prompt default(y|n)
    local answer hint="[y/N]"
    [ "$2" = y ] && hint="[Y/n]"
    read -r -p "$1 $hint " answer
    answer="${answer:-$2}"
    [[ $answer =~ ^[Yy] ]]
}

# ── 1. Settings ──────────────────────────────────────────────────────────────

host="$(get_kv MAIL_HOST)"
user="$(get_kv MAIL_USERNAME)"
configured=0
if ! is_unset "$host" && ! is_unset "$user"; then configured=1; fi

echo
echo "SMTP (outgoing mail) for $file"
if [ "$configured" -eq 1 ]; then
    echo "  current: $user via $host:$(get_kv MAIL_PORT)"
    change=0
    if ask_yes "  Change these settings?" n; then change=1; fi
else
    echo "  current: not configured"
    change=1
fi

if [ "$change" -eq 1 ]; then
    echo "  1) Microsoft 365 / Outlook   (smtp.office365.com:587)"
    echo "  2) Google Workspace / Gmail  (smtp.gmail.com:587, needs an app password)"
    echo "  3) Mailgun                   (smtp.mailgun.org / smtp.eu.mailgun.org:587)"
    echo "  4) Other SMTP server"
    echo "  5) Skip for now"
    read -r -p "  Choice [1]: " choice
    case "${choice:-1}" in
        1) host=smtp.office365.com; port=587 ;;
        2) host=smtp.gmail.com; port=587 ;;
        3)
            # The region is the one the sending domain was created in (Mailgun →
            # Sending → Domains); an EU domain does not authenticate on the US host.
            port=587
            if ask_yes "  Is the Mailgun domain in the EU region?" y; then
                host=smtp.eu.mailgun.org
            else
                host=smtp.mailgun.org
            fi
            ;;
        4)
            default_host="$host"
            if is_unset "$default_host"; then default_host=""; fi
            host="$(ask "  SMTP host" "$default_host")"
            port="$(ask "  SMTP port (587 = STARTTLS, 465 = TLS)" 587)"
            ;;
        *) echo "  -> skipped"; change=0 ;;
    esac
fi

if [ "$change" -eq 1 ]; then
    [ -n "$host" ] || { echo "  -> no host given; nothing written"; exit 1; }
    [[ $port =~ ^[0-9]+$ ]] || { echo "  -> port must be a number; nothing written"; exit 1; }

    default_user="$user"
    if is_unset "$default_user"; then default_user=""; fi
    if [[ $host == *mailgun.org ]]; then
        echo "  Mailgun: use the domain's SMTP credentials (Sending → Domain settings →"
        echo "  SMTP credentials), e.g. postmaster@mg.your-domain — not the API key."
    fi
    user="$(ask "  Username (usually the full mailbox address)" "$default_user")"
    [ -n "$user" ] || { echo "  -> no username given; nothing written"; exit 1; }

    old_pass="$(get_kv MAIL_PASSWORD)"
    while true; do
        if is_unset "$old_pass"; then
            read -r -s -p "  Password (hidden): " pass; echo
        else
            read -r -s -p "  Password (hidden, Enter = keep the current one): " pass; echo
            if [ -z "$pass" ]; then pass="$old_pass"; break; fi
        fi
        if [ -z "$pass" ]; then echo "  -> a password is required"; continue; fi
        if [[ $pass == *"'"* ]]; then echo "  -> passwords containing ' cannot be stored safely; choose another"; continue; fi
        read -r -s -p "  Type it again: " again; echo
        [ "$pass" = "$again" ] && break
        echo "  -> the two entries differ, try again"
    done
    unset again old_pass

    default_from="$(get_kv MAIL_FROM_ADDRESS)"
    if { is_unset "$default_from" || [ "$configured" -eq 0 ]; } && [[ $user == *@* ]]; then
        default_from="$user"
    fi
    from="$(ask "  Sender address (Microsoft 365: the mailbox itself; Mailgun: an address on the verified domain)" "$default_from")"
    name="$(get_kv MAIL_FROM_NAME)"
    if [ -z "$name" ]; then name=MAMIAS; fi
    name="$(ask "  Sender name" "$name")"
    if [[ $from == *"'"* || $name == *"'"* ]]; then
        echo "  -> ' is not allowed in the sender; nothing written"
        exit 1
    fi

    # 465 is TLS from the first byte (smtps); anything else upgrades with STARTTLS.
    scheme=smtp
    if [ "$port" = 465 ]; then scheme=smtps; fi

    set_kv MAIL_MAILER smtp
    set_kv MAIL_HOST "$host"
    set_kv MAIL_PORT "$port"
    set_kv MAIL_SCHEME "$scheme"
    set_kv MAIL_USERNAME "$(quoted "$user")"
    set_kv MAIL_PASSWORD "$(quoted "$pass")"
    set_kv MAIL_FROM_ADDRESS "$(quoted "$from")"
    set_kv MAIL_FROM_NAME "$(quoted "$name")"
    unset pass
    chmod 600 "$file"
    echo "  -> saved to $file"
    configured=1
fi

[ "$configured" -eq 1 ] || exit 0

# ── 2. Apply and test ────────────────────────────────────────────────────────

if [ -z "$("${compose_cmd[@]}" ps --status running -q app 2>/dev/null)" ]; then
    echo
    echo "The production app is not running: start it with 'make prod-up', then run"
    echo "'make prod-mail' again to send a test message."
    exit 0
fi

echo
ask_yes "Apply now (restarts app, queue, scheduler) and send a test message?" y || {
    echo "Apply later with: make prod-up"
    exit 0
}

to="$(ask "  Send the test to" "$(get_kv MAIL_FROM_ADDRESS)")"
echo "  Recreating app, queue and scheduler (up to 3 minutes)..."
"${compose_cmd[@]}" up -d --force-recreate --wait --wait-timeout 180 app queue scheduler </dev/null >/dev/null \
    || { echo "  -> the containers did not become healthy; check: docker logs mamias_app" >&2; exit 1; }

echo "  Sending..."
# </dev/null: compose exec would otherwise swallow input typed ahead.
result="$("${compose_cmd[@]}" exec -T -e MAIL_TEST_TO="$to" app php artisan tinker --execute '
try {
    Illuminate\Support\Facades\Mail::raw(
        "Test message from MAMIAS (" . config("app.url") . "). SMTP works.",
        fn ($m) => $m->to(getenv("MAIL_TEST_TO"))->subject("MAMIAS SMTP test")
    );
    echo "SENT\n";
} catch (Throwable $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
}' </dev/null 2>&1 || true)"

if [[ $result == *SENT* ]]; then
    echo "  -> sent to $to. Check that inbox (and its spam folder)."
else
    echo "  -> sending failed:"
    printf '%s\n' "$result" | grep -E 'FAILED|Exception|Error' | head -5 | sed 's/^/     /'
    if [[ $result == *5.7.139* || $result == *SmtpClientAuthentication* || $result == *535* ]]; then
        echo "     Microsoft 365 refused the login: SMTP AUTH must be enabled for this mailbox"
        echo "     by a tenant admin, or the tenant no longer accepts password logins for SMTP."
    fi
    if [[ $(get_kv MAIL_HOST) == *mailgun.org && $result == *535* ]]; then
        echo "     Mailgun refused the login: check the SMTP credential (not the API key) and"
        echo "     that the region matches the domain (EU domains need smtp.eu.mailgun.org)."
    fi
    echo "     Fix the settings with 'make prod-mail' and try again."
    exit 1
fi
