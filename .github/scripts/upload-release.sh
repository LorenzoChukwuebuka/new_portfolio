#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=.github/scripts/deployment-config.sh
source "$script_dir/deployment-config.sh"

: "${DEPLOY_SSH_CONFIG:?SSH configuration was not created by setup-ssh}"
: "${DEPLOY_RELEASE_ID:?Release identifier is required}"
: "${DEPLOY_COMMIT:?Commit SHA is required}"
: "${DEPLOY_ARTIFACT_PATH:?Build artifact path is required}"
if [[ ! "$DEPLOY_RELEASE_ID" =~ ^[0-9]{14}-[0-9]+-[0-9]+$ || ! "$DEPLOY_COMMIT" =~ ^[a-f0-9]{40}$ || ! -f "$DEPLOY_ARTIFACT_PATH" ]]; then
    echo "::error::Release upload inputs failed validation."
    exit 1
fi

remote_home="$(ssh -F "$DEPLOY_SSH_CONFIG" "$DEPLOY_SSH_ALIAS" 'printf "%s" "$HOME"')"
if [[ ! "$remote_home" =~ $DEPLOY_REMOTE_HOME_PATTERN ]]; then
    echo "::error::Litehost returned an unexpected site-home path; upload stopped."
    exit 1
fi

remote_release="$remote_home/$DEPLOY_REMOTE_ROOT_NAME/$DEPLOY_RELEASES_DIRECTORY/$DEPLOY_RELEASE_ID"
if ssh -F "$DEPLOY_SSH_CONFIG" "$DEPLOY_SSH_ALIAS" "test -e '$remote_release'" >/dev/null 2>&1; then
    echo "::error::Candidate release path already exists; refusing to overwrite it."
    exit 1
fi
ssh -F "$DEPLOY_SSH_CONFIG" "$DEPLOY_SSH_ALIAS" "mkdir -p -- '$remote_release'"

source_dir="${RUNNER_TEMP:-${TMPDIR:-/tmp}}/portfolio-release-$DEPLOY_RELEASE_ID"
rm -rf -- "$source_dir"
mkdir -p -- "$source_dir"
chmod 700 "$source_dir"
trap 'rm -rf -- "$source_dir"' EXIT
tar -xzf "$DEPLOY_ARTIFACT_PATH" -C "$source_dir"

if [[ ! -f "$source_dir/artisan" || ! -f "$source_dir/vendor/autoload.php" || ! -f "$source_dir/public/build/manifest.json" ]]; then
    echo "::error::The downloaded build package is missing artisan, Composer dependencies, or the Vite manifest."
    exit 1
fi

echo "Uploading validated candidate files to the isolated release directory."
rsync -az --delete \
    -e "ssh -F $DEPLOY_SSH_CONFIG" \
    --exclude='.git' \
    --exclude='.env' \
    --exclude='.aws' \
    --exclude='node_modules' \
    --exclude='tests' \
    --exclude='.github' \
    --exclude='storage' \
    --exclude='database/*.sqlite' \
    --exclude='public/storage' \
    "$source_dir/" \
    "$DEPLOY_SSH_ALIAS:$remote_release/"
echo "Upload completed for candidate release $DEPLOY_RELEASE_ID."
