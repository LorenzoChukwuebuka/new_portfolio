<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Software Engineer in Nigeria | Laravel & Go SaaS Developer – Lorenzo Chukwuebuka Obi')</title>

    <link rel="icon" href="{{ asset('/storage/images/profile.jpg') }}" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <meta name="description" content="@yield('meta_description', 'Experienced Software Engineer in Nigeria specializing in Laravel, Golang, SaaS architecture, multi-tenant systems, and payment integrations. Helping startups build scalable backend systems.')">
    <meta name="keywords" content="@yield(
        'meta_keywords',
        'Laravel Developer Nigeria,
            Backend Developer Nigeria,
            SaaS Developer Nigeria,
            Software Engineer in Lagos,
            Golang Developer Nigeria,
            Multi-tenant SaaS Developer,
            Payment Integration Developer Nigeria,
            Fintech Software Developer Nigeria,
            Custom Software Development Nigeria,
            Hire Laravel Developer Lagos'
    )">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- OpenGraph (Facebook, LinkedIn, WhatsApp, etc.) --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', 'Lorenzo Chukwuebuka Obi - Portfolio')">
    <meta property="og:description" content="@yield('og_description', 'Software Engineer Portfolio')">
    <meta property="og:image" content="@yield('og_image', asset('/storage/images/profile.jpg'))">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:site_name" content="Lorenzo Chukwuebuka Obi">
    <meta property="og:locale" content="en_US">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
    <meta name="twitter:title" content="@yield('twitter_title', 'Lorenzo Chukwuebuka Obi - Portfolio')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Software Engineer Portfolio')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('/storage/images/profile.jpg'))">
    <meta name="twitter:url" content="@yield('twitter_url', url()->current())">

    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">

    @stack('head')


    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
</head>

<body>
    <div id="app">
        @yield('content')
    </div>
</body>

</html>
