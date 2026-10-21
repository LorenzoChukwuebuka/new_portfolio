# IndexNow integration

## Existing public URL architecture

Laravel owns the public routes (`/`, `/projects`, `/projects/{slug}`, `/blog`, and `/blog/{slug}`); Vue Router is limited to the admin application. `SitemapUrlDiscovery` is the public URL source of truth: named `seo.public.*` GET routes are eligible when they have no unresolved parameters, and database discovery uses `Post::published()` plus every project because `SiteController` currently serves projects in every status. The canonical host and HTTPS rules come from `SEO_CANONICAL_URL` and `CanonicalUrl`.

Posts are created and edited in `Admin\PostController`, become public only when `status=published` and `published_at <= now()`, and use soft deletes with a restore action. Future-dated published posts are now queued in the local outbox with a future `available_at`; they are not sent before the post is public. Project create, edit, reorder, and hard delete are handled by `Admin\ProjectController`; `completed`, `in-progress`, and `archived` projects are all public today. View counts use direct increments and do not emit content events.

There is no queue worker or scheduled task configured in the Litehost deployment. The deployment builds each timestamped release, links the shared `.env` and storage, runs migrations and config/route/view caching, then switches `production/current`; the public document root rewrites through the current release's `public` directory. The verification URL is a root Laravel route, so it follows the active release without a separate file-sync step or a Vue route.

## Key and endpoint

`config/indexnow.php` contains a stable, generated 32-character key by default. It is a protocol verification key, not Laravel `APP_KEY`, Google verification, or an SSH key. `INDEXNOW_KEY` can override it in the shared `.env`, but keep the value unchanged across releases. During release preparation, `indexnow:publish-key-file` writes the exact configured key to `{release}/public/{key}.txt` with mode `0644`; the checked-in public `.htaccess` serves that physical text file before Laravel routing. The root route `/{key}.txt` remains a fallback and rejects non-matching keys. Both use the same configured value, and the route is in `seo.infrastructure.*`, so it is excluded from the sitemap.

The service posts JSON to `https://api.indexnow.org/indexnow` and includes `host`, `key`, `keyLocation`, and `urlList`. It canonicalizes and validates HTTPS URLs against the configured canonical host and eligible public Laravel route patterns. Requests are disabled outside `APP_ENV=production`.

IndexNow protocol references: [official documentation](https://www.indexnow.org/documentation) describes root key-file verification, the POST body and 10,000 URL limit, and response codes 200/202. Acceptance means URLs were received; it does not guarantee crawling or indexing.

## Content notifications and recovery

In enabled production only, post/project observers write notification rows to `indexnow_notifications`. Admin create/update/delete/restore model writes and project reordering are transaction-scoped, so their outbox rows commit or roll back with the content change. Meaningful model changes trigger notifications after the SQL write; slug changes include both the previous and current URLs. Unpublishing/deletion notifies the former URL and the affected listing/home pages. Relation updates for tags and media are covered by the admin controllers. Page views and counters are ignored. Local, test, and other nonproduction writes do not add notifications.

Rows are deduplicated by canonical URL within an immediate or scheduled one-minute bucket, so a future publication cannot replace an immediate removal notification for the same URL. A web termination callback claims a bounded batch after the response has been sent; it does not require Redis or a persistent worker. Delivery uses short connect/request timeouts, bounded HTTP retries for connection/server failures, then durable exponential retry scheduling (maximum five delivery attempts; a 429 waits at least ten minutes unless `Retry-After` is longer). Permanent failures remain visible in the outbox and application logs without recording the key or response body.

The outbox survives process termination and API errors. With no site traffic, delayed retries wait until the next request or an operator runs the recovery command. Litehost cron availability has not been inspected; if available, a one-minute cPanel cron can run:

```sh
php /home/<cpanel-user>/production/current/artisan indexnow:deliver-pending --batch=100 --max-batches=5
```

Review failed entries before requeueing them with `indexnow:deliver-pending --retry-failed`. Direct bulk SQL updates to posts/projects bypass observers; use normal Eloquent updates for public content. The in-repository project reorder path has been adjusted accordingly.

For source-maintained static pages, submit only the changed public route after deploying its content change:

```sh
php artisan indexnow:submit-route seo.public.home
php artisan indexnow:submit-route seo.public.home --execute
```

The first form is a dry run. `--execute` checks the production key URL before queueing the route. The command accepts only eligible static `seo.public.*` route names, so it does not introduce a second URL list.

## One-time existing URL submission

Use the sitemap discovery service; the command does not hardcode URL paths. A dry run is the default:

```sh
php artisan indexnow:submit-existing
```

After deployment, first confirm `https://lorenzochukwuebuka.com.ng/{key}.txt` returns HTTP 200 with the exact configured key and plain-text content. The execution command repeats that verification remotely before it writes a one-time run marker or sends URLs:

```sh
php artisan indexnow:submit-existing --verify-key
php artisan indexnow:submit-existing --execute --batch=100 --max-batches=100
```

The Litehost deployment workflow can run either operation through its manual dispatch input. Choose `verify-key` first to check the key configured in the shared production `.env` without submitting URLs; after the live key URL is confirmed, choose `submit-existing` to execute the guarded one-time run. The execution command repeats key verification before any IndexNow request.

The database run marker prevents a completed full submission from being repeated. An interrupted run resumes its durable pending rows. Permanently failed rows require the explicit recovery command above. Do not execute this from local/testing environments; the command and service reject non-production submissions, and automated tests fake all HTTP calls.

## Deployment and verification boundary

The existing release migration and config/route cache steps carry the new outbox tables and root route. The configured key file is served by Laravel, so no separate `public_html` symlink or release file needs to be created. If `INDEXNOW_KEY` is added to the shared `.env`, rebuild Laravel config cache through the normal deployment process. Local tests use `Http::fake()`; they do not establish production key accessibility or actual search indexing. Verify the live key URL and API response after release.
