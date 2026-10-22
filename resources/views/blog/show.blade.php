@extends('layouts.app')

@php
    $portfolio = config('portfolio');
    $postDescription = \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->content), 160);
    $postCanonical = app(\App\Support\Seo\CanonicalUrl::class)->forRoute('seo.public.blog.show', ['slug' => $post->slug]);
    $postMedia = $post->media->firstWhere('type', 'featured');
    $postImage = $postMedia?->url;
    if ($postImage && ! \Illuminate\Support\Str::startsWith($postImage, ['http://', 'https://', '/storage/'])) {
        $postImage = asset('storage/'.ltrim($postImage, '/'));
    }
    $postImageUrl = $postImage && \Illuminate\Support\Str::startsWith($postImage, ['http://', 'https://'])
        ? $postImage
        : app(\App\Support\Seo\CanonicalUrl::class)->forPath($postImage ?: $portfolio['profile_image']);
    $authorName = $post->author ?: $portfolio['name'];
    $publishedAt = $post->published_at;
    $shareUrl = rawurlencode($postCanonical);
    $shareTitle = rawurlencode($post->title);
@endphp

@section('title', $post->title.' | '.$portfolio['name'])
@section('meta_description', $postDescription)
@section('author', $authorName)
@section('canonical', $postCanonical)
@section('og_type', 'article')
@section('og_title', $post->title)
@section('og_description', $postDescription)
@section('og_image', $postImageUrl)
@section('og_url', $postCanonical)
@section('twitter_title', $post->title)
@section('twitter_description', $postDescription)
@section('twitter_image', $postImageUrl)
@section('twitter_url', $postCanonical)

@push('head')
    <meta property="article:published_time" content="{{ $publishedAt?->toAtomString() }}">
    @if ($post->category)<meta property="article:section" content="{{ $post->category->name }}">@endif
    <style>
        @media print {
            .site-nav, .site-footer, .skip-link, .article-progress, .article-toc, .article-share, .article-comments, .article-related, .article-neighbors { display: none !important; }
            body, .site-body { background: white !important; color: black !important; }
            .prose-article { max-width: none; color: black; }
            .prose-article a { color: black; text-decoration: underline; }
        }
    </style>
@endpush

@push('structured-data')
    @php
        $postSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $postDescription,
            'datePublished' => $publishedAt?->toAtomString(),
            'dateModified' => $post->updated_at?->toAtomString(),
            'author' => ['@type' => 'Person', 'name' => $authorName],
            'publisher' => ['@type' => 'Person', 'name' => $portfolio['name']],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $postCanonical],
            'image' => $postImageUrl,
            'wordCount' => str_word_count(strip_tags($article['html'])),
            'timeRequired' => 'PT'.$post->read_time.'M',
        ];
    @endphp
    <script type="application/ld+json">@json($postSchema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endpush

@section('content')
    <main id="main" class="article-page">
        <div class="article-progress" aria-hidden="true"><span></span></div>
        <article class="container-page article-layout">
            <nav class="breadcrumbs" aria-label="Breadcrumb">
                <a href="{{ route('seo.public.home') }}">Home</a><span aria-hidden="true">/</span><a href="{{ route('seo.public.blog.index') }}">Writing</a><span aria-hidden="true">/</span><span aria-current="page">{{ $post->title }}</span>
            </nav>

            <header class="article-header" data-reveal>
                @if ($post->category)
                    <a class="chip article-category" href="{{ route('seo.public.blog.index', ['category' => $post->category->slug]) }}">{{ $post->category->name }}</a>
                @else
                    <span class="chip article-category">Field note</span>
                @endif
                <h1>{{ $post->title }}</h1>
                @if ($post->excerpt)<p class="article-lead">{{ $post->excerpt }}</p>@endif
                <div class="article-byline">
                    <span class="article-byline__author">{{ $authorName }}</span>
                    @if ($publishedAt)<time datetime="{{ $publishedAt->toAtomString() }}">{{ $publishedAt->format('F j, Y') }}</time>@endif
                    <span>{{ $post->read_time }} min read</span>
                    <span>{{ number_format($post->views_count) }} {{ \Illuminate\Support\Str::plural('view', $post->views_count) }}</span>
                </div>
            </header>

            @if ($postImage)
                <figure class="article-hero-image" data-reveal>
                    <img src="{{ $postImage }}" alt="{{ $post->title }}" width="1200" height="675" fetchpriority="high" decoding="async" sizes="(min-width: 1280px) 80rem, 100vw">
                </figure>
            @endif

            @if (count($article['toc']))
                <details class="article-toc article-toc--mobile">
                    <summary>On this page <span aria-hidden="true">⌄</span></summary>
                    @include('blog.partials.toc-links', ['toc' => $article['toc']])
                </details>
            @endif

            <div class="article-body-layout">
                <div class="prose-article" data-reveal>
                    {!! $article['html'] !!}
                </div>
                @if (count($article['toc']))
                    <aside class="article-toc article-toc--desktop" aria-label="Table of contents">
                        <p class="eyebrow">On this page</p>
                        @include('blog.partials.toc-links', ['toc' => $article['toc']])
                    </aside>
                @endif
            </div>

            <div class="article-share" aria-label="Share this writeup">
                <span class="eyebrow">Share this writeup</span>
                <div class="article-share__links">
                    <div data-island="CopyLink" data-props="{}"><a class="btn btn-secondary" href="{{ $postCanonical }}">Copy link</a></div>
                    <a class="btn btn-secondary" href="https://twitter.com/intent/tweet?text={{ $shareTitle }}&amp;url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer">Share on X <span aria-hidden="true">↗</span></a>
                    <a class="btn btn-secondary" href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener noreferrer">Share on LinkedIn <span aria-hidden="true">↗</span></a>
                </div>
            </div>

            @if ($post->tags->isNotEmpty())
                <nav class="article-tags" aria-label="Article tags">
                    <span class="eyebrow">Filed under</span>
                    @foreach ($post->tags as $tag)
                        <a class="chip" href="{{ route('seo.public.blog.index', ['tag' => $tag->name]) }}">{{ $tag->name }}</a>
                    @endforeach
                </nav>
            @endif

            <aside class="article-author-card card">
                <div class="article-author-card__mark" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($authorName, 0, 1)) }}</div>
                <div><p class="eyebrow">Written by</p><h2>{{ $authorName }}</h2><p>{{ $portfolio['role'] }} based in {{ $portfolio['location'] }}.</p></div>
            </aside>

            @if ($previousPost || $nextPost)
                <nav class="article-neighbors" aria-label="More articles">
                    @if ($previousPost)
                        <a class="article-neighbor" href="{{ route('seo.public.blog.show', ['slug' => $previousPost->slug]) }}" rel="prev"><span>← Previous writeup</span><strong>{{ $previousPost->title }}</strong></a>
                    @else<span></span>@endif
                    @if ($nextPost)
                        <a class="article-neighbor article-neighbor--next" href="{{ route('seo.public.blog.show', ['slug' => $nextPost->slug]) }}" rel="next"><span>Next writeup →</span><strong>{{ $nextPost->title }}</strong></a>
                    @endif
                </nav>
            @endif
        </article>

        @if ($relatedPosts->isNotEmpty())
            <section class="section article-related" aria-labelledby="related-heading">
                <div class="container-page">
                    <div class="section-heading"><p class="eyebrow">Keep reading</p><h2 id="related-heading">Related writeups</h2></div>
                    <div class="post-grid">
                        @foreach ($relatedPosts as $relatedPost)
                            @include('partials.post-card', ['post' => $relatedPost])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section id="comments" class="section article-comments" aria-labelledby="comments-heading">
            <div class="container-page article-comments__layout">
                <div>
                    <p class="eyebrow">Join the conversation</p>
                    <h2 id="comments-heading">Comments <span>({{ $comments->count() }})</span></h2>
                    <p>Comments are reviewed before they appear. Keep it useful and kind.</p>
                </div>
                <div class="article-comments__list">
                    @forelse ($comments as $comment)
                        <article class="comment-card" id="comment-{{ $comment->id }}">
                            <header><strong>{{ $comment->author_name }}</strong><time datetime="{{ $comment->created_at->toAtomString() }}">{{ $comment->created_at->diffForHumans() }}</time></header>
                            <p>{!! nl2br(e($comment->body)) !!}</p>
                            <details class="comment-reply">
                                <summary>Reply</summary>
                                <div data-island="CommentForm" data-props="{{ json_encode(['postSlug' => $post->slug, 'parentId' => $comment->id], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) }}"><p class="field-hint">Reply form loading…</p></div>
                            </details>
                            @foreach ($comment->replies as $reply)
                                <article class="comment-card comment-card--reply" id="comment-{{ $reply->id }}">
                                    <header><strong>{{ $reply->author_name }}</strong><time datetime="{{ $reply->created_at->toAtomString() }}">{{ $reply->created_at->diffForHumans() }}</time></header>
                                    <p>{!! nl2br(e($reply->body)) !!}</p>
                                </article>
                            @endforeach
                        </article>
                    @empty
                        <p class="empty-state">No comments yet. Add a thoughtful first response.</p>
                    @endforelse
                    <div class="comment-card comment-card--form">
                        <h3>Leave a comment</h3>
                        <div data-island="CommentForm" data-props="{{ json_encode(['postSlug' => $post->slug], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) }}"><p class="field-hint">Comment form loading…</p></div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
