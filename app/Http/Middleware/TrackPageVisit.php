<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            PageVisit::create([
                'path'       => '/' . ltrim($request->path(), '/'),
                'method'     => $request->method(),
                'ip_hash'    => $request->ip() ? hash('sha256', $request->ip()) : null,
                'user_agent' => str($request->userAgent())->limit(500, '')->toString(),
                'referer'    => str($request->headers->get('referer'))->limit(500, '')->toString(),
            ]);
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        if (! $request->isMethod('GET') || ! $response->isSuccessful()) {
            return false;
        }

        return ! $request->is([
            'admin',
            'admin/*',
            'api/*',
            'build/*',
            'storage/*',
            'sanctum/*',
            'up',
            'favicon.ico',
            'robots.txt',
        ]);
    }
}
