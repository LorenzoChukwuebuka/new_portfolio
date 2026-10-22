<section id="contact" class="section contact-section" aria-labelledby="contact-title">
    <div class="container-page">
        <div class="section-heading" data-reveal>
            <p class="eyebrow">Have a good problem?</p>
            <h2 id="contact-title">Let’s make the next thing reliable.</h2>
            <p class="section-heading__description">I’m open to thoughtful product work, consulting, and engineering roles. Tell me what you’re building.</p>
        </div>
        <div class="contact-section__layout">
            <div class="contact-section__details" data-reveal>
                <p class="contact-section__intro">Share a little context and I’ll get back to you as soon as I can.</p>
                <a class="contact-detail" href="mailto:{{ config('portfolio.email') }}"><span class="contact-detail__label">Email</span><span>{{ config('portfolio.email') }}</span></a>
                <a class="contact-detail" href="tel:{{ preg_replace('/[^+\d]/', '', config('portfolio.phone')) }}"><span class="contact-detail__label">Phone</span><span>{{ config('portfolio.phone') }}</span></a>
                <p class="contact-detail"><span class="contact-detail__label">Based in</span><span>{{ config('portfolio.location') }}</span></p>
                <div class="contact-section__social">
                    @foreach (config('portfolio.social') as $network => $url)
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer">{{ ucfirst($network) }} <span aria-hidden="true">↗</span></a>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="contact-section__form card" data-reveal data-reveal-delay="100">
                <div data-island="ContactForm" data-props='@json(["email" => config("portfolio.email")])'></div>
                <noscript><p>JavaScript is needed to send the form. You can email me at <a href="mailto:{{ config('portfolio.email') }}">{{ config('portfolio.email') }}</a>.</p></noscript>
            </div>
        </div>
    </div>
</section>
