#!/bin/sh
# =============================================================================
#  Installed as /etc/entrypoint.d/6-ssl-sans.sh
#  Re-issue the self-signed TLS keypair with Subject Alternative Names.
#
#  Runs immediately after the base image's 5-generate-ssl.sh, which emits a
#  bare "/CN=localhost" certificate carrying no SAN extension — something every
#  modern browser rejects outright, whichever hostname you visit.
#
#  Caddy itself is already hostname-agnostic: the site addresses come from
#  CADDY_HTTP_SERVER_ADDRESS / CADDY_HTTPS_SERVER_ADDRESS, which compose sets to
#  a bare "http://" / "https://", so it answers on any Host. The certificate was
#  the only piece still bound to a single name.
#
#  Hostnames come from DEV_SSL_HOSTNAMES (comma-, semicolon- or space-separated).
#  Entries that look like IP literals become IP: SANs, everything else DNS:.
# =============================================================================
set -eu

script_name="ssl-sans"

if [ "${SSL_MODE:-off}" = "off" ]; then
    echo "ℹ️  NOTICE ($script_name): SSL_MODE is off — leaving certificates alone."
    exit 0
fi

CERT="${SSL_CERTIFICATE_FILE:-/etc/ssl/private/self-signed-web.crt}"
KEY="${SSL_PRIVATE_KEY_FILE:-/etc/ssl/private/self-signed-web.key}"
HC_CERT="${HEALTHCHECK_SSL_CERTIFICATE_FILE:-/etc/ssl/healthcheck/localhost.crt}"
HC_KEY="${HEALTHCHECK_SSL_PRIVATE_KEY_FILE:-/etc/ssl/healthcheck/localhost.key}"
MARKER="${CERT}.sans"

HOSTNAMES="${DEV_SSL_HOSTNAMES:-localhost,127.0.0.1,::1,mamias.local,*.mamias.local,host.docker.internal}"

# Normalise separators, drop blanks, de-duplicate while preserving order.
list=$(printf '%s' "$HOSTNAMES" | tr ',;' '  ' | tr -s ' ' '\n' | sed '/^$/d' | awk '!seen[$0]++')

sans=""
primary=""
for h in $list; do
    if [ -z "$primary" ]; then
        primary="$h"
    fi

    if printf '%s' "$h" | grep -qE '^[0-9]+(\.[0-9]+){3}$|:'; then
        sans="${sans}${sans:+,}IP:${h}"
    else
        sans="${sans}${sans:+,}DNS:${h}"
    fi
done

if [ -z "$sans" ]; then
    echo "⚠️  WARNING ($script_name): DEV_SSL_HOSTNAMES is empty — keeping the image's certificate."
    exit 0
fi

# Reissue only when the hostname set changed or the certificate is missing or
# about to expire; the keypair is otherwise stable across restarts so a browser
# exception you granted once keeps working.
if [ -f "$CERT" ] && [ -f "$KEY" ] && [ -f "$MARKER" ] &&
   [ "$(cat "$MARKER")" = "$sans" ] &&
   openssl x509 -in "$CERT" -noout -checkend 604800 >/dev/null 2>&1; then
    echo "ℹ️  NOTICE ($script_name): certificate already covers ${sans}"
else
    echo "🔐 NOTICE ($script_name): issuing self-signed certificate for ${sans}"
    openssl req -x509 \
        -subj "/CN=${primary}" \
        -addext "subjectAltName=${sans}" \
        -addext "basicConstraints=critical,CA:FALSE" \
        -addext "keyUsage=critical,digitalSignature,keyEncipherment" \
        -addext "extendedKeyUsage=serverAuth" \
        -nodes -newkey rsa:2048 \
        -keyout "$KEY" -out "$CERT" \
        -days 825 >/dev/null 2>&1
    printf '%s' "$sans" >"$MARKER"
fi

# 5-generate-ssl.sh rewrites the healthcheck pair unconditionally on every boot,
# so always re-copy. Without this, requests with Host: localhost — which match
# the more specific "https://localhost:8443" site block — fall back to its
# SAN-less certificate while every other hostname gets the good one.
mkdir -p "$(dirname "$HC_CERT")" "$(dirname "$HC_KEY")"
cp "$CERT" "$HC_CERT"
cp "$KEY" "$HC_KEY"

for f in "$CERT" "$HC_CERT" "$MARKER"; do
    chown www-data:www-data "$f" 2>/dev/null || true
    chmod 644 "$f" 2>/dev/null || true
done
for f in "$KEY" "$HC_KEY"; do
    chown www-data:www-data "$f" 2>/dev/null || true
    chmod 640 "$f" 2>/dev/null || true
done

exit 0
