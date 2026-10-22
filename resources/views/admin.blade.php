<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">

    <script>
        (() => {
            const saved = localStorage.getItem('admin_theme');
            const theme = saved || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme === 'dark' ? 'dark' : 'light';
        })();
    </script>

    <title>@yield('title', 'Lorenzo Chukwuebuka Obi - Admin')</title>

    @php
        $manifestPath = public_path('build/manifest.json');
        $fontManifest = is_file($manifestPath)
            ? (json_decode(file_get_contents($manifestPath), true) ?: [])
            : [];
        $bodyFont = $fontManifest['node_modules/@fontsource-variable/dm-sans/files/dm-sans-latin-wght-normal.woff2'] ?? null;
    @endphp
    @if ($bodyFont)
        <link rel="preload" as="font" type="font/woff2" href="{{ asset('build/'.$bodyFont['file']) }}" crossorigin>
    @endif

    <!-- Styles -->
    @vite('resources/js/app.ts')
</head>

<body data-admin>
    <div id="app">
       <app></app>
    </div>
</body>

</html>
