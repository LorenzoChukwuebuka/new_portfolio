<?php

namespace App\Support\Seo;

use InvalidArgumentException;
use RuntimeException;

class CanonicalUrl
{
    public function forRoute(string $name, array $parameters = []): string
    {
        return $this->forPath(route($name, $parameters, false));
    }

    public function forPath(string $path): string
    {
        $canonical = $this->canonicalBase();
        $path = parse_url($path, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            $path = '/';
        }

        $path = '/'.ltrim(preg_replace('#/+#', '/', $path) ?? '/', '/');
        $path = $path === '/' ? '/' : rtrim($path, '/');

        return $canonical['origin'].$path;
    }

    public function origin(): string
    {
        return $this->canonicalBase()['origin'];
    }

    /** @return array{origin: string, host: string} */
    private function canonicalBase(): array
    {
        $configured = trim((string) config('seo.canonical_url'));
        $parts = parse_url($configured);
        $host = is_array($parts) ? strtolower((string) ($parts['host'] ?? '')) : '';

        if ($host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('SEO_CANONICAL_URL must be an absolute URL with a host.');
        }

        if (app()->environment('production') && in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true)) {
            throw new RuntimeException('SEO_CANONICAL_URL must use the production hostname.');
        }

        $scheme = app()->environment('production')
            ? 'https'
            : strtolower((string) ($parts['scheme'] ?? 'http'));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('SEO_CANONICAL_URL must use HTTP or HTTPS.');
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return [
            'origin' => $scheme.'://'.$host.$port,
            'host' => $host,
        ];
    }
}
