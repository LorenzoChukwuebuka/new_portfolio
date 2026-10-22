<nav class="site-nav" aria-label="Primary navigation">
    <div class="container-page site-nav__bar">
        <a class="site-wordmark" href="{{ route('seo.public.home') }}" aria-label="{{ config('portfolio.name') }} home">
            <span>{{ config('portfolio.short_name') }}</span><span class="site-wordmark__period">.</span>
        </a>

        <div class="site-nav__links" aria-label="Main sections">
            @foreach (config('portfolio.navigation') as $link)
                <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
            @endforeach
        </div>

        <a class="btn btn-primary site-nav__hire" href="/#contact">Start a conversation</a>
        <button
            class="site-nav__toggle"
            type="button"
            aria-label="Open menu"
            aria-expanded="false"
            aria-controls="site-mobile-nav"
            data-menu-toggle
        >
            <span data-menu-toggle-label aria-hidden="true">Menu</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
        </button>
    </div>
</nav>
