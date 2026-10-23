# Deployment architecture

## Execution flow

Production deployment starts when a commit reaches `main`. The workflow builds the frontend and production Composer dependencies, runs the Laravel test suite, validates the Vite manifest, and packages a release artifact. The deploy job downloads that artifact, verifies the pinned Litehost SSH connection and current server layout, uploads an isolated candidate, prepares it, and atomically switches `production/current` only after preparation succeeds.

After activation, Chromium checks the live page, CSS and JavaScript assets, Vue islands, mobile navigation, public content pages, admin login, SEO endpoints, and Laravel health endpoint. The smoke check retries for up to eight attempts with ten seconds between attempts. If Litehost is serving stale PHP output, the log asks the operator to restart the website container in the Litehost panel while the check retries. A failed activation or browser check invokes the guarded rollback script. Old releases are pruned only after successful verification.

`workflow_dispatch` for IndexNow is defined in `indexnow.yml`. It shares the pinned SSH setup action but runs separately from the deploy flow.

## Responsibilities

| File | Responsibility |
| --- | --- |
| `.github/workflows/deploy.yml` | Defines the `main` trigger, permissions, concurrency, build job, artifact handoff, and deployment action. |
| `.github/workflows/ci.yml` | Validates pull requests with frontend build and browser checks, Laravel tests, deployment-script scenarios, ShellCheck, and actionlint. |
| `.github/workflows/indexnow.yml` | Runs an explicitly selected production IndexNow key check or guarded submission. |
| `.github/actions/setup-ssh/action.yml` | Reusable strict SSH setup for deployment and IndexNow operations. |
| `.github/actions/deploy-release/action.yml` | Orchestrates environment verification, upload, preparation, activation, browser verification, rollback, and retention cleanup. |
| `.github/scripts/deployment-config.sh` | Single source for canonical URL, remote directory names, SSH defaults, smoke retry policy, and release retention. Contains no credentials. |
| `.github/scripts/verify-ssh.sh` | Checks required inputs, key-pair match, pinned host key, DNS/TCP connectivity, strict SSH authentication, and writes the runner's temporary SSH config. |
| `.github/scripts/verify-environment.sh` | Remote read-only checks for shared environment settings, current/shared links, Litehost document-root symlink, rewrite rules, and Laravel public routes. |
| `.github/scripts/build-release.sh` | Writes commit and manifest markers, validates the package, and creates the artifact without local environment files or shared storage. |
| `.github/scripts/upload-release.sh` | Extracts the artifact into a temporary runner directory and synchronizes it into a new remote release path. |
| `.github/scripts/prepare-release.sh` | Remote candidate validation, shared `.env` and storage links, migrations, Laravel caches, IndexNow key-file generation, and additive content-hashed asset compatibility. |
| `.github/scripts/activate-release.sh` | Rechecks the captured rollback target and atomically switches `production/current`. |
| `.github/scripts/verify-deployment.sh` | Runs the production Chromium smoke check with release-marker, asset-manifest, Vue, page-rendering, and endpoint assertions. |
| `.github/scripts/rollback-release.sh` | Atomically restores the captured release only when `current` still points to the failed candidate; records the failure. |
| `.github/scripts/cleanup-releases.sh` | Removes expired release directories after successful health checks while retaining the active, previous, and recent releases. |
| `.github/scripts/indexnow-production.sh` | Executes only the selected IndexNow command against the active release. |
| `tests/deployment-scripts.sh` | Uses a temporary Litehost-like directory tree to exercise environment validation, preparation failures, activation, rollback guards, cleanup, and SSH input checks. |

## GitHub configuration

Configure these GitHub Actions secrets for the repository:

| Secret | Value |
| --- | --- |
| `SERVER_IP` | Litehost SSH hostname or IPv4 address from the hosting panel. |
| `CPANEL_USER` | Litehost SSH account username. |
| `CPANEL_SSH_SECRET` | SSH private key. |
| `CPANEL_PUBLIC_KEY` | The public key matching `CPANEL_SSH_SECRET`. |
| `CPANEL_KNOWN_HOSTS` | Independently verified host-key entry for the SSH endpoint. |

`CPANEL_SSH_PORT` is an optional repository variable. The shared configuration defaults to port `22`. Never generate a deployment trust pin from an unverified `ssh-keyscan` result. The actions write key material only to a mode-restricted runner temporary directory and never log keys or raw SSH errors.

The canonical URL and remote directory names are centralized in `.github/scripts/deployment-config.sh`. Production `production/shared/.env` must contain `APP_ENV=production`, `APP_DEBUG=false`, and `APP_URL` and `SEO_CANONICAL_URL` set to that canonical URL. Deployment only checks these values; it does not print or rewrite the shared environment file.

## Litehost directory layout

The existing hosting layout stays in place:

```text
$HOME/
├── production/
│   ├── current -> releases/<active-release>
│   ├── releases/
│   │   ├── <timestamp>-<run-id>-<attempt>/
│   │   └── ...
│   └── shared/
│       ├── .env
│       └── storage/
├── public_html/
│   ├── .htaccess
│   └── public -> ../production/current/public
└── ...
```

The deployment verifies that `public_html/public` remains the literal relative symlink to `production/current/public`, that it resolves to the active release, and that the existing `.htaccess` rewrite still goes through `/public`. It does not edit the document root, rewrite rules, or public symlink.

Each release contains the Laravel application, production `vendor/` dependencies, and its own Vite manifest/assets. Its `.env` and `storage` entries link to the shared directories. The upload targets a unique release directory; `rsync --delete` is scoped to that new candidate.

## Activation and rollback

The candidate must pass marker and manifest validation before Laravel commands run. Migrations and cache preparation run in the candidate while the previous release remains active. Content-hashed assets are copied additively between the current and candidate release, with byte checks on hash collisions, so a long-lived Litehost PHP worker can still find either release's asset files.

After candidate preparation succeeds, activation creates a temporary symlink and renames it over `production/current`. The existing `public_html/public` symlink continues to follow `current`. The previous target is captured before upload and is retained for rollback.

If atomic activation or post-deploy Chromium verification fails, `rollback-release.sh` checks that `current` still points to the candidate from this run, then atomically restores the captured previous target. It records the failed commit and release under `production/deploy-failures/`. It does not switch the public document root, alter shared storage, or reverse database migrations. Production schema changes must therefore remain compatible with the active release until deployment verification finishes.

For a manual rollback, first inspect the failed run and confirm both release paths. Use the same pinned SSH configuration, then send the version-controlled rollback script to the hosting account with the root, directory names, failed release, previous release, and failed commit as arguments. The script refuses to change `current` if another release became active or the previous directory is missing. Let the workflow perform this step automatically when its rollback guard is available.

## Troubleshooting and recovery

- **SSH verification fails:** Check that the endpoint and port match Litehost, the configured public key matches the private key, and the known-hosts entry has the independently verified fingerprint. The logs classify DNS, TCP, host-key, and authentication failures without printing key data.
- **Environment verification fails:** Check `APP_ENV`, `APP_DEBUG`, `APP_URL`, and `SEO_CANONICAL_URL` in the shared file without copying its contents into Actions logs. Verify `current`, the shared links, `public_html/public`, and `.htaccess` through the hosting panel or strict SSH.
- **Build validation fails:** Check `npm run build`, `npm run verify:build`, `npm run typecheck`, and `scripts/validate-release.php`. A package missing either Vite entry or an emitted asset is rejected before upload.
- **Upload fails:** Confirm runner SSH and rsync can reach Litehost and inspect the named phase. A partial candidate is never active; the next run uses a new release identifier.
- **Migration or cache preparation fails:** The active symlink remains unchanged. Read the command name and exit status in the phase log, then diagnose the server-side Laravel error without printing `.env` values. Review whether the migration is compatible with the active release before retrying.
- **The browser reports stale Blade or missing manifest assets:** During the smoke retry window, restart the website container from the Litehost website dashboard. The container restart clears the stale PHP worker state; the check then verifies that the served Blade marker and asset manifest match the candidate. If retries end first, the guarded rollback runs. Restart the container after a rollback if the public response still reflects the failed worker state, then verify the previous page and its assets.
- **Cleanup fails:** The deployment remains successful after health verification; cleanup reports a warning and can be retried after confirming `current` and the previous release. Cleanup never targets either protected release.
- **IndexNow operation fails:** Run `IndexNow production operations` with `verify-key` first. A submission response indicates receipt by the endpoint, not indexing. The operation does not deploy application files.

## Extending deployment behavior

Keep workflow YAML focused on triggers, permissions, jobs, and orchestration. Put substantial shell logic in a named script, source shared non-secret settings from `deployment-config.sh`, and add an isolated test case to `tests/deployment-scripts.sh` for path handling or failure behavior. Reuse `setup-ssh` when a separate production operation needs the same pinned connection. Keep SEO commands in `indexnow.yml` or another dedicated operation workflow. New activation checks must run before the symlink switch when possible; post-activation checks must preserve the previous target and route failures through guarded rollback.
