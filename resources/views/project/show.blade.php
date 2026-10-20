@extends('layouts.app')

@section('title', $project->title.' | Lorenzo Chukwuebuka Obi')

@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($project->description), 160))

@php
    $projectCanonical = app(\App\Support\Seo\CanonicalUrl::class)->forRoute('seo.public.projects.show', ['slug' => $project->slug]);
    $projectImage = $project->media->firstWhere('type', 'thumbnail')?->url;
    $projectImageUrl = $projectImage
        ? app(\App\Support\Seo\CanonicalUrl::class)->forPath('/storage/'.ltrim($projectImage, '/'))
        : app(\App\Support\Seo\CanonicalUrl::class)->forPath('/storage/images/profile.jpg');
@endphp

@section('canonical', $projectCanonical)

{{-- OpenGraph --}}
@section('og_type', 'website')
@section('og_title', $project->title)
@section('og_description', \Illuminate\Support\Str::limit(strip_tags($project->description), 160))
@section('og_image', $projectImageUrl)
@section('og_url', $projectCanonical)

{{-- Twitter --}}
@section('twitter_title', $project->title)
@section('twitter_description', \Illuminate\Support\Str::limit(strip_tags($project->description), 160))
@section('twitter_image', $projectImageUrl)
@section('twitter_url', $projectCanonical)

@push('structured-data')
    @php
        $projectSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $project->title,
            'description' => strip_tags($project->description),
            'url' => $projectCanonical,
            'creator' => [
                '@type' => 'Person',
                'name' => 'Lorenzo Chukwuebuka Obi',
            ],
        ];
        if ($projectImage) {
            $projectSchema['image'] = $projectImageUrl;
        }
    @endphp
    <script type="application/ld+json">@json($projectSchema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endpush

@section('content')
    <projectdetail-component>
        <article class="mx-auto max-w-5xl px-6 py-32 text-zinc-100">
            <nav aria-label="Breadcrumb" class="mb-8 text-sm">
                <a href="{{ route('seo.public.home') }}">Home</a>
                <span aria-hidden="true"> / </span>
                <a href="{{ route('seo.public.projects.index') }}">Projects</a>
            </nav>
            <h1 class="mb-5 text-4xl font-bold">{{ $project->title }}</h1>
            <p class="mb-8 text-lg text-zinc-400">{{ $project->description }}</p>
            @if ($project->full_description)
                <div class="whitespace-pre-line leading-8">{{ strip_tags($project->full_description) }}</div>
            @endif
        </article>
    </projectdetail-component>
@endsection
