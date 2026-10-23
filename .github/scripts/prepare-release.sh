#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 8 ]]; then
    echo "Usage: prepare-release.sh <root-name> <releases-dir> <shared-dir> <current-link> <commit-sha> <release-id> <base-url> <expected-previous-release>" >&2
    exit 2
fi
root_name="$1"
releases_name="$2"
shared_name="$3"
current_name="$4"
commit="$5"
release="$6"
base_url="$7"
expected_previous="$8"
for path_part in "$root_name" "$releases_name" "$shared_name" "$current_name"; do
    [[ "$path_part" =~ ^[A-Za-z0-9._-]+$ ]] || { echo "Invalid deployment path configuration." >&2; exit 2; }
done
[[ "$commit" =~ ^[a-f0-9]{40}$ ]] || { echo "Invalid deployment commit SHA." >&2; exit 2; }
[[ "$release" =~ ^[0-9]{14}-[0-9]+-[0-9]+$ ]] || { echo "Invalid release identifier." >&2; exit 2; }
[[ "$base_url" =~ ^https://[A-Za-z0-9.-]+$ ]] || { echo "Invalid canonical URL." >&2; exit 2; }

base_dir="$HOME/$root_name"
release_root="$base_dir/$releases_name"
shared_dir="$base_dir/$shared_name"
current_link="$base_dir/$current_name"
candidate="$release_root/$release"
current_target="$(readlink -f -- "$current_link" || true)"
if [[ "$current_target" != "$expected_previous" || "$expected_previous" != "$release_root/"* || ! -d "$candidate" || -L "$candidate" ]]; then
    echo "Candidate or active release path failed validation; no production switch was made." >&2
    exit 1
fi
public_link="$HOME/public_html/public"
rewrite_file="$HOME/public_html/.htaccess"
expected_public_link="../$root_name/$current_name/public"
if [[ ! -L "$public_link" || "$(readlink -- "$public_link" || true)" != "$expected_public_link" ]]; then
    echo "Litehost public_html/public changed after preflight; no migration or activation was performed." >&2
    exit 1
fi
rewrite_marker="/public/\$1"
if [[ "$(readlink -f -- "$public_link" || true)" != "$current_target/public" || ! -f "$rewrite_file" ]] || ! grep -Fq "$rewrite_marker" "$rewrite_file"; then
    echo "Litehost public routing no longer resolves to the active release public directory; deployment stopped before migrations." >&2
    exit 1
fi
if [[ ! -f "$shared_dir/.env" || ! -d "$shared_dir/storage" ]]; then
    echo "Required shared production .env or storage directory is missing." >&2
    exit 1
fi
for item in artisan vendor/autoload.php public/build/manifest.json public/.htaccess; do
    if [[ ! -f "$candidate/$item" ]]; then
        echo "Candidate release is incomplete; required file missing: $item" >&2
        exit 1
    fi
done
if [[ -e "$candidate/.env" || -L "$candidate/.env" || -e "$candidate/storage" || -L "$candidate/storage" ]]; then
    echo "Candidate already contains .env or storage; refusing to replace candidate data." >&2
    exit 1
fi
php "$candidate/scripts/validate-release.php" "$commit" "$release"

ln -s -- "$shared_dir/.env" "$candidate/.env"
ln -s -- "$shared_dir/storage" "$candidate/storage"
cd "$candidate"

operation_log="$(mktemp)"
asset_list_file=""
cleanup_temporary_files() {
    rm -f -- "$operation_log" "${asset_list_file:-}"
}
trap cleanup_temporary_files EXIT
run_phase() {
    local label="$1"
    shift
    echo "[prepare] START $label"
    if "$@" > "$operation_log" 2>&1; then
        echo "[prepare] PASS $label"
    else
        local status=$?
        echo "[prepare] FAIL $label (exit $status). Command output was suppressed because it may contain shared environment details." >&2
        return "$status"
    fi
}

run_phase "database migrations" php artisan migrate --force
run_phase "Laravel configuration cache" php artisan config:cache
run_phase "IndexNow verification key file" php artisan indexnow:publish-key-file
run_phase "shared storage link" php artisan storage:link
run_phase "Laravel route cache" php artisan route:cache
run_phase "Laravel view cache" php artisan view:cache

# Preserve both releases' content-hashed assets while Litehost's PHP worker
# may still return Blade compiled before the current symlink is switched.
copy_assets() {
    local source_dir="$1"
    local destination_dir="$2"
    local asset
    local relative
    local destination
    [[ -d "$source_dir" ]] || return 0
    mkdir -p -- "$destination_dir"
    asset_list_file="$(mktemp)"
    find "$source_dir" -type f -print0 > "$asset_list_file"
    while IFS= read -r -d '' asset; do
        relative="${asset#"$source_dir"/}"
        destination="$destination_dir/$relative"
        mkdir -p -- "$(dirname -- "$destination")"
        if [[ -e "$destination" ]]; then
            if ! cmp -s -- "$asset" "$destination"; then
                echo "Content-hashed asset collision with different bytes: $relative" >&2
                return 1
            fi
        else
            cp -p -- "$asset" "$destination"
        fi
    done < "$asset_list_file"
    rm -f -- "$asset_list_file"
    asset_list_file=""
}

echo "[prepare] START previous-release asset compatibility"
copy_assets "$current_target/public/build/assets" "$candidate/public/build/assets"
copy_assets "$candidate/public/build/assets" "$current_target/public/build/assets"
echo "[prepare] PASS previous and candidate hashed assets are available for rollback and stale-worker recovery."
echo "Candidate $release passed validation, migration, and cache preparation; the active release remains unchanged."
