@extends('layouts.app')


@section('title', $post->title.' | Lorenzo Chukwuebuka Obi')

@php
    $postDescription = \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 160);
    $postCanonical = app(\App\Support\Seo\CanonicalUrl::class)->forRoute('seo.public.blog.show', ['slug' => $post->slug]);
    $postImage = $post->media->firstWhere('type', 'featured')?->url;
    $postImageUrl = $postImage
        ? app(\App\Support\Seo\CanonicalUrl::class)->forPath('/storage/'.ltrim($postImage, '/'))
        : app(\App\Support\Seo\CanonicalUrl::class)->forPath('/storage/images/profile.jpg');
@endphp

@section('meta_description', $postDescription)
@section('author', $post->author ?: 'Lorenzo Chukwuebuka Obi')
@section('canonical', $postCanonical)

{{-- OpenGraph --}}
@section('og_type', 'article')
@section('og_title', $post->title)
@section('og_description', $postDescription)
@section('og_image', $postImageUrl)
@section('og_url', $postCanonical)

{{-- Twitter --}}
@section('twitter_title', $post->title)
@section('twitter_description', $postDescription)
@section('twitter_image', $postImageUrl)
@section('twitter_url', $postCanonical)

@push('structured-data')
    @php
        $postSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->title,
            'description' => $postDescription,
            'datePublished' => $post->published_at?->toAtomString(),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author ?: 'Lorenzo Chukwuebuka Obi',
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $postCanonical,
            ],
            'image' => $postImageUrl,
        ];
    @endphp
    <script type="application/ld+json">@json($postSchema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endpush

@section('content')
    <blogpost-component>
        <article class="mx-auto max-w-3xl px-6 py-32 text-zinc-100">
            <nav aria-label="Breadcrumb" class="mb-8 text-sm">
                <a href="{{ route('seo.public.home') }}">Home</a>
                <span aria-hidden="true"> / </span>
                <a href="{{ route('seo.public.blog.index') }}">Blog</a>
            </nav>
            <header class="mb-10">
                <h1 class="mb-5 text-4xl font-bold">{{ $post->title }}</h1>
                <p class="text-lg text-zinc-400">{{ strip_tags($post->excerpt) }}</p>
                <p class="mt-4 text-sm text-zinc-500">
                    <span>{{ $post->author ?: 'Lorenzo Chukwuebuka Obi' }}</span>
                    · <time datetime="{{ $post->published_at->toAtomString() }}">{{ $post->published_at->toFormattedDateString() }}</time>
                </p>
            </header>
            <div class="whitespace-pre-line leading-8">{{ strip_tags($post->content) }}</div>
        </article>
    </blogpost-component>
@endsection
