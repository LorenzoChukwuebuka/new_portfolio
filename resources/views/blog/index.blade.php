@extends('layouts.app')

@section('title', 'The Blog | '.config('portfolio.name'))
@section('meta_description', 'Writeups by '.config('portfolio.name').' about software engineering, Laravel, Go, architecture, and development.')
@section('og_title', 'The Blog | '.config('portfolio.name'))
@section('twitter_title', 'The Blog | '.config('portfolio.name'))

@section('content')
    <main id="main" class="blog-index">
        <section class="section blog-index__intro">
            <div class="container-page">
                <p class="eyebrow" data-reveal>Notes from the work</p>
                <div class="section-heading section-heading--split" data-reveal>
                    <div>
                        <h1 class="section-heading__title">The writeups</h1>
                        <p class="section-heading__copy">Field notes on building useful software, making systems dependable, and learning in public.</p>
                    </div>
                    <p class="writing-count"><strong>{{ $totalPosts }}</strong><span>published articles</span></p>
                </div>

                <nav class="blog-categories" aria-label="Filter articles by category">
                    <a class="chip {{ $activeCategory ? '' : 'chip--active' }}" href="{{ route('seo.public.blog.index') }}">All writing <span>{{ $totalPosts }}</span></a>
                    @foreach ($categories as $category)
                        @if ($category->posts_count > 0)
                            <a class="chip {{ $activeCategory?->id === $category->id ? 'chip--active' : '' }}" href="{{ route('seo.public.blog.index', ['category' => $category->slug]) }}">
                                {{ $category->name }} <span>{{ $category->posts_count }}</span>
                            </a>
                        @endif
                    @endforeach
                </nav>
                @if ($activeCategory)
                    <p class="blog-filter-note">Showing {{ $posts->total() }} {{ \Illuminate\Support\Str::plural('article', $posts->total()) }} in <strong>{{ $activeCategory->name }}</strong>. <a href="{{ route('seo.public.blog.index') }}">Clear filter</a></p>
                @endif
            </div>
        </section>

        <section class="section blog-index__list" aria-labelledby="articles-heading">
            <div class="container-page">
                @if ($featuredPost)
                    @php
                        $featuredImage = $featuredPost->media->firstWhere('type', 'featured')?->url;
                        if ($featuredImage && ! \Illuminate\Support\Str::startsWith($featuredImage, ['http://', 'https://', '/storage/'])) {
                            $featuredImage = asset('storage/'.ltrim($featuredImage, '/'));
                        }
                    @endphp
                    <article class="blog-featured card" data-reveal>
                        <div class="blog-featured__media">
                            @if ($featuredImage)
                                <img src="{{ $featuredImage }}" alt="" width="1200" height="675" fetchpriority="high" decoding="async" sizes="(min-width: 1024px) 55vw, 100vw">
                            @else
                                <div class="project-card__media--empty" aria-hidden="true"><span>Latest writeup / {{ $featuredPost->published_at?->format('Y') }}</span></div>
                            @endif
                        </div>
                        <div class="blog-featured__content">
                            <p class="eyebrow">Latest writeup · {{ $featuredPost->category?->name ?? 'Writing' }}</p>
                            <h2>{{ $featuredPost->title }}</h2>
                            <p>{{ $featuredPost->excerpt }}</p>
                            <div class="blog-featured__meta">
                                <span>{{ $featuredPost->read_time ?: max(1, (int) ceil(str_word_count(strip_tags($featuredPost->content ?? '')) / 200)) }} min read</span>
                                <time datetime="{{ $featuredPost->published_at?->toAtomString() }}">{{ $featuredPost->published_at?->format('M j, Y') }}</time>
                            </div>
                            <a class="btn btn-primary" href="{{ route('seo.public.blog.show', ['slug' => $featuredPost->slug]) }}">Read the writeup <span aria-hidden="true">↗</span></a>
                        </div>
                    </article>
                @endif

                <div class="section-heading section-heading--compact" data-reveal>
                    <h2 id="articles-heading" class="section-heading__title">{{ $activeCategory ? 'More in '.$activeCategory->name : 'More writing' }}</h2>
                </div>
                @php($listedPosts = $featuredPost ? $posts->getCollection()->reject(fn ($item) => $item->is($featuredPost)) : $posts->getCollection())
                @if ($listedPosts->isNotEmpty())
                    <div class="post-grid blog-index__grid">
                        @foreach ($listedPosts as $post)
                            @include('partials.post-card', ['post' => $post])
                        @endforeach
                    </div>
                @elseif (! $featuredPost)
                    <div class="empty-state" role="status">
                        <h2>No articles here yet</h2>
                        <p>There are no published writeups in this view right now.</p>
                        @if ($activeCategory)<a class="btn btn-secondary" href="{{ route('seo.public.blog.index') }}">Browse all writing</a>@endif
                    </div>
                @endif

                @if ($posts->hasPages())
                    <nav class="pagination" aria-label="Article pages">
                        @if ($posts->previousPageUrl())<a class="btn btn-secondary" href="{{ $posts->previousPageUrl() }}" rel="prev">← Newer articles</a>@endif
                        <span>Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>
                        @if ($posts->nextPageUrl())<a class="btn btn-secondary" href="{{ $posts->nextPageUrl() }}" rel="next">Older articles →</a>@endif
                    </nav>
                @endif
            </div>
        </section>
    </main>
@endsection
