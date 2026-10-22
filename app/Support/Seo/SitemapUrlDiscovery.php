<?php

namespace App\Support\Seo;

use App\Models\Post;
use App\Models\Project;
use Illuminate\Http\Request;
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

    /**
     * Return a canonical URL for a route that participates in the public SEO
     * contract. This also supports URLs that have just been removed from the
     * sitemap, such as a former post slug being reported as unavailable.
     */
    public function publicRouteUrl(string $name, array $parameters = []): string
    {
        $route = Route::getRoutes()->getByName($name);

        if (! $route || ! Str::startsWith((string) $route->getName(), self::PUBLIC_ROUTE_PREFIX)) {
            throw new \InvalidArgumentException('The route is not part of the public SEO routes.');
        }

        if (! in_array('GET', $route->methods(), true)
            || $this->hasExcludedPath($route->uri())
            || str_contains($route->getActionName(), 'RedirectController')
            || $this->hasRestrictedMiddleware($route)
            || ($route->getDomain() && preg_match('/\{[^}]+\}/', $route->getDomain()))
        ) {
            throw new \InvalidArgumentException('The route is not eligible for public discovery.');
        }

        $expected = $route->parameterNames();
        $provided = array_keys($parameters);
        sort($expected);
        sort($provided);

        if ($expected !== $provided) {
            throw new \InvalidArgumentException('The route parameters do not match its public route definition.');
        }

        return app(CanonicalUrl::class)->forRoute($name, $parameters);
    }

    public function publicStaticRouteUrl(string $name): string
    {
        $route = Route::getRoutes()->getByName($name);

        if (! $route || ! $this->isEligibleStaticRoute($route)) {
            throw new \InvalidArgumentException('The route is not an eligible public static route.');
        }

        return $this->publicRouteUrl($name);
    }

    public function isEligiblePublicUrl(string $url): bool
    {
        $request = Request::create($url, 'GET');

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name
                || ! Str::startsWith($name, self::PUBLIC_ROUTE_PREFIX)
                || ! in_array('GET', $route->methods(), true)
                || $this->hasExcludedPath($route->uri())
                || str_contains($route->getActionName(), 'RedirectController')
                || $this->hasRestrictedMiddleware($route)
                || ($route->getDomain() && preg_match('/\{[^}]+\}/', $route->getDomain()))
            ) {
                continue;
            }

            if ($route->matches($request)) {
                return true;
            }
        }

        return false;
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

        return ! $this->hasRestrictedMiddleware($route);
    }

    private function hasRestrictedMiddleware(RoutingRoute $route): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (preg_match('/^(auth|guest|verified|can|signed|password\.confirm)(:|$)/', $middleware)) {
                return true;
            }
        }

        return false;
    }

    private function hasExcludedPath(string $uri): bool
    {
        $excludedSegments = [
            '_design', 'admin', 'api', 'auth', 'debug', 'diagnostic', 'diagnostics', 'error',
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
