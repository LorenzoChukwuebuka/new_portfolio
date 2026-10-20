@extends('layouts.app')

@section('title', 'Projects | Lorenzo Chukwuebuka Obi')
@section('meta_description', 'Selected software projects by Lorenzo Chukwuebuka Obi, including work with Laravel, Go, Vue, and SaaS systems.')
@section('og_title', 'Projects | Lorenzo Chukwuebuka Obi')
@section('twitter_title', 'Projects | Lorenzo Chukwuebuka Obi')

@section('content')
    <projects-component :initial-projects='@json($projects->items())'>
        <section class="mx-auto max-w-7xl px-6 py-32 text-zinc-100">
            <h1 class="mb-4 text-4xl font-bold">Projects</h1>
            <p class="mb-10 text-zinc-400">Selected software projects and the technologies used to build them.</p>
            <ul class="grid gap-8 md:grid-cols-2">
                @forelse ($projects as $project)
                    <li>
                        <article>
                            <h2 class="text-2xl font-semibold">
                                <a href="{{ route('seo.public.projects.show', ['slug' => $project->slug]) }}">{{ $project->title }}</a>
                            </h2>
                            <p class="mt-2 text-zinc-400">{{ $project->description }}</p>
                        </article>
                    </li>
                @empty
                    <li>No projects are available yet.</li>
                @endforelse
            </ul>
        </section>
    </projects-component>
@endsection
