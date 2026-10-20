# SEO Architecture Audit

Audit completed before SEO implementation changes on 2026-10-20.

## Application and rendering model

- `composer.json` requires Laravel 12 and PHP `^8.2`. The deployment workflow builds on PHP 8.2; the available CLI is PHP 8.3.35.
- Vite builds Vue 3 assets from `resources/js/app.ts`. Public documents are selected by Laravel routes in `routes/web.php`; each route returns a distinct Blade view.
- The public Blade views place custom Vue components inside `#app`. The main Vue entry registers those components and mounts them. Vue Router is installed and configured with browser history, but its route records currently come only from `resources/js/admin/router.ts`. Public navigation and direct URLs are Laravel routes, not Vue Router routes.
- The Blade response for public home, index, and detail pages contains component tags but not the page's meaningful content. Vue fetches listing and detail content from public JSON endpoints after mount. Google needs JavaScript rendering to see most page content.
- Blog and project detail controllers already load their database record for metadata, but the Vue components independently fetch it again. Blog index and project index controller actions return only the Blade view.

## Routes, public content, and current URL behavior

- Public web routes are `/`, `/projects`, `/projects/{slug}`, `/blog`, and `/blog/{slug}`. Only `blog.show` is named. The project detail view calls a nonexistent `projects.show` route when generating its canonical URL.
- `routes/web.php` ends with `/{any}` and returns `admin.blade.php` for all otherwise unmatched GET paths. This makes misspelled URLs and unknown API paths return the admin shell with HTTP 200 instead of a real 404.
- `routes/api.php` exposes published-post JSON and project JSON. Admin JSON routes are grouped under `auth:sanctum`.
- `Post` uses soft deletes, `status`, and `published_at`. Its `published()` scope correctly requires published status and a publication timestamp that is not in the future, but the current public controllers only check status.
- `Project` has `completed`, `in-progress`, and `archived` states, but its public HTML and JSON routes currently expose all records without a visibility filter or soft deletes. The sitemap should follow that current public accessibility unless the application adds an explicit private state.
- Both Post and Project detail requests increment `views_count` through Eloquent, which also updates `updated_at`. Therefore those `updated_at` values are not reliable sitemap last-modified dates.

## Metadata and crawler behavior

- `resources/views/layouts/app.blade.php` has common title, description, canonical, Open Graph, Twitter, and robots tags. Canonical and social URLs use the request host, and the robots directive is always `index, follow`.
- Blog detail sections use post title and excerpt. Project details use project title and description, but the canonical route name is missing. Project JSON-LD is wrapped in `@verbatim`, so its Blade variables are output as literal text instead of real record values.
- `admin.blade.php` does not set a `noindex` directive.
- The public content pages contain a viewport and language declaration. Images in Vue cards generally use the record title as alt text; this should remain aligned with content metadata.

## Robots and shared-hosting deployment

- `public/robots.txt` is a physical file containing an unrestricted empty `Disallow`. Apache serves it before Laravel, so a Laravel robots route would not take effect while that file exists.
- `public/.htaccess` disables directory indexes, removes trailing slashes, and sends nonexistent paths to `public/index.php`.
- `.github/workflows/deploy.yaml` builds assets and installs Composer dependencies in GitHub Actions, deploys timestamped releases over SSH, symlinks shared `.env` and storage, points `production/current` at the release, and runs migrations and Laravel cache commands. The workflow does not show whether cPanel's document root points to `production/current/public`; that server mapping cannot be confirmed from this repository.
- The example and local `APP_URL` values are localhost. Production canonical URL configuration is not present in the repository.
- At audit time, `vendor/` was absent, so Laravel commands and feature tests could not bootstrap. Composer dependencies were installed locally later to run verification; `vendor/` is ignored. A pre-existing change to `package-lock.json` was present at audit start and is outside this task.

## Implementation policy

The implementation will opt in static Laravel pages by a `seo.public.*` route-name convention, omit unresolved route parameters from route discovery, and add concrete Post and Project URLs from the records their public routes currently expose. The production canonical base will come from environment configuration. Vue Router will not be used as a public sitemap source because public URLs are Laravel routes. Important content will get server-rendered Blade fallbacks so that the initial response contains useful text and links while existing Vue behavior remains available.
