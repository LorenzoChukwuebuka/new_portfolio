# SEO Deployment and Content Conventions

## Public route and content conventions

The sitemap is generated on each request and is not cached. The current application returns a single XML `urlset` at `/sitemap.xml`; it does not emit `<lastmod>` because detail views increment `views_count`, which also changes `updated_at`.

For a new static Laravel page intended for indexing, add a named GET route using the `seo.public.*` prefix, for example:

```php
Route::get('/services', [SiteController::class, 'services'])
    ->name('seo.public.services.index');
```

This is an explicit opt-in convention. A route must resolve without parameters, return a stable public page, and not require authentication or redirect. Names outside `seo.public.*`, parameterized routes, infrastructure paths, and routes with auth-related middleware are excluded. Keep query filters and fragments out of canonical links.

The current database discovery rules are:

- Posts enter the sitemap when `Post::published()` considers them published: `status = published`, a non-null `published_at` at or before the current time, and not soft deleted.
- All Project records with a non-empty slug are included because the existing public project endpoints expose all three stored statuses (`completed`, `in-progress`, and `archived`). Add an explicit visibility policy to the public project endpoints before introducing private projects.
- A new content model must be added to `SitemapUrlDiscovery` when it is also added to public routes. The existing Post and Project records need no manual sitemap edits.

This application does not use Vue Router for public URLs. Public pages must have a Laravel web route and use the Blade layout. If Vue Router is later extended to own public URLs, use a generated manifest from its route definitions rather than maintaining a second route list.

## Shared-hosting deployment

The checked-in GitHub Actions workflow builds Vite assets and installs Composer dependencies in CI, uploads a unique candidate release, links the shared `.env` and storage, then runs migrations and Laravel config, route, storage, and view commands inside that candidate. It switches `production/current` atomically only after those commands succeed. SSH uses a pinned host key and strict host-key checking; the port defaults to `22` and can be overridden with the repository variable `CPANEL_SSH_PORT`. It requires no persistent Node process, queue worker, Redis, or scheduled sitemap job. Database migrations run before activation against the shared production database, so migrations should remain compatible with the currently active release; a failed deployment does not automatically roll back database changes.

The workflow validates the Litehost endpoint, pinned SSH host key, existing `production/current` release, and public document-root symlink before it runs production migrations or activates a candidate. A successful local build does not verify that a release has reached production.

Before deploying:

1. In the shared production `.env` (`$HOME/production/shared/.env` from the Litehost SSH account), set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://lorenzochukwuebuka.com.ng`, and `SEO_CANONICAL_URL=https://lorenzochukwuebuka.com.ng`.
2. Configure the repository Actions secrets `SERVER_IP`, `CPANEL_USER`, `CPANEL_SSH_SECRET`, `CPANEL_PUBLIC_KEY`, and `CPANEL_KNOWN_HOSTS`. Use the SSH server IPv4 address shown in the hosting panel, not a proxied website address. `CPANEL_PUBLIC_KEY` must match the key derived from `CPANEL_SSH_SECRET`; authorize that public key for the hosting account. Store the SSH host key in `CPANEL_KNOWN_HOSTS` only after verifying its fingerprint with Litehost. For port `22`, the known-hosts entry uses the plain host or IP; bracket the host with the port only for nonstandard ports. Do not trust an unverified `ssh-keyscan` result. The workflow defaults to port `22`; set the repository variable `CPANEL_SSH_PORT` only when the hosting panel confirms a different port.
3. Keep the Litehost domain document root at `/public_html`; do not change it to the release path. The panel's `/public_html/.htaccess` currently rewrites requests through `/public`, and File Manager shows `/public_html/public` is an existing symlink (28-byte target, mode 777). The panel does not display that symlink's target in its read-only details view, so the deployment now checks remotely that it resolves to `$HOME/production/current/public` and that `.htaccess` still rewrites through `/public`. This check runs before migrations and activation. The workflow only switches `$HOME/production/current`, preserving the existing domain root and public symlink.
4. Confirm the hosting PHP runtime is at least 8.2 and has the extensions required by Laravel and the configured database. The workflow itself uses PHP 8.2 and Node 20 to prepare each release.
5. Keep `public/.htaccess` enabled so clean Laravel routes work. Vite assets are built into `public/build` and shipped with the release; Composer dependencies are also shipped, so production does not need Composer or Node at request time.
6. Configure one HTTPS and hostname redirect at the hosting layer after verifying how TLS/proxy headers reach PHP. The repository currently normalizes canonical tags and sitemap URLs, but does not issue a host/scheme redirect because the Litehost TLS/proxy topology is not represented here. Redirect the alternate host and HTTP requests directly to the configured canonical host with one 301 rule, and test both variants to avoid a loop.

`public/robots.txt` is intentionally absent so Apache sends `/robots.txt` through Laravel. Production allows public crawling, disallows the admin UI and admin API paths, and names the configured canonical sitemap. Public API endpoints remain crawlable because Vue uses some of them to render the home page and detail views. Local and test environments disallow crawling. Confirm there is no server-level physical `robots.txt` outside the deployed release shadowing the Laravel route.

The sitemap is generated dynamically on request, so posts that become published, projects added to the public model, and records that are unpublished or deleted are reflected immediately. The protocol limit is 50,000 URLs per sitemap; add a sitemap index and split files before this site's discovered URL count approaches that limit.

## Google Search Console

After the release is live:

1. Open `https://lorenzochukwuebuka.com.ng/robots.txt` and confirm it names the same host's sitemap.
2. Open `https://lorenzochukwuebuka.com.ng/sitemap.xml`; confirm it returns HTTP 200, `application/xml`, and canonical HTTPS URLs.
3. In Google Search Console, verify the site property separately (DNS verification is preferred for a Domain property), then open **Sitemaps**, enter `sitemap.xml`, and submit it.
4. Use URL Inspection on the home page, one blog article, and one project page. Check the rendered page and selected canonical after Google fetches them.

Do not add a Google verification token to the application unless Search Console specifically requires an HTML-token method; DNS verification does not require a code change.
