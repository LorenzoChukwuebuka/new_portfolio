@extends('layouts.app')

@section('title', 'Projects | '.config('portfolio.name'))
@section('meta_description', 'Selected software projects by '.config('portfolio.name').', including Laravel, Go, Vue, payments, dashboards, and SaaS systems.')
@section('og_title', 'Projects | '.config('portfolio.name'))
@section('twitter_title', 'Projects | '.config('portfolio.name'))

@section('content')
<main id="main" class="page-shell">
    <div class="container-page">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="{{ route('seo.public.home') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">Projects</span>
        </nav>
        <header class="page-intro" data-reveal>
            <p class="eyebrow">Selected work / 01</p>
            <h1>Projects built to hold up.</h1>
            <p>A collection of practical products and systems shaped around useful workflows, reliable behavior, and thoughtful interfaces.</p>
            <span class="page-intro__count">{{ $projects->total() }} {{ \Illuminate\Support\Str::plural('project', $projects->total()) }}</span>
        </header>

        <section class="project-filters" aria-label="Filter projects">
            <div class="project-filters__group">
                <span class="project-filters__label">Status</span>
                <a class="filter-chip {{ !$status ? 'is-active' : '' }}" href="{{ route('seo.public.projects.index', array_filter(['tech' => $technology])) }}">All</a>
                @foreach ($statuses as $projectStatus)
                    <a class="filter-chip {{ $status === $projectStatus ? 'is-active' : '' }}" href="{{ route('seo.public.projects.index', array_filter(['status' => $projectStatus, 'tech' => $technology])) }}">
                        {{ match ($projectStatus) { 'completed' => 'Completed', 'in-progress' => 'In progress', default => 'Archived' } }}
                    </a>
                @endforeach
            </div>
            @if ($technologies->isNotEmpty())
                <div class="project-filters__group">
                    <span class="project-filters__label">Technology</span>
                    <a class="filter-chip {{ !$technology ? 'is-active' : '' }}" href="{{ route('seo.public.projects.index', array_filter(['status' => $status])) }}">All</a>
                    @foreach ($technologies as $item)
                        <a class="filter-chip {{ $technology === $item ? 'is-active' : '' }}" href="{{ route('seo.public.projects.index', array_filter(['status' => $status, 'tech' => $item])) }}">{{ $item }}</a>
                    @endforeach
                </div>
            @endif
        </section>

        @if ($projects->count())
            <div class="project-grid project-grid--all">
                @foreach ($projects as $project)
                    @include('partials.project-card', ['project' => $project])
                @endforeach
            </div>
            @if ($projects->hasPages())
                <nav class="page-pagination" aria-label="Project pages">
                    @if ($projects->previousPageUrl())
                        <a class="btn btn-secondary" href="{{ $projects->previousPageUrl() }}" rel="prev">← Previous</a>
                    @else
                        <span></span>
                    @endif
                    <span class="page-pagination__status">Page {{ $projects->currentPage() }} of {{ $projects->lastPage() }}</span>
                    @if ($projects->nextPageUrl())
                        <a class="btn btn-secondary" href="{{ $projects->nextPageUrl() }}" rel="next">Next →</a>
                    @else
                        <span></span>
                    @endif
                </nav>
            @endif
        @else
            <div class="empty-state empty-state--large" role="status">
                <p class="eyebrow">No matching projects</p>
                <h2>Try a different filter.</h2>
                <a class="btn btn-secondary" href="{{ route('seo.public.projects.index') }}">Clear filters</a>
            </div>
        @endif
    </div>
</main>
@endsection
