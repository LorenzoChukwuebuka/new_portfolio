# Lawrence Chukwuebuka Obi — Portfolio

Laravel 12 serves the public portfolio as server-rendered Blade pages. Vue 3 is
loaded only for interactive public islands and the authenticated `/admin` SPA.
Tailwind CSS v4 and Vite 7 provide the shared token-based design system.

## Local setup

Requirements: PHP 8.2+, Composer, Node.js 22.12+, and a database supported by
Laravel. SQLite is suitable for a local install.

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Configure the database in `.env`. To create an administrator with the database
seeder, set both `ADMIN_EMAIL` and `ADMIN_PASSWORD` in the environment first;
the seeder does not invent credentials, and refuses to seed a production admin
without a password. Then run:

```sh
php artisan migrate --seed
npm ci
npm run build
php artisan storage:link
php artisan serve
```

The public site is available at `http://127.0.0.1:8000`. The admin SPA is at
`/admin/login`. Public article content is sanitized and rendered by Laravel;
Vue islands add form, copy-link, and navigation behavior without replacing the
server-rendered content.

## Useful commands

```sh
npm run dev                 # Vite development server
npm run build               # Production assets
npm run typecheck           # Vue and TypeScript type checks
npm run check:contrast      # Check documented WCAG text pairs
php artisan test            # Laravel unit and feature tests
```

See [docs/DESIGN_SYSTEM.md](docs/DESIGN_SYSTEM.md) for tokens, component usage,
and the local public kitchen sink at `/_design`. In a Vite development build,
the authenticated admin kit is at `/admin/_kit`.

## Project structure

- `resources/views/` — public Blade layouts, pages, and reusable partials.
- `resources/js/site.ts` — small public entry and dynamically loaded islands.
- `resources/js/admin.ts` — isolated admin SPA entry and lazy route views.
- `resources/css/tokens.css` — primitive and semantic design tokens.
- `app/Support/Content/ArticleHtml.php` — Quill HTML allow-list sanitizer and
  article outline builder.
- `docs/` — design, SEO, deployment, and overhaul notes.

## Deployment

The production workflow runs on pushes to `main`; pushing this overhaul to
`main` therefore starts a production deployment. Deployment requires the
existing Litehost and IndexNow environment configuration documented in
[docs/SEO_DEPLOYMENT.md](docs/SEO_DEPLOYMENT.md). Run `npm ci` without
`--force` when installing assets. The release workflow runs pending migrations
before it activates a release. Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` securely
before running `php artisan db:seed --class=Database\\Seeders\\AdminUserSeeder`.
