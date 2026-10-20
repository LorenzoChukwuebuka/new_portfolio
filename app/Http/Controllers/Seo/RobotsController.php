<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Support\Seo\CanonicalUrl;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(CanonicalUrl $canonicalUrl): Response
    {
        if (! app()->environment('production')) {
            return response("User-agent: *\nDisallow: /\n", 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
            ]);
        }

        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /api/admin/',
            'Sitemap: '.$canonicalUrl->forPath('/sitemap.xml'),
            '',
        ]);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
