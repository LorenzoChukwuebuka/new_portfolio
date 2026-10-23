#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 5 ]]; then
    echo "Usage: verify-environment.sh <root-name> <releases-dir> <shared-dir> <current-link> <base-url>" >&2
    exit 2
fi
root_name="$1"
releases_name="$2"
shared_name="$3"
current_name="$4"
base_url="$5"
for path_part in "$root_name" "$releases_name" "$shared_name" "$current_name"; do
    if [[ ! "$path_part" =~ ^[A-Za-z0-9._-]+$ ]]; then
        echo "Deployment path configuration contains an unsupported value." >&2
        exit 2
    fi
done
if [[ ! "$base_url" =~ ^https://[A-Za-z0-9.-]+$ ]]; then
    echo "The configured canonical URL is invalid." >&2
    exit 2
fi

home_dir="$HOME"
base_dir="$home_dir/$root_name"
release_root="$base_dir/$releases_name"
shared_dir="$base_dir/$shared_name"
current_link="$base_dir/$current_name"
env_file="$shared_dir/.env"
public_link="$home_dir/public_html/public"
rewrite_file="$home_dir/public_html/.htaccess"

for path in "$release_root" "$shared_dir/storage"; do
    if [[ ! -d "$path" ]]; then
        echo "Required Litehost directory is missing: $path" >&2
        exit 1
    fi
done
if [[ ! -f "$env_file" ]]; then
    echo "The production shared environment file is missing; its contents were not read or logged." >&2
    exit 1
fi
if [[ ! -L "$current_link" || ! -e "$current_link" ]]; then
    echo "The existing active-release symlink is missing or broken." >&2
    exit 1
fi
current_target="$(readlink -f -- "$current_link" || true)"
if [[ "$current_target" != "$release_root"/* || ! "${current_target##*/}" =~ ^[0-9]{14}-[0-9]+-[0-9]+$ ]]; then
    echo "The active release does not resolve to a valid release directory; deployment is stopped." >&2
    exit 1
fi

env_has_value() {
    local expected="$1"
    grep -Fqx -- "$expected" "$env_file"
}
for expected in \
    'APP_ENV=production' \
    'APP_DEBUG=false' \
    "APP_URL=$base_url" \
    "SEO_CANONICAL_URL=$base_url"; do
    if ! env_has_value "$expected"; then
        key="${expected%%=*}"
        echo "Production shared .env failed validation for $key; no values were logged or changed." >&2
        exit 1
    fi
done

if [[ ! -L "$current_target/.env" || "$(readlink -f -- "$current_target/.env" || true)" != "$(readlink -f -- "$env_file")" ]]; then
    echo "The active release does not use the configured shared .env." >&2
    exit 1
fi
if [[ ! -L "$current_target/storage" || "$(readlink -f -- "$current_target/storage" || true)" != "$(readlink -f -- "$shared_dir/storage")" ]]; then
    echo "The active release does not use the configured shared storage directory." >&2
    exit 1
fi

expected_public_link="../$root_name/$current_name/public"
if [[ ! -L "$public_link" ]]; then
    echo "Litehost public_html/public is not a symlink; deployment will not change the domain document root." >&2
    exit 1
fi
public_link_literal="$(readlink -- "$public_link" || true)"
if [[ "$public_link_literal" != "$expected_public_link" ]]; then
    echo "Litehost public_html/public no longer follows the verified current-release link; deployment stopped without changing it." >&2
    exit 1
fi
rewrite_marker="/public/\$1"
if [[ ! -f "$rewrite_file" ]] || ! grep -Fq "$rewrite_marker" "$rewrite_file"; then
    echo "Litehost public_html/.htaccess does not contain the verified rewrite through /public; deployment stopped without changing it." >&2
    exit 1
fi
public_target="$(readlink -f -- "$public_link" || true)"
if [[ "$public_target" != "$current_target/public" ]]; then
    echo "Litehost public_html/public does not resolve to the active release public directory; deployment stopped." >&2
    exit 1
fi

echo "PASS Litehost shared environment, storage, and active-release links are valid."
echo "PASS public_html/public and .htaccess still use the verified Litehost routing chain."
printf 'Active release: %s\n' "${current_target##*/}"
echo "Read-only Laravel public-route diagnostics:"
cd "$current_target"
php artisan route:list --path=sitemap.xml -v
php artisan route:list --path=robots.txt -v
printf 'PREVIOUS_RELEASE=%s\n' "$current_target"
