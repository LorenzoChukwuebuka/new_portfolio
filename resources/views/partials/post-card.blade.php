@php
    $thumbnail = $post->media->firstWhere('type', 'featured');
    $thumbnailUrl = $thumbnail?->url;
    if ($thumbnailUrl && ! \Illuminate\Support\Str::startsWith($thumbnailUrl, ['http://', 'https://', '/storage/'])) {
        $thumbnailUrl = asset('storage/'.ltrim($thumbnailUrl, '/'));
    }
    $readingMinutes = $post->read_time ?: max(1, (int) ceil(str_word_count(strip_tags($post->content ?? '')) / 200));
@endphp
<article class="post-card card" data-reveal>
    @if ($thumbnailUrl)
        <div class="post-card__media"><img src="{{ $thumbnailUrl }}" alt="" width="1200" height="675" loading="lazy" decoding="async" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 46vw, 92vw"></div>
    @else
        <div class="post-card__media post-card__media--empty" aria-hidden="true"><span>Field note / {{ $post->published_at?->format('Y') }}</span></div>
    @endif
    <div class="post-card__content">
        <div class="post-card__meta"><span>{{ $post->category?->name ?? 'Writing' }}</span><span>{{ $readingMinutes }} min read</span></div>
        <h3>{{ $post->title }}</h3>
        <p>{{ $post->excerpt }}</p>
        <div class="post-card__footer"><time datetime="{{ $post->published_at?->toAtomString() }}">{{ $post->published_at?->format('M j, Y') }}</time><span>Read writeup <span aria-hidden="true">↗</span></span></div>
    </div>
    <a class="post-card__link" href="{{ route('seo.public.blog.show', ['slug' => $post->slug]) }}" aria-label="Read {{ $post->title }}"></a>
</article>
