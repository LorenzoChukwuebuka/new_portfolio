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

The checked-in GitHub Actions workflow builds Vite assets and installs Composer dependencies in CI, uploads a timestamped release, links the shared `.env` and storage, then runs migrations and Laravel config, route, storage, and view commands inside the candidate release. It switches `production/current` atomically only after those commands succeed. SSH uses port `428` and a pinned host key from `CPANEL_KNOWN_HOSTS`. It requires no persistent Node process, queue worker, Redis, or scheduled sitemap job.

Before deploying:

1. In the shared production `.env` (`/home/<cpanel-user>/production/shared/.env` in the workflow), set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://lorenzochukwuebuka.com.ng`, and `SEO_CANONICAL_URL=https://lorenzochukwuebuka.com.ng`.
2. Configure the repository Actions secrets `SERVER_IP`, `CPANEL_USER`, and `CPANEL_SSH_SECRET`. Add `CPANEL_KNOWN_HOSTS` with Litehost's independently verified SSH host key entry for `[SERVER_IP]:428`. The workflow derives the public key from `CPANEL_SSH_SECRET`; it does not need a separate public-key secret.
3. Confirm in cPanel that the domain's document root serves `production/current/public`. The repository workflow creates the `current` symlink but does not configure the cPanel document root.
4. Confirm the hosting PHP runtime is at least 8.2 and has the extensions required by Laravel and the configured database. The workflow itself uses PHP 8.2 and Node 20 to prepare each release.
5. Keep `public/.htaccess` enabled so clean Laravel routes work. Vite assets are built into `public/build` and shipped with the release; Composer dependencies are also shipped, so production does not need Composer or Node at request time.
6. Configure one HTTPS and hostname redirect at the hosting layer after verifying how TLS/proxy headers reach PHP. The repository currently normalizes canonical tags and sitemap URLs, but does not issue a host/scheme redirect because the cPanel proxy/TLS topology is not represented here. Redirect the alternate host and HTTP requests directly to the configured canonical host with one 301 rule, and test both variants to avoid a loop.

`public/robots.txt` is intentionally absent so Apache sends `/robots.txt` through Laravel. Production allows public crawling, disallows the admin UI and admin API paths, and names the configured canonical sitemap. Public API endpoints remain crawlable because Vue uses some of them to render the home page and detail views. Local and test environments disallow crawling. Confirm there is no server-level physical `robots.txt` outside the deployed release shadowing the Laravel route.

The sitemap is generated dynamically on request, so posts that become published, projects added to the public model, and records that are unpublished or deleted are reflected immediately. The protocol limit is 50,000 URLs per sitemap; add a sitemap index and split files before this site's discovered URL count approaches that limit.

## Google Search Console

After the release is live:

1. Open `https://lorenzochukwuebuka.com.ng/robots.txt` and confirm it names the same host's sitemap.
2. Open `https://lorenzochukwuebuka.com.ng/sitemap.xml`; confirm it returns HTTP 200, `application/xml`, and canonical HTTPS URLs.
3. In Google Search Console, verify the site property separately (DNS verification is preferred for a Domain property), then open **Sitemaps**, enter `sitemap.xml`, and submit it.
4. Use URL Inspection on the home page, one blog article, and one project page. Check the rendered page and selected canonical after Google fetches them.

Do not add a Google verification token to the application unless Search Console specifically requires an HTML-token method; DNS verification does not require a code change.
