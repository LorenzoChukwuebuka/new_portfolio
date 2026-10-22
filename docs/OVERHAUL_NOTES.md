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
  the SEO content without its visual styling or Vue behavior. The active
  release's `public/build/manifest.json` and all files named by that manifest
  were present and served with the expected CSS, JavaScript, and font MIME
  types. The broken response therefore came from stale rendered HTML/runtime
  state across an atomic `production/current` symlink activation, rather than
  a missing build or incorrect `public_html/public` mapping.
- Restarting the Litehost PHP container refreshed that stale state without
  changing the release or document-root symlinks. The homepage then served the
  active manifest's asset hashes; its dark styling, self-hosted fonts, hero
  image, navigation, and Vue contact island rendered in the live browser. This
  points to retained PHP worker/realpath/view state after symlink activation;
  the workflow now verifies active commit and asset identity to detect that
  condition instead of assuming `/up` is sufficient.
- The production document-root arrangement was left untouched: `public_html`
  rewrites through its existing `.htaccess` to `public/`, and
  `public_html/public` follows `production/current/public`. Candidate deploy
  validation now resolves and checks these exact relationships before
  activation. No production database, shared storage, `.env`, or symlink was
  changed during diagnosis.
- Deployment candidates now carry matching commit/release markers in the
  release root and public build directory, including manifest and Composer
  lock checksums. Before activation, the workflow validates every manifest
  output and entry point on the server. After activation, Playwright verifies
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
- A separate live metadata check found double-escaped entities in the page
  title/social metadata. The shared public Blade layout now normalizes one
  already-escaped layer before its normal escaped output; the SEO feature test
  guards the title output. Server-rendered article/content and SEO markup were
  retained.
- Incident verification before the corrective workflow deployment: active
  public routes and SEO endpoints returned HTTP 200, unknown paths returned
  404, production assets matched the served manifest after the Litehost
  restart, and a live browser confirmed the styled page and loaded fonts. Full
  deployment run, commit, release ID, and post-activation checks are recorded
  below after the workflow has run.
