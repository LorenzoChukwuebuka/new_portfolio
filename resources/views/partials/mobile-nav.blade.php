<div
    id="site-mobile-nav"
    class="mobile-drawer"
    role="dialog"
    aria-modal="true"
    aria-label="Site navigation"
    aria-hidden="true"
    inert
    data-open="false"
    hidden
>
    <div class="mobile-drawer__body">
        <div class="mobile-drawer__links">
            <p class="eyebrow">Navigate</p>
            @foreach (config('portfolio.navigation') as $index => $link)
                <a class="mobile-drawer__link" href="{{ $link['href'] }}" data-menu-link>
                    <span class="mobile-drawer__number">0{{ $index + 1 }}</span>{{ $link['label'] }}
                </a>
            @endforeach
        </div>
        <div class="mobile-drawer__footer">
            <a class="btn btn-primary btn-lg" href="{{ route('seo.public.projects.index') }}" data-menu-link>Browse selected work</a>
            <p class="mobile-drawer__note">{{ config('portfolio.location') }} · Available for thoughtful work</p>
        </div>
    </div>
</div>
