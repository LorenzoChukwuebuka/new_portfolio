<section class="hero section" aria-labelledby="hero-title">
    <div class="hero__texture" aria-hidden="true"></div>
    <div class="container-page hero__layout">
        <div class="hero__copy">
            <p class="hero__availability"><span aria-hidden="true"></span> Available for new opportunities</p>
            <p class="eyebrow">{{ config('portfolio.role') }}</p>
            <h1 id="hero-title">{{ config('portfolio.tagline') }}</h1>
            <p class="hero__summary">{{ config('portfolio.summary') }}</p>
            <p class="hero__stack"><span aria-hidden="true">// </span> Laravel <i>·</i> Go <i>·</i> Vue <i>·</i> Payments <i>·</i> SaaS</p>
            <div class="hero__actions">
                <a class="btn btn-primary btn-lg" href="#work">Explore selected work <span aria-hidden="true">↘</span></a>
                <a class="btn btn-secondary btn-lg" href="#contact">Get in touch</a>
                @if ($hasActiveCv)
                    <a class="btn btn-ghost btn-lg" href="{{ route('cv.download.active') }}">Download CV <span aria-hidden="true">↓</span></a>
                @endif
            </div>
        </div>
        <figure class="hero__portrait">
            <div class="hero__portrait-frame">
                <img
                    src="{{ asset(config('portfolio.profile_image')) }}"
                    alt="Portrait of {{ config('portfolio.name') }}"
                    width="720"
                    height="900"
                    sizes="(min-width: 1100px) 420px, (min-width: 700px) 38vw, 86vw"
                    fetchpriority="high"
                    decoding="async"
                >
            </div>
            <figcaption><span>{{ config('portfolio.name') }}</span><span>{{ config('portfolio.location') }}</span></figcaption>
        </figure>
    </div>
    <div class="hero__index container-page" aria-hidden="true"><span>01 / 04</span><span>Scroll to explore</span></div>
</section>
