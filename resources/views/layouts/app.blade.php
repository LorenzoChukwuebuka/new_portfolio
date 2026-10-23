<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $portfolio = config('portfolio');
        $deploymentPath = base_path('DEPLOYMENT.json');
        $deployment = is_file($deploymentPath)
            ? (json_decode(file_get_contents($deploymentPath), true) ?: [])
            : [];
        $normalizeSeoText = static fn ($value) => html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $defaultTitle = $portfolio['name'].' | '.$portfolio['role'];
        $defaultDescription = $portfolio['summary'];
        $seoTitle = $normalizeSeoText($__env->yieldContent('title', $defaultTitle));
        $seoDescription = $normalizeSeoText($__env->yieldContent('meta_description', $defaultDescription));
        $seoCanonical = $normalizeSeoText($__env->yieldContent(
            'canonical',
            app(\App\Support\Seo\CanonicalUrl::class)->forPath(request()->getPathInfo()),
        ));
        $seoImage = $normalizeSeoText($__env->yieldContent(
            'og_image',
            app(\App\Support\Seo\CanonicalUrl::class)->forPath($portfolio['profile_image']),
        ));
        $seoAuthor = $normalizeSeoText($__env->yieldContent('author', $portfolio['name']));
        $ogType = $normalizeSeoText($__env->yieldContent('og_type', 'website'));
        $ogTitle = $normalizeSeoText($__env->yieldContent('og_title', $seoTitle));
        $ogDescription = $normalizeSeoText($__env->yieldContent('og_description', $seoDescription));
        $ogUrl = $normalizeSeoText($__env->yieldContent('og_url', $seoCanonical));
        $twitterCard = $normalizeSeoText($__env->yieldContent('twitter_card', 'summary_large_image'));
        $twitterTitle = $normalizeSeoText($__env->yieldContent('twitter_title', $seoTitle));
        $twitterDescription = $normalizeSeoText($__env->yieldContent('twitter_description', $seoDescription));
        $twitterImage = $normalizeSeoText($__env->yieldContent('twitter_image', $seoImage));
        $twitterUrl = $normalizeSeoText($__env->yieldContent('twitter_url', $seoCanonical));
        $seoRobots = app()->environment('production')
            ? $__env->yieldContent('robots', 'index, follow')
            : 'noindex, nofollow';
        $personSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $portfolio['name'],
            'jobTitle' => $portfolio['role'],
            'url' => app(\App\Support\Seo\CanonicalUrl::class)->forPath('/'),
            'sameAs' => array_values(array_filter($portfolio['social'])),
        ];
        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $portfolio['name'],
            'url' => app(\App\Support\Seo\CanonicalUrl::class)->forPath('/'),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
        ];
        $manifestPath = public_path('build/manifest.json');
        $fontManifest = is_file($manifestPath)
            ? (json_decode(file_get_contents($manifestPath), true) ?: [])
            : [];
        $preloadFonts = collect($fontManifest)->only([
            'node_modules/@fontsource-variable/dm-sans/files/dm-sans-latin-wght-normal.woff2',
            'node_modules/@fontsource-variable/playfair-display/files/playfair-display-latin-wght-normal.woff2',
        ]);
    @endphp

    <meta name="app-commit" content="{{ $deployment['commit'] ?? '' }}">
    <meta name="app-release" content="{{ $deployment['release'] ?? '' }}">
    <title>{{ $seoTitle }}</title>
    <link rel="icon" href="{{ asset($portfolio['profile_image']) }}" type="image/jpeg">
    @foreach ($preloadFonts as $font)
        <link rel="preload" as="font" type="font/woff2" href="{{ asset('build/'.$font['file']) }}" crossorigin>
    @endforeach

    <meta name="description" content="{{ $seoDescription }}">
    <meta name="author" content="{{ $seoAuthor }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:url" content="{{ $ogUrl }}">
    <meta property="og:site_name" content="{{ $portfolio['name'] }}">
    <meta property="og:locale" content="en_US">

    <meta name="twitter:card" content="{{ $twitterCard }}">
    <meta name="twitter:title" content="{{ $twitterTitle }}">
    <meta name="twitter:description" content="{{ $twitterDescription }}">
    <meta name="twitter:image" content="{{ $twitterImage }}">
    <meta name="twitter:url" content="{{ $twitterUrl }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <meta name="googlebot" content="{{ $seoRobots }}">

    @stack('head')
    <script type="application/ld+json">@json($personSchema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
    <script type="application/ld+json">@json($websiteSchema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
    @stack('structured-data')
    <script>document.documentElement.classList.add('js')</script>
    @vite('resources/js/site.ts')
</head>
<body class="site-body">
    <a class="skip-link" href="#main">Skip to content</a>
    <header>
        @include('partials.nav')
    </header>
    @yield('content')
    @include('partials.footer')
    @include('partials.mobile-nav')
</body>
</html>
