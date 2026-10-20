<?php

return [
    /*
    | Canonical URL used for public metadata, structured data, robots.txt, and
    | sitemap output. Set SEO_CANONICAL_URL in production; local environments
    | fall back to APP_URL.
    */
    'canonical_url' => env('SEO_CANONICAL_URL', env('APP_URL', 'http://localhost')),
];
