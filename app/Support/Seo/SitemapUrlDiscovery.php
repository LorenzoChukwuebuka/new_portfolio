<?php

namespace App\Support\Seo;

use App\Models\Post;
use App\Models\Project;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SitemapUrlDiscovery
{
    private const PUBLIC_ROUTE_PREFIX = 'seo.public.';

    /**
     * Discover canonical URLs from explicitly public static routes and public
     * database records. The associative keys deduplicate routes and records.
     *
     * @return array<int, string>
     */
    public function discover(): array
    {
        // Fail loudly on a misconfigured production host instead of publishing
        // an empty sitemap or local development URLs.
        app(CanonicalUrl::class)->origin();

        $urls = [];

        foreach (Route::getRoutes() as $route) {
            if (! $this->isEligibleStaticRoute($route)) {
                continue;
            }

            try {
                try {
                    $path = route($route->getName(), [], false);
                } catch (Throwable) {
                    // Route names added after boot may not yet be in Laravel's name lookup.
                    $path = URL::to($route->uri());
                }

                $url = app(CanonicalUrl::class)->forPath($path);
                $urls[$url] = $url;
            } catch (Throwable) {
                // A route that cannot be generated safely is not sitemap eligible.
            }
        }

        Post::query()
            ->published()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->select(['id', 'slug'])
            ->chunkById(500, function ($posts) use (&$urls): void {
                foreach ($posts as $post) {
                    $this->addGeneratedRoute($urls, 'seo.public.blog.show', ['slug' => $post->slug]);
                }
            });

        Project::query()
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->select(['id', 'slug'])
            ->chunkById(500, function ($projects) use (&$urls): void {
                foreach ($projects as $project) {
                    $this->addGeneratedRoute($urls, 'seo.public.projects.show', ['slug' => $project->slug]);
                }
            });

        return array_values($urls);
    }

    private function isEligibleStaticRoute(RoutingRoute $route): bool
    {
        $name = $route->getName();

        if (! $name || ! Str::startsWith($name, self::PUBLIC_ROUTE_PREFIX)) {
            return false;
        }

        if (! in_array('GET', $route->methods(), true) || $route->parameterNames() !== []) {
            return false;
        }

        if ($route->getDomain() && preg_match('/\{[^}]+\}/', $route->getDomain())) {
            return false;
        }

        if ($this->hasExcludedPath($route->uri()) || str_contains($route->getActionName(), 'RedirectController')) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (preg_match('/^(auth|guest|verified|can|signed|password\.confirm)(:|$)/', $middleware)) {
                return false;
            }
        }

        return true;
    }

    private function hasExcludedPath(string $uri): bool
    {
        $excludedSegments = [
            'admin', 'api', 'auth', 'debug', 'diagnostic', 'diagnostics', 'error',
            'errors', 'health', 'health-check', 'internal', 'login', 'logout',
            'metrics', 'password', 'register', 'robots.txt', 'search', 'sitemap.xml',
            'storage', 'up',
        ];

        foreach (explode('/', strtolower(trim($uri, '/'))) as $segment) {
            if (in_array($segment, $excludedSegments, true)
                || preg_match('/^sitemap(?:[-_].*)?\.xml$/', $segment)
            ) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, string> $urls */
    private function addGeneratedRoute(array &$urls, string $name, array $parameters): void
    {
        try {
            $url = app(CanonicalUrl::class)->forRoute($name, $parameters);
            $urls[$url] = $url;
        } catch (Throwable) {
            // A broken route definition should not turn into a malformed <loc>.
        }
    }
}
