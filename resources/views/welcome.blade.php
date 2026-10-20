@extends('layouts.app')

@section('title', 'Lorenzo Chukwuebuka Obi | Laravel and Go Software Engineer')
@section('meta_description', 'Lorenzo Chukwuebuka Obi builds Laravel, Go, payment, dashboard, and SaaS systems for teams in Nigeria and beyond.')
@section('og_title', 'Lorenzo Chukwuebuka Obi | Laravel and Go Software Engineer')
@section('twitter_title', 'Lorenzo Chukwuebuka Obi | Laravel and Go Software Engineer')

@section('content')
    <home-component :initial-projects='@json($projects)' :initial-posts='@json($posts)'>
        <section class="mx-auto max-w-7xl px-6 py-24 text-zinc-100">
            <h1 class="mb-5 text-4xl font-bold">I build reliable software for serious teams.</h1>
            <p class="mb-10 max-w-3xl text-lg text-zinc-400">I work with founders and teams to build production-ready Laravel, Go, payment, dashboard, and SaaS systems with a focus on clarity, reliability, and long-term maintainability.</p>
            <p class="mb-10"><a href="{{ route('seo.public.projects.index') }}">Browse all projects</a> · <a href="{{ route('seo.public.blog.index') }}">Read all articles</a></p>
            <h2 class="mb-4 text-2xl font-semibold">Selected projects</h2>
            <ul class="mb-10 space-y-2">
                @forelse ($projects as $project)
                    <li><a href="{{ route('seo.public.projects.show', ['slug' => $project->slug]) }}">{{ $project->title }}</a> — {{ $project->description }}</li>
                @empty
                    <li>No projects are available yet.</li>
                @endforelse
            </ul>
            <h2 class="mb-4 text-2xl font-semibold">Latest writing</h2>
            <ul class="space-y-2">
                @forelse ($posts->take(3) as $post)
                    <li><a href="{{ route('seo.public.blog.show', ['slug' => $post->slug]) }}">{{ $post->title }}</a> — {{ $post->excerpt }}</li>
                @empty
                    <li>No published articles yet.</li>
                @endforelse
            </ul>
        </section>
    </home-component>
@endsection
