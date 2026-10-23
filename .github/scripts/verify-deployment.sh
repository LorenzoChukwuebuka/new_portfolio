#!/usr/bin/env bash
set -euo pipefail

script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
repo_root="$(cd -- "$script_dir/../.." && pwd)"
# shellcheck source=.github/scripts/deployment-config.sh
source "$script_dir/deployment-config.sh"
cd "$repo_root"

: "${DEPLOY_COMMIT:?Commit SHA is required}"
if [[ ! "$DEPLOY_COMMIT" =~ ^[a-f0-9]{40}$ ]]; then
    echo "::error::Post-deployment verification received an invalid commit SHA."
    exit 1
fi

export SMOKE_BASE_URL="$DEPLOY_BASE_URL"
export SMOKE_EXPECTED_COMMIT="$DEPLOY_COMMIT"
export SMOKE_REQUIRE_INDEXABLE=1
log_file="${RUNNER_TEMP:-${TMPDIR:-/tmp}}/portfolio-production-smoke.log"

for ((attempt = 1; attempt <= DEPLOY_SMOKE_RETRIES; attempt++)); do
    if npm run smoke:production > "$log_file" 2>&1; then
        cat "$log_file"
        echo "Post-deployment browser verification passed on attempt $attempt."
        exit 0
    fi
    if (( attempt < DEPLOY_SMOKE_RETRIES )); then
        echo "Production still serves stale runtime output (attempt $attempt/$DEPLOY_SMOKE_RETRIES). Restart the Litehost website container; the browser check will retry in ${DEPLOY_SMOKE_RETRY_DELAY}s."
        sleep "$DEPLOY_SMOKE_RETRY_DELAY"
    fi
done

cat "$log_file"
echo "::error::Production browser verification failed after retries; the guarded rollback step will restore the previous release."
exit 1
