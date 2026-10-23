#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 6 ]]; then
    echo "Usage: rollback-release.sh <root-name> <releases-dir> <current-link> <failed-release> <previous-release> <failed-commit>" >&2
    exit 2
fi
root_name="$1"
releases_name="$2"
current_name="$3"
failed_release="$4"
previous_release="$5"
failed_commit="$6"
for path_part in "$root_name" "$releases_name" "$current_name"; do
    [[ "$path_part" =~ ^[A-Za-z0-9._-]+$ ]] || { echo "Invalid deployment path configuration." >&2; exit 2; }
done
[[ "$failed_release" =~ ^[0-9]{14}-[0-9]+-[0-9]+$ ]] || { echo "Invalid failed release identifier." >&2; exit 2; }
[[ "$failed_commit" =~ ^[a-f0-9]{40}$ ]] || { echo "Invalid failed commit SHA." >&2; exit 2; }

base_dir="$HOME/$root_name"
release_root="$base_dir/$releases_name"
current_link="$base_dir/$current_name"
candidate="$release_root/$failed_release"
if [[ "$previous_release" != "$release_root/"* || ! -d "$previous_release" || ! -L "$current_link" ]]; then
    echo "Rollback target is unavailable or outside the release directory; no symlink change made." >&2
    exit 1
fi
current_target="$(readlink -f -- "$current_link" || true)"
if [[ "$current_target" == "$previous_release" ]]; then
    echo "Candidate activation did not complete; the previous release remains active."
    exit 0
fi
if [[ "$current_target" != "$candidate" ]]; then
    echo "The active release no longer targets this failed candidate; rollback stopped to protect the current site." >&2
    exit 1
fi

failure_dir="$base_dir/deploy-failures"
mkdir -p -- "$failure_dir"
chmod 750 "$failure_dir"
failure_record="$failure_dir/$failed_release.txt"
umask 027
{
    printf 'commit=%s\nrelease=%s\nreason=post-activation-health-check-failed\n' "$failed_commit" "$failed_release"
    printf 'recorded_at=%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)"
} > "$failure_record"
chmod 640 "$failure_record"

next_link="$base_dir/.rollback-$failed_release"
if [[ -e "$next_link" || -L "$next_link" ]]; then
    echo "Temporary rollback link already exists; refusing to overwrite it." >&2
    exit 1
fi
ln -s -- "$previous_release" "$next_link"
if ! mv -Tf -- "$next_link" "$current_link"; then
    rm -f -- "$next_link"
    echo "Atomic rollback failed; the active release was not intentionally changed." >&2
    exit 1
fi
printf 'Rolled back failed release %s to %s. The Litehost document-root symlink and shared storage were left unchanged. Database migrations were not reversed. Failure record: %s\n' \
    "$failed_release" "$previous_release" "$failure_record"
