@extends('layouts.app')


@section('title', $post->title)

@section('meta_description', $post->excerpt)

@section('meta_keywords', $post->tags->pluck('name')->join(', '))

@section('canonical', route('blog.show', $post->slug))

{{-- OpenGraph --}}
@section('og_type', 'article')
@section('og_title', $post->title)
@section('og_description', $post->excerpt)
@section('og_image', asset('/storage/' . $post->media->first()?->url))
@section('og_url', route('blog.show', $post->slug))

{{-- Twitter --}}
@section('twitter_title', $post->title)
@section('twitter_description', $post->excerpt)
@section('twitter_image', asset('/storage/' . $post->media->first()?->url))
@section('twitter_url', route('blog.show', $post->slug))

@section('content')
    <blogpost-component></blogpost-component>
@endsection
