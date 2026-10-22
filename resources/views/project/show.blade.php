@extends('layouts.app')

@php
    $portfolio = config('portfolio');
    $projectCanonical = app(\App\Support\Seo\CanonicalUrl::class)->forRoute('seo.public.projects.show', ['slug' => $project->slug]);
    $projectMedia = $project->media->firstWhere('type', 'image') ?? $project->media->firstWhere('type', 'thumbnail');
    $projectImage = $projectMedia?->url;
    if ($projectImage && ! \Illuminate\Support\Str::startsWith($projectImage, ['http://', 'https://', '/storage/'])) {
        $projectImage = asset('storage/'.ltrim($projectImage, '/'));
    }
    $projectImageUrl = $projectImage && \Illuminate\Support\Str::startsWith($projectImage, ['http://', 'https://'])
        ? $projectImage
        : app(\App\Support\Seo\CanonicalUrl::class)->forPath($projectImage ?: $portfolio['profile_image']);
    $projectDescription = \Illuminate\Support\Str::limit(strip_tags($project->description), 160);
    $statusLabel = match ($project->status) {
        'completed' => 'Completed',
        'in-progress' => 'In progress',
        'archived' => 'Archived',
        default => \Illuminate\Support\Str::headline($project->status),
    };
    $descriptionBlocks = \App\Support\Content\ProjectDescription::blocks($project->full_description ?: $project->description);
    $technologies = collect($project->technologies ?? [])->merge($project->tags->pluck('name'))->filter()->unique()->values();
    $projectYear = $project->completed_at?->format('Y') ?? $project->created_at?->format('Y') ?? '—';
@endphp

@section('title', $project->title.' | '.$portfolio['name'])
@section('meta_description', $projectDescription)
@section('canonical', $projectCanonical)
@section('og_type', 'website')
@section('og_title', $project->title)
@section('og_description', $projectDescription)
@section('og_image', $projectImageUrl)
@section('og_url', $projectCanonical)
@section('twitter_title', $project->title)
@section('twitter_description', $projectDescription)
@section('twitter_image', $projectImageUrl)
@section('twitter_url', $projectCanonical)

@push('structured-data')
    @php
        $projectSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => $project->title,
            'description' => $projectDescription,
            'url' => $projectCanonical,
            'creator' => ['@type' => 'Person', 'name' => $portfolio['name']],
        ];
        if ($projectImage) {
            $projectSchema['image'] = $projectImageUrl;
        }
    @endphp
    <script type="application/ld+json">@json($projectSchema, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
@endpush

@section('content')
<main id="main" class="page-shell">
    <article class="container-page project-detail">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="{{ route('seo.public.home') }}">Home</a><span aria-hidden="true">/</span><a href="{{ route('seo.public.projects.index') }}">Projects</a><span aria-hidden="true">/</span><span aria-current="page">{{ $project->title }}</span>
        </nav>

        <header class="project-detail__header" data-reveal>
            <p class="project-detail__eyebrow">{{ $projectYear }} <span aria-hidden="true">·</span> Case study</p>
            <div class="project-detail__badges">
                <span class="chip project-status--{{ $project->status }}">{{ $statusLabel }}</span>
                @if ($project->is_featured)
                    <span class="chip">Featured work</span>
                @endif
            </div>
            <h1>{{ $project->title }}</h1>
            <p class="project-detail__lead">{{ $project->description }}</p>
        </header>

        @if ($projectImage)
            <figure class="project-detail__image" data-reveal>
                <img src="{{ $projectImage }}" alt="{{ $project->title }} project image" width="1200" height="675" loading="lazy" decoding="async" sizes="(min-width: 1280px) 80rem, 100vw">
            </figure>
        @endif

        <dl class="project-detail__meta" aria-label="Project details">
            <div><dt>Year</dt><dd>{{ $projectYear }}</dd></div>
            <div><dt>Status</dt><dd class="project-status--{{ $project->status }}">{{ $statusLabel }}</dd></div>
            <div><dt>Type</dt><dd>{{ $project->is_featured ? 'Featured project' : 'Selected project' }}</dd></div>
            <div><dt>Stack</dt><dd>{{ $technologies->take(2)->join(', ') ?: 'Software engineering' }}</dd></div>
        </dl>

        @if ($technologies->isNotEmpty())
            <section aria-labelledby="project-stack-title" data-reveal>
                <h2 id="project-stack-title" class="eyebrow">Tools and technologies</h2>
                <ul class="project-detail__stack">
                    @foreach ($technologies as $technology)
                        <li class="chip">{{ $technology }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="project-detail__description" aria-label="About this project" data-reveal>
            @foreach ($descriptionBlocks as $block)
                @if ($block['type'] === 'heading')
                    <h2>{{ $block['text'] }}</h2>
                @elseif ($block['type'] === 'list')
                    <ul>
                        @foreach ($block['items'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>{{ $block['text'] }}</p>
                @endif
            @endforeach
        </section>

        <div class="project-detail__actions">
            @if ($project->project_link)
                <a class="btn btn-primary" href="{{ $project->project_link }}" target="_blank" rel="noopener noreferrer">Visit project <span aria-hidden="true">↗</span></a>
            @endif
            @if ($project->github_link)
                <a class="btn btn-secondary" href="{{ $project->github_link }}" target="_blank" rel="noopener noreferrer">View source <span aria-hidden="true">↗</span></a>
            @endif
            <a class="btn btn-ghost" href="{{ route('seo.public.projects.index') }}">All projects</a>
        </div>

        @if ($previousProject || $nextProject)
            <nav class="project-detail__neighbors" aria-label="More projects">
                @if ($previousProject)
                    <a class="project-neighbor" href="{{ route('seo.public.projects.show', ['slug' => $previousProject->slug]) }}" rel="prev"><span>← Previous project</span><strong>{{ $previousProject->title }}</strong></a>
                @else
                    <span></span>
                @endif
                @if ($nextProject)
                    <a class="project-neighbor project-neighbor--next" href="{{ route('seo.public.projects.show', ['slug' => $nextProject->slug]) }}" rel="next"><span>Next project →</span><strong>{{ $nextProject->title }}</strong></a>
                @endif
            </nav>
        @endif
    </article>
</main>
@endsection
