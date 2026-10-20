@extends('layouts.app')

@section('title', 'Blog | Lorenzo Chukwuebuka Obi')
@section('meta_description', 'Articles by Lorenzo Chukwuebuka Obi about software engineering, Laravel, Go, architecture, and development.')
@section('og_title', 'Blog | Lorenzo Chukwuebuka Obi')
@section('twitter_title', 'Blog | Lorenzo Chukwuebuka Obi')

@section('content')
    <blog-component :initial-posts='@json($posts->items())'>
        <section class="mx-auto max-w-7xl px-6 py-32 text-zinc-100">
            <h1 class="mb-4 text-4xl font-bold">The Blog</h1>
            <p class="mb-10 text-zinc-400">Articles about software engineering, Laravel, Go, and system design.</p>
            <ul class="space-y-6">
                @forelse ($posts as $post)
                    <li>
                        <article>
                            <h2 class="text-2xl font-semibold">
                                <a href="{{ route('seo.public.blog.show', ['slug' => $post->slug]) }}">{{ $post->title }}</a>
                            </h2>
                            <p class="mt-2 text-zinc-400">{{ $post->excerpt }}</p>
                        </article>
                    </li>
                @empty
                    <li>No published posts yet.</li>
                @endforelse
            </ul>
        </section>
    </blog-component>
@endsection
