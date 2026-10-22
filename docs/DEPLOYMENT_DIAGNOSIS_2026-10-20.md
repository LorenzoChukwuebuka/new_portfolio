# Litehost Deployment Recovery — 2026-10-20

## Current status

The Litehost endpoint, authorized SSH key, trusted server host key, and release paths have been verified. The readiness branch was fast-forwarded to main at 6e97047 and pushed with the user's approval, triggering production run 39478805887. A follow-up deployment-fix commit, 2238686, completed successfully in run 39479919583.

Run 39478805887 passed Node and Composer installation, the frontend build, strict SSH verification, candidate upload, migrations, and Laravel cache preparation. It activated the release, then failed in old-release cleanup because Litehost's remote shell did not provide `/dev/fd/63`. The follow-up run 39479919583 confirmed that release was active, deployed release `20261020190228-39479919583-1`, and completed successfully after the cleanup fix.

After run 39479919583, the home page returned HTTP 200 HTML as expected. `/sitemap.xml` and `/robots.txt` returned HTTP 200 HTML containing the Admin app shell, not XML or plain text. The run confirmed the active release had both Laravel routes, and the document-root rewrite sent requests through `public/`. After the authenticated Litehost PHP container was restarted, all three public URLs returned HTTP 500.

The user supplied the Laravel exception page for that 500. It identifies `app/Support/Seo/CanonicalUrl.php:47`: `SEO_CANONICAL_URL must use the production hostname.` The displayed guard throws this exact exception when the canonical URL host is a local or wildcard host. This confirms the shared production `.env` canonical URL is misconfigured; its actual value has not been read or logged. This is the current root cause for the production 500, and it also prevented the sitemap and robots controllers from responding.

The next `main` push will first make a permission-restricted backup of the shared `.env` outside the document root, atomically replace only `SEO_CANONICAL_URL` with `https://lorenzochukwuebuka.com.ng`, and rebuild the active release's Laravel config cache. It then checks the live home, sitemap, and robots status/content before uploading a candidate. The workflow never logs other `.env` contents. The fix is not confirmed until that run and the independent live checks succeed.

## Git diagnosis

- The original workspace is on main at the same commit as origin/main. GitHub CLI is authenticated as the repository owner, the HTTPS remote is valid, and the repository reports push permission.
- The main branch has no branch-protection configuration or repository ruleset. No remote/local divergence was found.
- A safe push dry-run to origin/main reported everything up to date. A reviewed readiness branch has been pushed successfully; its dry-run also reports everything up to date.
- The local Git write problem is caused by this workspace exposing .git on a read-only mount. This is not a GitHub authentication or repository permission failure. The writable clone at /tmp/new-portfolio-writable.2Y5aJX/repo was used for commits and branch pushes.
- No force-push, history rewrite, or deletion of the user's local work was performed. The original workspace's existing edits and untracked .aws file were preserved; .aws was not read or changed.

## Confirmed SSH findings

### Endpoint and original failure

- Litehost Developer Tools show the SSH command uses port 22 and username lorenzoc1 at server IPv4 51.161.208.192.
- GitHub runner diagnostic run 39468207904 confirmed port 22 connected and port 428 timed out. The old deployment workflow used port 22 for ssh-keyscan but hard-coded port 428 for later SSH and rsync operations.
- Failed deploy run 39463191375 stopped at ssh-keyscan on port 22 with exit code 1. Its log did not show a more specific network or key-scan diagnostic. The later port check confirms port 428 was wrong, but it does not prove why the earlier port-22 key scan failed.
- The old workflow also referenced CPANEL_SSH_PUBLIC_KEY while the configured secret name was CPANEL_PUBLIC_KEY.

### Authorized client key and host key

- The user-generated ECDSA key is present in Litehost's .ssh/authorized_keys. Its public fingerprint matched the original GitHub Actions key pair fingerprint, SHA256:vJlGLZmmHmW3J9K/oNnM8beYG1rD3APN0AD+jMUOL2A.
- CPANEL_SSH_SECRET and CPANEL_PUBLIC_KEY have been restored to that same cPanel-generated key pair. Bounded SSH tests from this machine and a GitHub-hosted runner authenticated successfully using that key and a harmless remote true command. The hosted-runner test also validated the current Actions key pair and host pin.
- The live ECDSA server key fingerprint, SHA256:CTMMXPiJYWJrCgvkesej9heiQn3cjgN+6cpIiBIr584, matched the existing ECDSA host-key entry in the authenticated cPanel account's .ssh/known_hosts file. The entry was checked for the Litehost server address before being used as the trust pin.
- CPANEL_KNOWN_HOSTS is configured with that verified known-host entry. StrictHostKeyChecking=yes was used during the direct SSH test. The support ticket is no longer part of this process; the account owner deleted it, and the authenticated cPanel host-key record supplied the trust anchor instead.
- GitHub Actions secret plaintext cannot be retrieved through GitHub. Metadata confirms the expected names exist: SERVER_IP, CPANEL_USER, CPANEL_SSH_SECRET, CPANEL_PUBLIC_KEY, and CPANEL_KNOWN_HOSTS. Masked checks matched the Litehost username and server address; hosted-runner run 39477873674 passed key-pair, endpoint, strict host-key, and authentication checks without printing secret values. No private key or token was printed in logs.
- The temporary private-key downloads used for SSH verification were removed securely after each test. The extra ED25519 key generated during the earlier investigation is not used for deployment.

## Verified Litehost layout

A read-only SSH session verified the following without opening the environment file or changing hosting files:

- Account home: /var/www/0fafaee1-7ca4-42ed-ac2e-5b48938877c6
- production/current is a symlink to releases/2026-05-24_05-19-23.
- production/shared/.env exists and production/shared/storage exists.
- public_html/public is the literal relative symlink ../production/current/public.
- The public_html/public resolved target matches production/current/public.
- public_html/.htaccess contains the expected rewrite through /public/$1.

The existing document root and symlink were preserved. The deployment workflow does not edit either one.

## Workflow changes on main

The deployment workflow on main:

- Uses port 22 consistently for preflight, SSH, and rsync, with an optional CPANEL_SSH_PORT repository variable.
- Uses CPANEL_PUBLIC_KEY, checks that the configured public/private keys match, and requires CPANEL_KNOWN_HOSTS.
- Uses strict host-key verification and no longer trusts fresh ssh-keyscan output or disables host-key checking.
- Performs bounded DNS/TCP and non-interactive SSH authentication checks with sanitized failure classifications.
- Builds a unique candidate release, excludes shared .env and storage from rsync, and validates candidate artifacts.
- Verifies the existing domain symlink resolves through production/current/public and that its literal target continues to follow current. A direct link to a fixed old release is rejected.
- Links the shared .env and storage into the candidate, then runs migrations and Laravel cache/storage commands before switching current atomically.
- Leaves the current release untouched if candidate validation, migrations, or cache preparation fail. It retains old releases until activation; cleanup is best-effort and cannot turn a completed activation into a failed deployment.
- Prints read-only Litehost diagnostics for the active release path, rewrite rules, and cached sitemap and robots routes without printing secret values.
- On a `push` to `main`, backs up the shared `.env` once to `$HOME/.env-before-seo-canonical-20261020` with mode `600`, corrects only the `SEO_CANONICAL_URL` entry using an atomic replacement, and refreshes the active release's config cache. This is needed because the existing shared value triggers the production guard in `CanonicalUrl`; the value itself is not logged.
- Before uploading a candidate, checks that `/` is HTML, `/sitemap.xml` is XML, and `/robots.txt` is plain text with the expected prefix. A failed check stops the deployment and suppresses response bodies.
- Does not automatically reverse database migrations. Production schema changes should remain compatible with the current release in case a later preparation step fails.

The readiness branch's workflow_dispatch path is intended for an SSH preflight only once the workflow is on the default branch; upload and activation steps run only on a push to main.

## Verification and limitations

- GitHub-hosted SSH preflight run 39477873674 passed. It checked the Actions key pair and trusted host entry, reached port 22, verified the host key strictly, and authenticated with a remote true command. The temporary probe branch was deleted and its workflow disabled after the run.
- The read-only rsync list-only check over strict SSH succeeded and listed the five existing release directories. It did not upload or modify files.
- Live home, shared paths, current release, literal public symlink, resolved targets, and rewrite were verified read-only over SSH.
- Before the first production push, workflow YAML parsing and bash -n passed for all six embedded run steps, and git diff --check passed. The cleanup portability correction also passes YAML parsing and bash -n locally.
- Production run 39478805887 confirms runner dependencies, frontend build, SSH, upload, migrations, and cache preparation succeeded. It failed only after the activation command, during cleanup. The exact failure was `bash: line 96: /dev/fd/63: No such file or directory`.
- Before the PHP restart, `/` was 200 HTML while `/sitemap.xml` and `/robots.txt` returned the Admin app shell as HTML. After the restart, all three returned HTTP 500. The supplied exception page confirms the canonical URL guard is the current failure; the actual shared value remains private and uninspected.
- Composer production dependency installation and composer check-platform-reqs --no-dev passed. The Composer test suite passed: 13 tests and 81 assertions. Tests used only .env.example in the temporary clone and an ephemeral test APP_KEY; that ignored .env file was removed afterward.
- npm ci --force and npm run build passed. npm audit reported 22 advisories (1 low, 4 moderate, 15 high, 2 critical); dependency upgrades were outside this deployment repair and were not applied.
- actionlint is not installed locally. GitHub runner execution validated the secret-backed SSH behavior instead.
- `main` was at 2238686 after the second approved deployment push. Run 39479919583 successfully activated release `20261020190228-39479919583-1`; a later PHP restart exposed the invalid canonical setting, and current live endpoint checks return HTTP 500. The corrective workflow change is prepared locally and must still be pushed and verified.
- GitHub retains historical run records for the previous diagnostic attempts. The two obsolete diagnostic workflows and the temporary authentication probe were disabled; only the actual deployment workflow remains active.

## Remaining action

The shared canonical URL is the confirmed production failure. The corrective workflow change is prepared in the writable clone; after it is pushed, the run will safely repair that one setting, check the current live endpoints before upload, then deploy the candidate. Production recovery remains unverified until the run succeeds and the home page, XML sitemap, and plain-text robots file return the expected responses.
