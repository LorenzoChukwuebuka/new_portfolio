@extends('layouts.app')

@section('title', config('portfolio.name').' | '.config('portfolio.role'))
@section('meta_description', config('portfolio.summary'))
@section('og_title', config('portfolio.name').' | '.config('portfolio.role'))
@section('twitter_title', config('portfolio.name').' | '.config('portfolio.role'))

@section('content')
<main id="main">
    @include('partials.hero', ['hasActiveCv' => $hasActiveCv])

    @include('partials.skills')

    <section id="work" class="section home-work" aria-labelledby="work-title">
        <div class="container-page">
            <div class="section-heading" data-reveal>
                <p class="eyebrow">Selected work / 01</p>
                <h2 id="work-title">Projects built to hold up.</h2>
                <p class="section-heading__description">A selection of practical systems, products, and experiments shaped around clear workflows.</p>
            </div>
            <div class="project-grid project-grid--featured">
                @forelse ($projects as $project)
                    @include('partials.project-card', ['project' => $project])
                @empty
                    <p class="empty-state">New project writeups are on the way.</p>
                @endforelse
            </div>
            <div class="section-action" data-reveal>
                <a class="btn btn-secondary" href="{{ route('seo.public.projects.index') }}">Explore all projects <span aria-hidden="true">↗</span></a>
            </div>
        </div>
    </section>

    <section id="blog" class="section home-writing" aria-labelledby="writing-title">
        <div class="container-page">
            <div class="section-heading section-heading--split" data-reveal>
                <div>
                    <p class="eyebrow">Field notes / 02</p>
                    <h2 id="writing-title">Thinking made practical.</h2>
                    <p class="section-heading__description">Writeups on engineering decisions, system design, and the work behind dependable software.</p>
                </div>
                <p class="writing-count"><strong>{{ $totalPosts }}</strong><span>published {{ \Illuminate\Support\Str::plural('article', $totalPosts) }}</span></p>
            </div>
            <div class="post-grid">
                @forelse ($posts as $post)
                    @include('partials.post-card', ['post' => $post])
                @empty
                    <p class="empty-state">The first writeup is in progress.</p>
                @endforelse
            </div>
            <div class="section-action" data-reveal>
                <a class="btn btn-secondary" href="{{ route('seo.public.blog.index') }}">Browse all writing <span aria-hidden="true">↗</span></a>
            </div>
        </div>
    </section>

    @include('partials.contact')
</main>
@endsection
