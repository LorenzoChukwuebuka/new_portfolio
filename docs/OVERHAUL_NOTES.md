# Final portfolio overhaul notes

## Decisions

- Public pages are Blade-rendered. Vue is limited to dynamically imported
  islands for forms, copy/share controls, and navigation behavior. This keeps
  titles, metadata, article content, project descriptions, and comments in the
  first HTML response and removes the in-DOM runtime compiler from the public
  entry.
- Portfolio identity, contact details, social URLs, skills, navigation, and
  the displayed years value live in `config/portfolio.php` so Blade and
  structured data read one source.
- Article HTML is passed through `App\Support\Content\ArticleHtml`, an
  allow-list sanitizer and Quill 2 list normalizer. Videos are restricted to
  YouTube and Vimeo embeds. Project descriptions remain plain text and are
  escaped before paragraph/list/heading blocks are generated.
- Public project/category filters use server-side query parameters and native
  links. Pagination and adjacent/related content are supplied by Laravel.
- Admin keeps the existing Sanctum token and JSON API contracts. Shared Vue
  controls and semantic tokens replace duplicated page-specific patterns;
  charts are small SVG components, with no charting runtime dependency.
- The `/tmp` visual fixture uses `admin@example.test`. Its password is read
  from the already-configured local `ADMIN_PASSWORD` only for local seeding and
  screenshot login. No credentials were written to source or documentation.
- Existing IndexNow/deployment changes already present in the working tree
  were preserved. The deployment workflow runs on `main` pushes and manual
  dispatch only when the ref is `main`; the feature branch itself does not
  trigger production deployment. The owner subsequently requested pushing the
  completed branch to `main`, which will start the production deployment.

## Build and payload measurements

The earlier public app entry was reported at about 324 kB raw / 115 kB gzip.
The current production build reports:

| Resource | Raw | Gzip |
| --- | ---: | ---: |
| Public site entry | 4.89 kB | 1.95 kB |
| Vite module preload shim | 1.12 kB | 0.66 kB |
| Initial home JS (entry + shim) | 6.01 kB | 2.61 kB |
| Deferred contact island | 4.93 kB | 2.00 kB |
| Deferred Vue runtime (tree-shaken core + DOM) | 77.60 kB | 31.24 kB |
| Estimated home JS after the contact island nears the viewport | 88.71 kB | 36.00 kB |
| Shared CSS | 105.55 kB | 18.18 kB |
| Self-hosted DM Sans WOFF2 | 36.93 kB | — |
| Self-hosted Playfair Display WOFF2 | 38.40 kB | — |
| Self-hosted JetBrains Mono WOFF2 | 40.40 kB | — |

These are Vite build sizes. The home JavaScript remains under 40 kB gzip even
after the contact form island loads near the viewport. The previous rough 115
kB gzip figure is from the supplied baseline, not a reproduced build of the
old branch.

## Verification

- `npm ci` passed without `--force` using a writable `/tmp` npm cache. The
  system default npm cache is read-only in this environment.
- `npm run build` passed.
- `npm run typecheck` passed.
- `npm run check:contrast` passed all 29 documented foreground/background
  pairs.
- `php artisan test` passed: 60 tests, 345 assertions.
- The 36-route/viewport screenshot matrix is stored under
  `/tmp/new_portfolio_after-final6`. Every route returned 200 with no console
  errors, failed requests, overflow, hidden reveal items, or missing fonts.
- The corrected dashboard shell was captured at 360, 390, 768, and 1440px in
  both themes. Contact, comment, and copy-link islands mounted when brought
  near the viewport.
- Axe WCAG 2.0/2.1/2.2 AA checks ran on home, blog, post, projects, login, and
  dashboard at 390px in both admin themes. No serious or critical violations
  were found (20 or more passing rules per run). Neither Playwright nor axe is
  an application dependency.
- Throttled mobile Lighthouse on the built local site reported performance
  0.84, LCP 2.8s, CLS 0, TBT 50ms, and FCP 2.0s. LCP misses the 2.5s target
  by 0.3s; CLS is below 0.05 and TBT is below 200ms. The run used Chrome's
  simulated mobile profile and is specific to this local host.
- The production deploy workflow uses Node 22.12.0 and `npm ci`; it is
  restricted to `main`. A push to `main` runs the deployment workflow, which
  checks the configured SSH credentials and host pin, prepares a candidate,
  and runs pending migrations before activation. The deploy configuration and
  migration outcome must be read from the corresponding GitHub Actions run;
  a successful Git push alone is not proof of deployment.

## Performance and deferred checks

- Lighthouse used Chrome's simulated mobile profile against the built local
  site. The 0.3s LCP miss remains recorded rather than hidden; the other
  measured Core Web Vitals are within their targets.
- Screenshot artifacts remain in `/tmp` as requested and are not checked in.
- npm reported dependency advisories during `npm ci`; no forced or breaking
  audit upgrade was applied as part of this visual and behavior overhaul.

## Production rendering incident — 2026-10-22

- The homepage source itself was intact: Blade returned the expected server
  rendered content and SEO metadata, but its HTML referenced old hashed Vite
  files. Those URLs returned Litehost HTML 404 pages, so the browser displayed
  SEO content without its visual styling or Vue behavior. Candidate manifests
  and files existed, but the HTML came from stale Laravel/PHP runtime state
  after the atomic `production/current` switch. Restarting the Litehost website
  container refreshed that state without changing the release or document-root
  links. The dark layout, self-hosted fonts, portrait, navigation, and contact
  island then rendered in the live browser.
- A second controlled release reproduced the mismatch. Pointing
  `public_html/public` at a candidate's physical `releases/{id}/public` path
  did not refresh the response: it still returned the old commit's HTML and
  missing asset hashes. That experiment was rolled back. The workflow preserves
  the verified stable `public_html/public -> production/current/public`
  arrangement and refuses a detached public root; changing symlink shape is
  not treated as a runtime refresh.
- The verified document-root rewrite remains unchanged:
  `public_html/.htaccess` routes requests through `/public/`. The workflow
  validates that rewrite and the current web-root resolution and switches only
  `production/current`. A failed browser check switches it back to the captured
  release. No production database, shared storage, or `.env` value was changed
  by the corrective changes.
- Deployment candidates now carry matching commit/release markers in the
  release root and public build directory, including manifest and Composer
  lock checksums. Before activation, the workflow validates every manifest
  output and entry point on the server. It also copies content-hashed assets
  additively between the active and candidate releases, checking that any
  duplicate hash has identical bytes. This keeps stale PHP output styled and
  its old scripts available during a provider restart or rollback; each
  release keeps its own manifest. After activation, Playwright verifies
  served asset bytes/types, release markers, Blade metadata, page rendering,
  Vue islands, navigation, mobile overflow, SEO routes, and expected 404s.
  A failed post-activation smoke restores the captured previous release only
  when `production/current` still points to the failed candidate, and writes a
  failure record; database and shared storage remain in place. Old releases
  are cleaned only after the smoke passes.
- `npm run verify:build` validates the local Vite manifest and required site
  and admin entries before upload. `npm run smoke:production` supports local
  `SMOKE_BASE_URL` checks, while the deploy job requires production indexability
  and the exact workflow commit. Playwright is a development-only dependency
  used by CI, not a production runtime dependency.
- Litehost exposes a website-container restart control in the authenticated
  panel, but the configured deployment access is SSH only; no supported
  restart API or panel credential is configured for GitHub Actions. After
  activation, the workflow retries its browser check up to eight times and
  prints a restart prompt. An operator must click **Restart container** in the
  Litehost panel during that window. If the served commit, asset responses, or
  browser rendering still fail, the action records the failed candidate and
  rolls back rather than reporting success. This guard prevents silent
  regressions, but unattended activation remains dependent on a supported,
  narrowly scoped Litehost restart integration.
  Litehost's own [GitHub shared-hosting guide](https://getlitehost.com/guides/deploy-from-github/)
  documents restarting the website container after deploying long-lived
  workers; the production restart behavior was reproduced in this incident.
- The first two guarded deployment attempts stopped before migration or
  activation: Litehost PHP CLI rejects both `php -` and `/dev/stdin`. The
  workflow now invokes the checked-in `scripts/validate-release.php` from the
  uploaded candidate. It was exercised against a matching release fixture;
  both failed attempts left the prior release active and their rollback checks
  confirmed it was unchanged.
- A separate live metadata check found double-escaped entities in the page
  title/social metadata. The shared public Blade layout now normalizes one
  already-escaped layer before its normal escaped output; the SEO feature test
  guards the title output. Server-rendered article/content and SEO markup were
  retained.
- The first two guarded deployment attempts stopped before activation because
  Litehost PHP CLI rejected `php -` and `/dev/stdin`; invoking the checked-in
  validator script fixed both limitations. The next two runs activated
  candidates but correctly failed their browser smoke because live Laravel
  continued to serve old HTML and missing asset hashes; both rolled back. The
  physical public-root experiment also failed and its workflow behavior was
  reverted. Run `39848489457` then stopped before activation because the Litehost
  remote Bash had no usable `/dev/fd` for process substitution; a temporary
  file list fixed that environment-specific constraint. Run `39848794375`
  passed. Its first browser check saw stale runtime output, the website
  container was restarted from the Litehost panel during the retry window, and
  the next check passed for commit `3ef9181922a2714d0d5aabd1df5cb06720446068`
  in release `20261022202708-39848794375-1`. It verified all 39 manifest
  assets, page metadata/rendering, Vue islands, navigation, responsive
  overflow, SEO endpoints, `/up`, and expected 404 behavior. No migration was
  pending. Cleanup retained the active and immediately previous releases.
  Documentation-only pushes are excluded from production deployment; pushes
  that change application or deployment code still go through the full smoke
  and rollback gate.
- The final main deployment was run `39849732957`, commit
  `25164ad52720cacc525a239986f801f33e16d51d`, release
  `20261022203327-39849732957-1`. The first browser attempt detected stale
  runtime output; restarting the Litehost container during the retry window
  let the next attempt pass. That final run verified the 39 manifest outputs,
  the home/blog/post/projects/project/admin-login pages, self-hosted font
  responses, Vue islands, mobile menu behavior, mobile widths, SEO files,
  `/up`, and the 404 route, then retained the active and previous releases.
  A separate live-browser check confirmed all eight home images loaded, the
  contact island mounted, the reveal elements were visible, no console errors
  occurred, and a 390px viewport had zero horizontal overflow.
