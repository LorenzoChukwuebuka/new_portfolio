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
            aria-label="Open navigation menu"
            aria-expanded="false"
            aria-controls="site-mobile-nav"
            data-menu-toggle
        >
            <span aria-hidden="true">Menu</span>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" /></svg>
        </button>
    </div>

    <div id="site-mobile-nav" class="mobile-drawer" role="dialog" aria-modal="true" aria-label="Site navigation" hidden>
        <div class="mobile-drawer__head">
            <a class="site-wordmark" href="{{ route('seo.public.home') }}">{{ config('portfolio.short_name') }}<span class="site-wordmark__period">.</span></a>
            <button class="btn btn-ghost" type="button" data-menu-close aria-label="Close navigation menu">Close <span aria-hidden="true">×</span></button>
        </div>
        <div class="mobile-drawer__body">
            <p class="eyebrow">Navigate</p>
            @foreach (config('portfolio.navigation') as $index => $link)
                <a class="mobile-drawer__link" href="{{ $link['href'] }}" data-menu-link>
                    <span class="mobile-drawer__number">0{{ $index + 1 }}</span>{{ $link['label'] }}
                </a>
            @endforeach
            <a class="btn btn-primary btn-lg" href="{{ route('seo.public.projects.index') }}" data-menu-link>Browse selected work</a>
        </div>
        <p class="mobile-drawer__note">{{ config('portfolio.location') }} · Available for thoughtful work</p>
    </div>
</nav>
