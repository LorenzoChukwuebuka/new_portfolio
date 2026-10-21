<?php

namespace App\Support\IndexNow;

use App\Support\Seo\CanonicalUrl;
use App\Support\Seo\SitemapUrlDiscovery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class IndexNowService
{
    public function key(): string
    {
        return (string) config('indexnow.key');
    }

    public function keyLocation(): string
    {
        return app(CanonicalUrl::class)->origin().'/'.$this->key().'.txt';
    }

    public function canSubmit(): bool
    {
        if (! config('indexnow.enabled') || ! app()->environment('production')) {
            return false;
        }

        if (! preg_match('/^[A-Za-z0-9-]{8,128}$/', $this->key())) {
            return false;
        }

        try {
            return str_starts_with(app(CanonicalUrl::class)->origin(), 'https://');
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Validate and normalize an absolute URL using the same canonical origin
     * used by the sitemap. Query and fragment variants are never submitted.
     */
    public function canonicalizeUrl(string $url): string
    {
        $origin = app(CanonicalUrl::class)->origin();
        $canonicalParts = parse_url($origin);
        $parts = parse_url(trim($url));

        if (! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || strtolower((string) ($parts['host'] ?? '')) !== strtolower((string) ($canonicalParts['host'] ?? ''))
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || (int) ($parts['port'] ?? 443) !== (int) ($canonicalParts['port'] ?? 443)
        ) {
            throw new \InvalidArgumentException('IndexNow URLs must use the configured canonical HTTPS host without query strings or fragments.');
        }

        $canonical = app(CanonicalUrl::class)->forPath((string) ($parts['path'] ?? '/'));

        if (! str_starts_with($canonical, 'https://')) {
            throw new \InvalidArgumentException('IndexNow requires canonical HTTPS URLs.');
        }

        if (! app(SitemapUrlDiscovery::class)->isEligiblePublicUrl($canonical)) {
            throw new \InvalidArgumentException('The URL path does not match an eligible public Laravel route.');
        }

        return $canonical;
    }

    /**
     * @param  array<int, string>  $urls
     */
    public function submit(array $urls): IndexNowSubmissionResult
    {
        if (! $this->canSubmit()) {
            return new IndexNowSubmissionResult(false, null, false, 'environment_or_configuration_disabled');
        }

        $canonicalUrls = array_values(array_unique(array_map(
            fn (string $url): string => $this->canonicalizeUrl($url),
            $urls,
        )));

        if ($canonicalUrls === []) {
            return new IndexNowSubmissionResult(true, 200);
        }

        if (count($canonicalUrls) > 10000) {
            throw new \InvalidArgumentException('IndexNow accepts at most 10,000 URLs in one request.');
        }

        $originParts = parse_url(app(CanonicalUrl::class)->origin());
        $payload = [
            'host' => $originParts['host'],
            'key' => $this->key(),
            'keyLocation' => $this->keyLocation(),
            'urlList' => $canonicalUrls,
        ];

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->connectTimeout((int) config('indexnow.connect_timeout', 2))
                ->timeout((int) config('indexnow.timeout', 5))
                ->retry(
                    max(1, (int) config('indexnow.http_retries', 2) + 1),
                    200,
                    static function (Throwable $exception): bool {
                        if ($exception instanceof ConnectionException) {
                            return true;
                        }

                        if ($exception instanceof RequestException && $exception->response) {
                            return $exception->response->serverError()
                                || $exception->response->status() === 408;
                        }

                        return false;
                    },
                    throw: false,
                )
                ->post((string) config('indexnow.endpoint'), $payload);
        } catch (Throwable) {
            return new IndexNowSubmissionResult(false, null, true, 'connection_error');
        }

        $status = $response->status();

        if (in_array($status, [200, 202], true)) {
            return new IndexNowSubmissionResult(true, $status);
        }

        $retryable = $status === 408 || $status === 429 || $status >= 500;
        $errorCode = match ($status) {
            400 => 'invalid_format',
            403 => 'invalid_key',
            422 => 'invalid_url_or_host',
            429 => 'rate_limited',
            default => $status >= 500 ? 'server_error' : 'http_error',
        };

        return new IndexNowSubmissionResult(
            false,
            $status,
            $retryable,
            $errorCode,
            $status === 429 ? $this->retryAfterSeconds($response->header('Retry-After')) : null,
        );
    }

    public function verifyKeyFile(): bool
    {
        if (! $this->canSubmit()) {
            return false;
        }

        try {
            $response = Http::connectTimeout((int) config('indexnow.connect_timeout', 2))
                ->timeout((int) config('indexnow.timeout', 5))
                ->get($this->keyLocation());

            return $response->status() === 200
                && hash_equals($this->key(), $response->body())
                && str_contains(strtolower((string) $response->header('Content-Type')), 'text/plain');
        } catch (Throwable) {
            return false;
        }
    }

    private function retryAfterSeconds(?string $header): ?int
    {
        if ($header === null || $header === '') {
            return null;
        }

        if (ctype_digit($header)) {
            return min(3600, max(1, (int) $header));
        }

        $timestamp = strtotime($header);

        return $timestamp === false ? null : min(3600, max(1, $timestamp - time()));
    }
}
