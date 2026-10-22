<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $portfolio = config('portfolio');
        $defaultTitle = $portfolio['name'].' | '.$portfolio['role'];
        $defaultDescription = $portfolio['summary'];
        $seoTitle = $__env->yieldContent('title', $defaultTitle);
        $seoDescription = $__env->yieldContent('meta_description', $defaultDescription);
        $seoCanonical = $__env->yieldContent(
            'canonical',
            app(\App\Support\Seo\CanonicalUrl::class)->forPath(request()->getPathInfo()),
        );
        $seoImage = $__env->yieldContent(
            'og_image',
            app(\App\Support\Seo\CanonicalUrl::class)->forPath($portfolio['profile_image']),
        );
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

    <title>{{ $seoTitle }}</title>
    <link rel="icon" href="{{ asset($portfolio['profile_image']) }}" type="image/jpeg">
    @foreach ($preloadFonts as $font)
        <link rel="preload" as="font" type="font/woff2" href="{{ asset('build/'.$font['file']) }}" crossorigin>
    @endforeach

    <meta name="description" content="{{ $seoDescription }}">
    <meta name="author" content="{{ $__env->yieldContent('author', $portfolio['name']) }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:type" content="{{ $__env->yieldContent('og_type', 'website') }}">
    <meta property="og:title" content="{{ $__env->yieldContent('og_title', $seoTitle) }}">
    <meta property="og:description" content="{{ $__env->yieldContent('og_description', $seoDescription) }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:url" content="{{ $__env->yieldContent('og_url', $seoCanonical) }}">
    <meta property="og:site_name" content="{{ $portfolio['name'] }}">
    <meta property="og:locale" content="en_US">

    <meta name="twitter:card" content="{{ $__env->yieldContent('twitter_card', 'summary_large_image') }}">
    <meta name="twitter:title" content="{{ $__env->yieldContent('twitter_title', $seoTitle) }}">
    <meta name="twitter:description" content="{{ $__env->yieldContent('twitter_description', $seoDescription) }}">
    <meta name="twitter:image" content="{{ $__env->yieldContent('twitter_image', $seoImage) }}">
    <meta name="twitter:url" content="{{ $__env->yieldContent('twitter_url', $seoCanonical) }}">
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
</body>
</html>
