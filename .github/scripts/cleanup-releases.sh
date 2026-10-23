#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 6 ]]; then
    echo "Usage: cleanup-releases.sh <root-name> <releases-dir> <current-link> <expected-active-release> <previous-release> <retention-count>" >&2
    exit 2
fi
root_name="$1"
releases_name="$2"
current_name="$3"
expected_active="$4"
previous_release="$5"
retention="$6"
for path_part in "$root_name" "$releases_name" "$current_name"; do
    [[ "$path_part" =~ ^[A-Za-z0-9._-]+$ ]] || { echo "Invalid deployment path configuration." >&2; exit 2; }
done
[[ "$retention" =~ ^[1-9][0-9]*$ ]] || { echo "Invalid release retention count." >&2; exit 2; }

base_dir="$HOME/$root_name"
release_root="$base_dir/$releases_name"
current_link="$base_dir/$current_name"
active_now="$(readlink -f -- "$current_link" || true)"
if [[ "$active_now" != "$expected_active" || ! -d "$expected_active" ]]; then
    echo "Active release changed after health verification; cleanup was skipped." >&2
    exit 1
fi
if [[ "$previous_release" != "$release_root/"* || ! -d "$previous_release" ]]; then
    echo "The previous working release is unavailable; cleanup was skipped." >&2
    exit 1
fi

shopt -s nullglob
releases=("$release_root"/*)
kept=0
for ((index = ${#releases[@]} - 1; index >= 0; index--)); do
    candidate="${releases[index]}"
    [[ -d "$candidate" && ! -L "$candidate" ]] || continue
    [[ "${candidate##*/}" =~ ^[0-9]{14}-[0-9]+-[0-9]+$ ]] || continue
    if (( kept < retention )); then
        kept=$((kept + 1))
        continue
    fi
    if [[ "$candidate" == "$active_now" || "$candidate" == "$previous_release" ]]; then
        continue
    fi
    rm -rf -- "$candidate"
    printf 'Removed expired release: %s\n' "${candidate##*/}"
done
echo "Release cleanup passed; the active release, previous working release, and $retention most recent releases are retained."
