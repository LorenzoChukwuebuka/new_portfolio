# Litehost Deployment Diagnosis — 2026-10-20

## Status

The deployment workflow and documentation have local, uncommitted fixes. No push to `main` and no production deployment were performed. Production `/sitemap.xml` still returns HTTP 200 with HTML rather than XML; this is consistent with the SEO release not having reached production and is not evidence that the new sitemap route itself failed.

The site document root remains `/public_html`. Litehost File Manager shows `/public_html/public` as an existing 28-byte, mode-777 symlink. The visible `/public_html/.htaccess` rewrites requests to `/public/$1`. Its file manager details view does not expose the symlink target. The workflow now follows the existing layout: before running migrations or switching releases, it checks over SSH that `public_html/public` resolves to `production/current/public` and that the rewrite is still present. It never edits the domain mapping or replaces that symlink.

The authenticated File Manager reconfirmed `/public_html/.htaccess` (306 bytes) and `/public_html/public` (28 bytes, mode 777). I read the rewrite file without saving; it contains `RewriteEngine on` and `RewriteRule ^(.*)$ /public/$1 [L]`. Its metadata still does not show the symlink target. `/production` contains `current` (28-byte entry), `releases` (five release folders dated May 23–24, 2026), and `shared`. `/production/shared` contains `storage`, `.env`, and `.env.dbhostbak`; I did not open either environment file. No hosting files were changed.

## Git diagnosis

- The local branch was `main`, matched `origin/main`, and had no pre-existing tracked changes at the start of this diagnosis.
- `gh auth status` showed the signed-in GitHub account had repository Admin/push access. `git ls-remote` and `git push --dry-run origin main` succeeded. The repository had no branch protection preventing this push.
- A safe push attempt from the original workspace reached GitHub, but updating local remote-tracking metadata failed because this environment exposes `.git` as read-only. A fresh temporary clone completed the corresponding Git operation. This identifies a local sandbox limitation, not a confirmed GitHub authentication or repository write-permission failure.
- The current `.git` directory reports ordinary owner write bits, but `findmnt -T .git` shows the filesystem mounted `ro`; `git push --dry-run origin main` reports `Everything up-to-date`. The limitation is the workspace mount, not Unix mode bits or GitHub authentication. Git commits must use a writable clone/worktree outside this mount; do not try to remount or chmod the protected `.git` directory.
- No force push, history rewrite, or production-triggering push was used.

## Confirmed deployment causes

1. The failed GitHub run [39463191375](https://github.com/LorenzoChukwuebuka/new_portfolio/actions/runs/39463191375) stopped in `Setup SSH`: `ssh-keyscan -H -p 22 <SERVER_IP>` exited 1. The log did not contain a more specific `ssh-keyscan` diagnostic. Separate runner diagnostics later showed port 22 connects to the Litehost endpoint and port 428 times out. The old workflow also mixed ports: key scanning used 22 while rsync and the remote action hard-coded 428. Litehost's Developer Tools show `ssh -p 22 lorenzoc1@51.161.208.192`.
2. The configured `SERVER_IP` and `CPANEL_USER` Actions secrets did not match the values shown by Litehost. They were corrected through GitHub's secret update mechanism. Masked checks confirmed the configured values match; no secret plaintext was printed or recorded.
3. The old workflow referenced `CPANEL_SSH_PUBLIC_KEY`, while the configured public-key secret is named `CPANEL_PUBLIC_KEY`.
4. Litehost's SSH key manager displayed no authorized SSH keys. The original configured private/public key pair parsed and matched in a masked GitHub Actions check, but it could not authenticate until its public key was authorized for this hosting account.
5. There is no `CPANEL_KNOWN_HOSTS` Actions secret. A final `gh secret list` check confirmed it is absent, and `gh variable list` returned no variables. Host-key results from `ssh-keyscan` are observations, not a trusted fingerprint. The new workflow requires a verified host-key pin and keeps `StrictHostKeyChecking=yes`; it will fail closed until that pin is supplied.

During the follow-up, Litehost's reverse DNS for the server resolved to `srv3.getlitehost.net`, but DNS returned no SSHFP record for that name. Litehost's published SSH guide does not publish a host-key fingerprint. The local public key was checked against the GitHub deployment-key fingerprint and proved to be a different key, so it was not installed.

On 2026-10-20, I repeated the endpoint check from the local environment: TCP to `51.161.208.192:22` connected, while TCP to port `428` timed out. `ssh-keyscan` observed ED25519 fingerprint `SHA256:amQObvd5PHlPWuDsmKuW/Q6HVSJ8Ajt7TNiu6eGBScY` and RSA fingerprint `SHA256:Jsq6+z9r4lu4OX3YZY+Vsa3VEUcrHxy8sOcgJTmCtSs`. These are untrusted network observations only; neither was added to GitHub or accepted as Litehost's identity.

The host fingerprint is required for the configured strict SSH check because the deployment key and the host key prove opposite sides of the connection: the deployment key proves the runner is authorized to log in, while the pinned host key proves the runner reached the intended Litehost server. The pin remains pending Litehost's authenticated confirmation.

A temporary, branch-scoped GitHub Actions diagnostic ([run 39472203811](https://github.com/LorenzoChukwuebuka/new_portfolio/actions/runs/39472203811)) derived the original public key from the GitHub private-key secret, checked it against `CPANEL_PUBLIC_KEY`, and logged only its fingerprint `SHA256:vJlGLZmmHmW3J9K/oNnM8beYG1rD3APN0AD+jMUOL2A`. The check passed, proving the original GitHub secrets matched. That fingerprint did not match the only local `.pub` key (`id_ed25519.pub`), and no other local public keys were present. The temporary diagnostic branch was deleted after the result was collected; it made no deployment changes.

Because the matching public half of the original key was unavailable locally, I generated a dedicated ED25519 deployment pair and replaced only this repository's `CPANEL_SSH_SECRET` and `CPANEL_PUBLIC_KEY` Actions secrets. The pair was derived and compared locally before upload. The new public fingerprint is `SHA256:Ic9AjqcFiCiXruA5fEc7JrkpN0nwuQYov6pHO5LqD/A`; its public half is saved for owner handoff at `/tmp/new_portfolio_litehost_deploy_key.pub`. The temporary private-key file was removed after the secret update. The new public key has not been authorized in Litehost, so the hosting account still cannot accept this key yet.

Litehost's authenticated support form accepted a request for its authoritative SSH host-key fingerprint. Ticket [LHTK_FTW-790-66072](https://client.getlitehost.com/support/tickets/d5308768-251d-4800-edec-247e390921e6) is open in the General department. The request includes only the public hostname, server IP, port, and account SSH username; no credentials were sent. The reply is pending.

Litehost's published guide confirms SSH uses port 22, the SSH username shown in Developer Tools, and the server IPv4 from the site's overview; it also explains that the public half is what is added to the panel. See [Litehost SSH guide](https://getlitehost.com/guides/use-ssh-on-shared-hosting/). Litehost's [Laravel guide](https://getlitehost.com/guides/run-laravel-on-shared-hosting/) recommends serving Laravel's `public` directory; this site already does so through its `/public_html` rewrite and symlink arrangement.

## Changes in the working tree

### `.github/workflows/deploy.yaml`

- Uses the verified port by default (`22`), with an optional `CPANEL_SSH_PORT` repository variable.
- Uses the actual `CPANEL_PUBLIC_KEY` secret name, checks the private/public pair without printing key material, and requires a pinned `CPANEL_KNOWN_HOSTS` value.
- Uses the same strict SSH options and port for preflight, rsync, and remote commands; removes `ssh-keyscan` trust-on-first-use and `StrictHostKeyChecking=no`.
- Checks DNS/TCP and performs a non-interactive SSH authentication preflight with classified, sanitized errors.
- Supports a manual `workflow_dispatch` from `main` for build and SSH preflight only; upload and release activation remain push-to-`main` steps.
- Builds and uploads a unique candidate release, preserves the shared `.env` and storage, validates Laravel/build artifacts, and runs migrations/cache preparation before an atomic `current` symlink switch.
- Checks the existing Litehost public symlink and rewrite before migrations. A failed check leaves the current release untouched.
- Keeps old releases until after activation; cleanup failure is only a warning. Database migration changes are not automatically reversed if a later command fails, so migrations must be backward-compatible with the live release.

### Documentation

- `docs/SEO_DEPLOYMENT.md` now describes the existing `/public_html` symlink routing and points to this diagnosis record. It no longer requests a document-root change.

## Verification performed

- The final workflow YAML parsed successfully; all six embedded shell blocks passed `bash -n`; `git diff --check` passed.
- Earlier local checks passed: Composer production install/platform check; `composer test` (13 tests, 81 assertions); Vite production build; local rsync dry-run; and `git diff --check` before the latest documentation/workflow edits.
- GitHub diagnostic run [39468207904](https://github.com/LorenzoChukwuebuka/new_portfolio/actions/runs/39468207904) passed its masked endpoint checks. It did not prove the hosting account accepts the key or complete a deployment.
- GitHub diagnostic run [39472203811](https://github.com/LorenzoChukwuebuka/new_portfolio/actions/runs/39472203811) verified the original private/public deployment-key pair and emitted only its public fingerprint. The temporary branch is now deleted.
- The replacement key pair was verified locally before both GitHub Actions secrets were updated. `gh secret list` confirms the two secret names are present with updated timestamps; it does not expose their plaintext. No new GitHub Actions run has tested the replacement pair or the Litehost login.
- `actionlint` is not installed in the local environment.
- The workflow has not been run against production. The public endpoints have not been rechecked after a deployment because none occurred.
- npm audit reported 22 dependency findings (1 low, 4 moderate, 15 high, 2 critical). No automated dependency upgrade was applied as part of deployment repair.

## Remaining secure setup

The workflow is intentionally not deploy-ready until both checks below are completed:

1. Wait for Litehost's response to ticket `LHTK_FTW-790-66072`, validate the returned key type and SHA256 fingerprint against the live endpoint, and store the matching known-hosts entry as the `CPANEL_KNOWN_HOSTS` Actions secret. Do not copy an unverified `ssh-keyscan` result into this secret.
2. The account owner must add the replacement public key from `/tmp/new_portfolio_litehost_deploy_key.pub` in this site's Litehost Developer Tools SSH key manager. Its fingerprint is `SHA256:Ic9AjqcFiCiXruA5fEc7JrkpN0nwuQYov6pHO5LqD/A`. I left the Litehost key form open, but the browser security handoff requires the owner to enter and submit an authentication key. No key has been entered or submitted.

Then run the workflow's SSH preflight and candidate deployment only after reviewing the ready commit. A push to `main` triggers production; this record does not authorize or perform that push. After an approved deployment, verify the home page, `/sitemap.xml` (HTTP 200 XML), and `/robots.txt` on the production domain.

## Preserved local work

The existing untracked `.aws` file was not inspected, modified, copied, or removed. The original workspace still has local tracked edits and the untracked diagnosis document because its `.git` mount is read-only. A writable clone was used to publish the reviewed workflow and documentation to `codex/litehost-deploy-readiness-20261020` (workflow commit `79b9a6b`, latest diagnosis update `206f5d1`). This branch does not trigger the production workflow. No push to `main` or production deployment was performed.
