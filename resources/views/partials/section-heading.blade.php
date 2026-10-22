<div class="section-heading" data-reveal>
    @if (!empty($eyebrow))
        <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    <h2>{{ $title }}</h2>
    @if (!empty($description))
        <p class="section-heading__description">{{ $description }}</p>
    @endif
</div>
