<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Lorenzo Chukwuebuka Obi - Portfolio')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <meta name="description" content="@yield('meta_description', 'Software Engineer Portfolio')">
    <meta name="keywords" content="@yield('meta_keywords', 'Laravel, Vue, Fullstack Developer')">
    <link rel="canonical" href="@yield('canonical', url()->current())">

    {{-- OpenGraph (Facebook, LinkedIn, WhatsApp, etc.) --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', 'Lorenzo Chukwuebuka Obi - Portfolio')">
    <meta property="og:description" content="@yield('og_description', 'Software Engineer Portfolio')">
    <meta property="og:image" content="@yield('og_image', asset('default-og.jpg'))">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:site_name" content="Lorenzo Chukwuebuka Obi">
    <meta property="og:locale" content="en_US">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
    <meta name="twitter:title" content="@yield('twitter_title', 'Lorenzo Chukwuebuka Obi - Portfolio')">
    <meta name="twitter:description" content="@yield('twitter_description', 'Software Engineer Portfolio')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('default-og.jpg'))">
    <meta name="twitter:url" content="@yield('twitter_url', url()->current())">

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
