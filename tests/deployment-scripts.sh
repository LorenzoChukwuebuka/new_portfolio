#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
scripts="$repo_root/.github/scripts"
# shellcheck source=.github/scripts/deployment-config.sh
source "$scripts/deployment-config.sh"
test_root="$(mktemp -d "${TMPDIR:-/tmp}/portfolio-deploy-test.XXXXXX")"
trap 'rm -rf -- "$test_root"' EXIT

fail() {
    echo "FAIL deployment script test: $*" >&2
    exit 1
}

expect_failure() {
    local output_file="$1"
    shift
    if "$@" > "$output_file" 2>&1; then
        fail "expected command to fail: $*"
    fi
}

test_home="$test_root/home"
base="$test_home/$DEPLOY_REMOTE_ROOT_NAME"
releases="$base/$DEPLOY_RELEASES_DIRECTORY"
shared="$base/$DEPLOY_SHARED_DIRECTORY"
current_link="$base/$DEPLOY_CURRENT_LINK"
old_release="20260101000000-1-1"
candidate_release="20260102000000-2-1"
commit="aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"
base_url="$DEPLOY_BASE_URL"
mkdir -p "$releases" "$shared/storage/app/public" "$test_home/public_html"
cat > "$shared/.env" <<EOF
APP_ENV=production
APP_DEBUG=false
APP_URL=$base_url
SEO_CANONICAL_URL=$base_url
EOF
cat > "$test_home/public_html/.htaccess" <<'EOF'
RewriteEngine On
RewriteRule ^(.*)$ /public/$1 [L]
EOF
ln -s "../$DEPLOY_REMOTE_ROOT_NAME/$DEPLOY_CURRENT_LINK/public" "$test_home/public_html/public"

write_artisan() {
    cat > "$1/artisan" <<'PHP'
<?php
$command = $argv[1] ?? '';
$log = getenv('ARTISAN_LOG');
if ($log) {
    file_put_contents($log, $command.PHP_EOL, FILE_APPEND);
}
if ((getenv('ARTISAN_FAIL_COMMAND') ?: '') === $command) {
    fwrite(STDERR, 'mock artisan failure');
    exit(23);
}
PHP
}

write_release() {
    local release_dir="$1"
    local release_id="$2"
    mkdir -p "$release_dir/vendor" "$release_dir/scripts" \
        "$release_dir/public/build/assets"
    printf '<?php return true;\n' > "$release_dir/vendor/autoload.php"
    printf 'mock composer lock for %s\n' "$release_id" > "$release_dir/composer.lock"
    cat > "$release_dir/public/build/manifest.json" <<EOF
{"resources/js/site.ts":{"isEntry":true,"file":"assets/site-$release_id.js"},"resources/js/admin.ts":{"isEntry":true,"file":"assets/admin-$release_id.js"}}
EOF
    printf 'site bundle %s\n' "$release_id" > "$release_dir/public/build/assets/site-$release_id.js"
    printf 'admin bundle %s\n' "$release_id" > "$release_dir/public/build/assets/admin-$release_id.js"
    printf 'RewriteEngine On\n' > "$release_dir/public/.htaccess"
    cp "$repo_root/scripts/validate-release.php" "$release_dir/scripts/validate-release.php"
    write_artisan "$release_dir"
    local manifest_hash
    local lock_hash
    manifest_hash="$(sha256sum "$release_dir/public/build/manifest.json" | awk '{print $1}')"
    lock_hash="$(sha256sum "$release_dir/composer.lock" | awk '{print $1}')"
    # shellcheck disable=SC2016
    php -r '$data = ["commit" => $argv[1], "release" => $argv[2], "manifest_sha256" => $argv[3], "composer_lock_sha256" => $argv[4]]; $json = json_encode($data).PHP_EOL; file_put_contents($argv[5]."/DEPLOYMENT.json", $json); file_put_contents($argv[5]."/public/build/deploy-".$argv[1].".json", $json);' \
        "$commit" "$release_id" "$manifest_hash" "$lock_hash" "$release_dir"
}

old_dir="$releases/$old_release"
candidate_dir="$releases/$candidate_release"
write_release "$old_dir" "$old_release"
ln -s "$shared/.env" "$old_dir/.env"
ln -s "$shared/storage" "$old_dir/storage"
ln -s "$old_dir" "$current_link"

environment_output="$(HOME="$test_home" bash "$scripts/verify-environment.sh" \
    "$DEPLOY_REMOTE_ROOT_NAME" "$DEPLOY_RELEASES_DIRECTORY" "$DEPLOY_SHARED_DIRECTORY" \
    "$DEPLOY_CURRENT_LINK" "$base_url")"
[[ "$environment_output" == *"PREVIOUS_RELEASE=$old_dir"* ]] || fail "environment verification did not report the current release"
echo "PASS environment verification accepts the verified Litehost layout"

cp "$shared/.env" "$test_root/shared.env"
sed -i 's#^SEO_CANONICAL_URL=.*#SEO_CANONICAL_URL=https://wrong.invalid#' "$shared/.env"
expect_failure "$test_root/bad-environment.log" \
    env HOME="$test_home" bash "$scripts/verify-environment.sh" \
    "$DEPLOY_REMOTE_ROOT_NAME" "$DEPLOY_RELEASES_DIRECTORY" "$DEPLOY_SHARED_DIRECTORY" \
    "$DEPLOY_CURRENT_LINK" "$base_url"
if grep -Eq 'wrong\.invalid|APP_KEY=|DB_PASSWORD=' "$test_root/bad-environment.log"; then
    fail "environment validation logged an environment value"
fi
grep -q 'SEO_CANONICAL_URL' "$test_root/bad-environment.log" || fail "environment failure did not identify the invalid key"
mv "$test_root/shared.env" "$shared/.env"
echo "PASS environment validation rejects a bad canonical URL without printing .env values"

write_release "$candidate_dir" "$candidate_release"
failure_log="$test_root/migration-failure.log"
expect_failure "$failure_log" \
    env HOME="$test_home" ARTISAN_LOG="$test_root/artisan.log" ARTISAN_FAIL_COMMAND=migrate \
    bash "$scripts/prepare-release.sh" "$DEPLOY_REMOTE_ROOT_NAME" "$DEPLOY_RELEASES_DIRECTORY" \
    "$DEPLOY_SHARED_DIRECTORY" "$DEPLOY_CURRENT_LINK" \
    "$commit" "$candidate_release" "$base_url" "$old_dir"
[[ "$(readlink -f "$current_link")" == "$old_dir" ]] || fail "migration failure changed the active release"
grep -q 'FAIL database migrations' "$failure_log" || fail "migration failure did not identify the failed operation"
if grep -Eq 'APP_KEY=|DB_PASSWORD=|wrong\.invalid' "$failure_log"; then
    fail "candidate preparation logged shared environment values"
fi
echo "PASS failed migrations leave the previous release active and identify the operation"

rm -f -- "$candidate_dir/.env" "$candidate_dir/storage"
env HOME="$test_home" ARTISAN_LOG="$test_root/artisan.log" \
    bash "$scripts/prepare-release.sh" "$DEPLOY_REMOTE_ROOT_NAME" "$DEPLOY_RELEASES_DIRECTORY" \
    "$DEPLOY_SHARED_DIRECTORY" "$DEPLOY_CURRENT_LINK" \
    "$commit" "$candidate_release" "$base_url" "$old_dir" > "$test_root/prepare.log"
[[ -L "$candidate_dir/.env" && "$(readlink -f "$candidate_dir/.env")" == "$(readlink -f "$shared/.env")" ]] || fail "candidate did not link the shared environment"
[[ -L "$candidate_dir/storage" && "$(readlink -f "$candidate_dir/storage")" == "$(readlink -f "$shared/storage")" ]] || fail "candidate did not link shared storage"
[[ -f "$candidate_dir/public/build/assets/site-$old_release.js" && -f "$old_dir/public/build/assets/site-$candidate_release.js" ]] || fail "hashed assets were not preserved for both releases"
grep -q '^view:cache$' "$test_root/artisan.log" || fail "Laravel cache preparation did not finish"
echo "PASS candidate release validation, shared links, migrations, and cache preparation succeed"

env HOME="$test_home" bash "$scripts/activate-release.sh" \
    "$DEPLOY_REMOTE_ROOT_NAME" "$DEPLOY_RELEASES_DIRECTORY" "$DEPLOY_CURRENT_LINK" \
    "$commit" "$candidate_release" "$old_dir"
[[ "$(readlink -f "$current_link")" == "$candidate_dir" ]] || fail "activation did not switch the active release atomically"
echo "PASS activation points current at the validated candidate"

env HOME="$test_home" bash "$scripts/rollback-release.sh" \
    "$DEPLOY_REMOTE_ROOT_NAME" "$DEPLOY_RELEASES_DIRECTORY" "$DEPLOY_CURRENT_LINK" \
    "$candidate_release" "$old_dir" "$commit"
[[ "$(readlink -f "$current_link")" == "$old_dir" ]] || fail "rollback did not restore the previous release"
[[ -f "$base/deploy-failures/$candidate_release.txt" ]] || fail "rollback did not record the failed deployment"
echo "PASS guarded rollback restores the previous release and records the failure"

other_release="20260103000000-3-1"
other_dir="$releases/$other_release"
mkdir -p "$other_dir"
ln -sfn "$other_dir" "$current_link"
expect_failure "$test_root/guarded-rollback.log" \
    env HOME="$test_home" bash "$scripts/rollback-release.sh" \
    "$DEPLOY_REMOTE_ROOT_NAME" "$DEPLOY_RELEASES_DIRECTORY" "$DEPLOY_CURRENT_LINK" \
    "$candidate_release" "$old_dir" "$commit"
[[ "$(readlink -f "$current_link")" == "$other_dir" ]] || fail "guarded rollback replaced an unrelated active release"
echo "PASS rollback refuses to replace an unrelated active release"

for number in {1..10}; do
    release_id="202602$(printf '%02d' "$number")000000-$number-1"
    mkdir -p "$releases/$release_id"
done
active_cleanup="$releases/20260210000000-10-1"
previous_cleanup="$releases/20260201000000-1-1"
ln -sfn "$active_cleanup" "$current_link"
env HOME="$test_home" bash "$scripts/cleanup-releases.sh" \
    "$DEPLOY_REMOTE_ROOT_NAME" "$DEPLOY_RELEASES_DIRECTORY" "$DEPLOY_CURRENT_LINK" \
    "$active_cleanup" "$previous_cleanup" 2 \
    > "$test_root/cleanup.log"
[[ -d "$active_cleanup" && -d "$previous_cleanup" && -d "$releases/20260209000000-9-1" ]] || fail "cleanup removed the active, previous, or recent release"
[[ ! -e "$releases/20260208000000-8-1" ]] || fail "cleanup retained an expired release beyond the configured window"
echo "PASS cleanup retains the active, previous, and recent releases"

ssh_input_log="$(env DEPLOY_HOST=127.0.0.1 DEPLOY_USER=deploy DEPLOY_SSH_PRIVATE_KEY=dummy \
    DEPLOY_SSH_PUBLIC_KEY=dummy DEPLOY_KNOWN_HOSTS=dummy DEPLOY_SSH_PORT=65536 \
    bash "$scripts/verify-ssh.sh" 2>&1 || true)"
[[ "$ssh_input_log" == *"1 through 65535"* ]] || fail "SSH port input validation did not reject an invalid port"
if [[ "$ssh_input_log" == *"dummy"* ]]; then
    fail "SSH input validation echoed credential material"
fi
echo "PASS SSH input validation rejects invalid ports without logging key material"
