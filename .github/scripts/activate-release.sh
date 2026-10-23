#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 6 ]]; then
    echo "Usage: activate-release.sh <root-name> <releases-dir> <current-link> <commit-sha> <release-id> <expected-previous-release>" >&2
    exit 2
fi
root_name="$1"
releases_name="$2"
current_name="$3"
commit="$4"
release="$5"
expected_previous="$6"
for path_part in "$root_name" "$releases_name" "$current_name"; do
    [[ "$path_part" =~ ^[A-Za-z0-9._-]+$ ]] || { echo "Invalid deployment path configuration." >&2; exit 2; }
done
[[ "$commit" =~ ^[a-f0-9]{40}$ ]] || { echo "Invalid deployment commit SHA." >&2; exit 2; }
[[ "$release" =~ ^[0-9]{14}-[0-9]+-[0-9]+$ ]] || { echo "Invalid release identifier." >&2; exit 2; }
if [[ "$expected_previous" != "$HOME/$root_name/$releases_name/"* || ! -d "$expected_previous" ]]; then
    echo "The captured rollback target is missing or outside the release directory." >&2
    exit 1
fi

base_dir="$HOME/$root_name"
current_link="$base_dir/$current_name"
candidate="$base_dir/$releases_name/$release"
active_now="$(readlink -f -- "$current_link" || true)"
if [[ "$active_now" != "$expected_previous" ]]; then
    echo "The active release changed after rollback target capture; activation stopped." >&2
    exit 1
fi
if [[ ! -d "$candidate" || -L "$candidate" ]]; then
    echo "Validated candidate release is missing or not a directory." >&2
    exit 1
fi
# shellcheck disable=SC2016
marker="$(php -r '$data = json_decode((string) file_get_contents($argv[1]), true); echo ($data["commit"] ?? "")."|".($data["release"] ?? "");' "$candidate/DEPLOYMENT.json")"
if [[ "$marker" != "$commit|$release" ]]; then
    echo "Candidate deployment marker does not match the requested commit and release." >&2
    exit 1
fi

next_link="$base_dir/.current-$release"
if [[ -e "$next_link" || -L "$next_link" ]]; then
    echo "Temporary activation link already exists; refusing to overwrite it." >&2
    exit 1
fi
ln -s -- "$candidate" "$next_link"
if ! mv -Tf -- "$next_link" "$current_link"; then
    rm -f -- "$next_link"
    echo "Atomic activation failed; the previous active release remains in place." >&2
    exit 1
fi
printf 'Activated release: %s\n' "$(readlink -f -- "$current_link")"
