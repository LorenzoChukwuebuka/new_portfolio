#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd -- "$script_dir/../.." && pwd)"
# shellcheck source=.github/scripts/deployment-config.sh
source "$script_dir/deployment-config.sh"
cd "$repo_root"

if [[ ! "${GITHUB_SHA:-}" =~ ^[a-f0-9]{40}$ ]]; then
    echo "::error::GitHub did not provide a valid source commit SHA."
    exit 1
fi
if [[ ! "${GITHUB_RUN_ID:-}" =~ ^[0-9]+$ || ! "${GITHUB_RUN_ATTEMPT:-}" =~ ^[0-9]+$ ]]; then
    echo "::error::GitHub run identifiers are missing or invalid."
    exit 1
fi
if [[ ! -f public/build/manifest.json || ! -f composer.lock || ! -f vendor/autoload.php ]]; then
    echo "::error::Build output is incomplete; expected the Vite manifest, Composer lock, and vendor autoloader."
    exit 1
fi

release_id="$(date -u +%Y%m%d%H%M%S)-${GITHUB_RUN_ID}-${GITHUB_RUN_ATTEMPT}"
manifest_hash="$(sha256sum public/build/manifest.json | awk '{print $1}')"
lock_hash="$(sha256sum composer.lock | awk '{print $1}')"
DEPLOY_COMMIT="$GITHUB_SHA" DEPLOY_RELEASE="$release_id" DEPLOY_MANIFEST_HASH="$manifest_hash" DEPLOY_LOCK_HASH="$lock_hash" node --input-type=module <<'NODE'
import { writeFile } from 'node:fs/promises'

const metadata = {
  commit: process.env.DEPLOY_COMMIT,
  release: process.env.DEPLOY_RELEASE,
  manifest_sha256: process.env.DEPLOY_MANIFEST_HASH,
  composer_lock_sha256: process.env.DEPLOY_LOCK_HASH,
}
const marker = `${JSON.stringify(metadata)}\n`
await writeFile(`public/build/deploy-${metadata.commit}.json`, marker, { mode: 0o644 })
await writeFile('DEPLOYMENT.json', marker, { mode: 0o644 })
NODE

php scripts/validate-release.php "$GITHUB_SHA" "$release_id"

artifact_path="${RUNNER_TEMP:-${TMPDIR:-/tmp}}/portfolio-release.tar.gz"
rm -f -- "$artifact_path"
tar -czf "$artifact_path" \
    --exclude='./.git' \
    --exclude='./.github' \
    --exclude='./.aws' \
    --exclude='./.env*' \
    --exclude='./node_modules' \
    --exclude='./tests' \
    --exclude='./storage' \
    --exclude='./database/*.sqlite' \
    --exclude='./public/storage' \
    --exclude='./bootstrap/cache/config.php' \
    --exclude='./bootstrap/cache/routes-*.php' \
    --exclude='./bootstrap/cache/events.php' \
    .

if tar -tzf "$artifact_path" | grep -Eq '^\./\.env($|\.)|^\./\.aws(/|$)|^\./node_modules/|^\./\.git/|^\./tests/|^\./storage/|^\./database/.*\.sqlite$|^\./public/storage($|/)'; then
    echo "::error::Release artifact contains an excluded environment, dependency cache, VCS, or storage path."
    rm -f -- "$artifact_path"
    exit 1
fi
if [[ -n "${GITHUB_OUTPUT:-}" ]]; then
    printf 'release_id=%s\n' "$release_id" >> "$GITHUB_OUTPUT"
    printf 'artifact_path=%s\n' "$artifact_path" >> "$GITHUB_OUTPUT"
fi
echo "Build package prepared and validated for release $release_id."
