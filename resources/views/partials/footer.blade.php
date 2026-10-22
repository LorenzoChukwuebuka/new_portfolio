<footer class="site-footer">
    <div class="container-page">
        <div class="site-footer__top">
            <div>
                <a class="site-wordmark" href="{{ route('seo.public.home') }}">{{ config('portfolio.short_name') }}<span class="site-wordmark__period">.</span></a>
                <p class="site-footer__tagline">Building reliable software since {{ config('portfolio.since_year') }}.</p>
            </div>
            <div class="site-footer__social" aria-label="Social profiles">
                @foreach (config('portfolio.social') as $network => $url)
                    @if ($url)
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ ucfirst($network) }} <span aria-hidden="true">↗</span></a>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="site-footer__bottom">
            <span>© {{ now()->year }} {{ config('portfolio.name') }}</span>
            <span>{{ config('portfolio.location') }}</span>
            <a href="mailto:{{ config('portfolio.email') }}">{{ config('portfolio.email') }}</a>
        </div>
    </div>
</footer>
