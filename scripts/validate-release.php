<?php

declare(strict_types=1);

function failValidation(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}

if (PHP_SAPI !== 'cli' || count($argv) !== 3) {
    failValidation('Usage: php scripts/validate-release.php <commit-sha> <release-id>');
}

[$script, $commit, $release] = $argv;
$releaseDir = dirname(__DIR__);
$buildDir = $releaseDir.'/public/build';
$manifestPath = $buildDir.'/manifest.json';
$markerPath = $buildDir.'/deploy-'.$commit.'.json';
$deploymentPath = $releaseDir.'/DEPLOYMENT.json';

if (!preg_match('/^[a-f0-9]{40}$/', $commit) || !preg_match('/^[0-9]{14}-[0-9]+-[0-9]+$/', $release)) {
    failValidation('Commit or release identifier has an invalid format.');
}

$manifest = json_decode((string) @file_get_contents($manifestPath), true);
$marker = json_decode((string) @file_get_contents($markerPath), true);
$deployment = json_decode((string) @file_get_contents($deploymentPath), true);
if (!is_array($manifest) || !is_array($marker) || !is_array($deployment)) {
    failValidation('Candidate manifest or commit marker is invalid.');
}

foreach (['resources/js/site.ts', 'resources/js/admin.ts'] as $entry) {
    if (empty($manifest[$entry]['isEntry']) || empty($manifest[$entry]['file'])) {
        failValidation("Candidate manifest is missing {$entry}.");
    }
}

foreach ($manifest as $key => $item) {
    if (!is_array($item)) {
        failValidation("Invalid candidate manifest entry {$key}.");
    }

    foreach (array_merge([$item['file'] ?? null], $item['css'] ?? [], $item['assets'] ?? []) as $relative) {
        if ($relative === null) {
            continue;
        }

        if (
            !is_string($relative)
            || str_starts_with($relative, '/')
            || str_contains($relative, '..')
            || !is_file($buildDir.'/'.$relative)
        ) {
            failValidation("Candidate manifest output missing or unsafe for {$key}.");
        }
    }

    foreach (array_merge($item['imports'] ?? [], $item['dynamicImports'] ?? []) as $dependency) {
        if (!isset($manifest[$dependency])) {
            failValidation("Candidate manifest dependency missing for {$key}.");
        }
    }
}

$lockPath = $releaseDir.'/composer.lock';
$manifestHash = is_file($manifestPath) ? hash_file('sha256', $manifestPath) : false;
$lockHash = is_file($lockPath) ? hash_file('sha256', $lockPath) : false;
if (
    ($marker['commit'] ?? null) !== $commit
    || ($marker['release'] ?? null) !== $release
    || ($deployment['commit'] ?? null) !== $commit
    || ($deployment['release'] ?? null) !== $release
    || !is_string($manifestHash)
    || !hash_equals((string) ($marker['manifest_sha256'] ?? ''), $manifestHash)
    || !is_string($lockHash)
    || !hash_equals((string) ($marker['composer_lock_sha256'] ?? ''), $lockHash)
) {
    failValidation('Candidate source, Vite manifest, and Composer lock do not match the release marker.');
}

echo "Candidate commit, manifest outputs, Composer lock, and release marker agree.\n";
