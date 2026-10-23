#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=.github/scripts/deployment-config.sh
source "$script_dir/deployment-config.sh"

required=(DEPLOY_HOST DEPLOY_USER DEPLOY_SSH_PRIVATE_KEY DEPLOY_SSH_PUBLIC_KEY DEPLOY_KNOWN_HOSTS)
for name in "${required[@]}"; do
    if [[ -z "${!name:-}" ]]; then
        echo "::error::Required deployment input $name is empty or missing."
        exit 1
    fi
done

if [[ ! "$DEPLOY_HOST" =~ ^[A-Za-z0-9][A-Za-z0-9.-]*$ ]]; then
    echo "::error::The deployment host must be a hostname or IPv4 address; its value was not logged."
    exit 1
fi
if [[ ! "$DEPLOY_USER" =~ ^[A-Za-z0-9._-]+$ ]]; then
    echo "::error::The deployment username has an unsupported format; its value was not logged."
    exit 1
fi
if [[ ! "$DEPLOY_SSH_PORT" =~ ^[0-9]{1,5}$ ]] || (( DEPLOY_SSH_PORT < 1 || DEPLOY_SSH_PORT > 65535 )); then
    echo "::error::CPANEL_SSH_PORT must be an integer from 1 through 65535."
    exit 1
fi

ssh_root="${RUNNER_TEMP:-${TMPDIR:-/tmp}}/portfolio-deploy-ssh"
install -d -m 700 "$ssh_root"
umask 077
private_key="$ssh_root/id_deploy"
provided_key="$ssh_root/provided.pub"
derived_key="$ssh_root/derived.pub"
known_hosts="$ssh_root/known_hosts"
ssh_config="$ssh_root/config"
printf '%s\n' "$DEPLOY_SSH_PRIVATE_KEY" > "$private_key"
printf '%s\n' "$DEPLOY_SSH_PUBLIC_KEY" > "$provided_key"
printf '%s\n' "$DEPLOY_KNOWN_HOSTS" > "$known_hosts"
chmod 600 "$private_key" "$provided_key" "$known_hosts"

if ! ssh-keygen -y -P '' -f "$private_key" > "$derived_key" 2>/dev/null; then
    echo "::error::The configured private key is unreadable or requires a passphrase. Key data was not logged."
    exit 1
fi
private_fingerprint="$(ssh-keygen -lf "$derived_key" 2>/dev/null | awk 'NR == 1 {print $2}')"
public_fingerprint="$(ssh-keygen -lf "$provided_key" 2>/dev/null | awk 'NR == 1 {print $2}')"
if [[ -z "$private_fingerprint" || -z "$public_fingerprint" || "$private_fingerprint" != "$public_fingerprint" ]]; then
    echo "::error::The configured public and private keys do not match. Fingerprints and key data were not logged."
    exit 1
fi

host_key_lookup="$DEPLOY_HOST"
if [[ "$DEPLOY_SSH_PORT" != "$DEPLOY_SSH_DEFAULT_PORT" ]]; then
    host_key_lookup="[$DEPLOY_HOST]:$DEPLOY_SSH_PORT"
fi
if ! ssh-keygen -F "$host_key_lookup" -f "$known_hosts" >/dev/null 2>&1; then
    echo "::error::The pinned known-hosts file has no entry for the configured endpoint. Verify the host fingerprint with Litehost before changing it."
    exit 1
fi

DEPLOY_HOST="$DEPLOY_HOST" DEPLOY_SSH_PORT="$DEPLOY_SSH_PORT" python3 - <<'PY'
import os
import socket
import sys

host = os.environ["DEPLOY_HOST"]
port = int(os.environ["DEPLOY_SSH_PORT"])
try:
    addresses = socket.getaddrinfo(host, port, type=socket.SOCK_STREAM)
except socket.gaierror:
    print("::error::DNS resolution failed for the configured Litehost SSH endpoint.")
    sys.exit(1)

last_error = None
for family, socktype, proto, _, address in addresses:
    try:
        with socket.socket(family, socktype, proto) as connection:
            connection.settimeout(8)
            connection.connect(address)
        print(f"SSH endpoint TCP preflight passed on port {port}.")
        sys.exit(0)
    except OSError as error:
        last_error = error

reason = type(last_error).__name__ if last_error else "UnknownNetworkError"
print(f"::error::Could not connect to the configured SSH endpoint ({reason}). Check the Litehost address, SSH service, and network allowlist.")
sys.exit(1)
PY

cat > "$ssh_config" <<EOF
Host $DEPLOY_SSH_ALIAS
  HostName $DEPLOY_HOST
  User $DEPLOY_USER
  Port $DEPLOY_SSH_PORT
  IdentityFile $private_key
  UserKnownHostsFile $known_hosts
  GlobalKnownHostsFile /dev/null
  StrictHostKeyChecking yes
  IdentitiesOnly yes
  PreferredAuthentications publickey
  BatchMode yes
  ConnectTimeout $DEPLOY_SSH_CONNECT_TIMEOUT
EOF
chmod 600 "$ssh_config"

ssh_error="$ssh_root/preflight.stderr"
if ! ssh -F "$ssh_config" "$DEPLOY_SSH_ALIAS" true >/dev/null 2>"$ssh_error"; then
    if grep -Eqi 'REMOTE HOST IDENTIFICATION HAS CHANGED|Host key verification failed|No .* host key is known' "$ssh_error"; then
        echo "::error::Strict SSH host-key verification failed for the configured Litehost endpoint."
    elif grep -Eqi 'Permission denied|No more authentication methods' "$ssh_error"; then
        echo "::error::The pinned key pair is valid, but Litehost rejected public-key authentication. Check the hosting username and authorized key."
    elif grep -Eqi 'Could not resolve hostname|Connection timed out|Connection refused|No route to host|Operation timed out' "$ssh_error"; then
        echo "::error::SSH failed after the TCP preflight. Recheck the Litehost endpoint and network allowlist."
    else
        echo "::error::SSH preflight failed for an unclassified reason. Raw SSH diagnostics were suppressed."
    fi
    rm -f -- "$ssh_error"
    exit 1
fi
rm -f -- "$ssh_error"
echo "SSH host-key verification and public-key authentication passed."
if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
    printf 'ssh_config=%s\n' "$ssh_config" >> "$GITHUB_OUTPUT"
fi
