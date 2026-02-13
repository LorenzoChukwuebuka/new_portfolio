@extends('layouts.app')

@section('title', $project->title)

@section('meta_description', $project->description)

@section('meta_keywords', implode(', ', $project->technologies ?? []))

@section('canonical', route('projects.show', $project->slug))

{{-- OpenGraph --}}
@section('og_type', 'website')
@section('og_title', $project->title)
@section('og_description', $project->description)
@section('og_image', asset('storage/' . $project->media->firstWhere('type', 'thumbnail')?->url))
@section('og_url', route('projects.show', $project->slug))

{{-- Twitter --}}
@section('twitter_title', $project->title)
@section('twitter_description', $project->description)
@section('twitter_image', asset('storage/' . $project->media->firstWhere('type', 'thumbnail')?->url))
@section('twitter_url', route('projects.show', $project->slug))

@push('head')
    @verbatim
        <script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "CreativeWork",
    "name": "{{ $project->title }}",
    "description": "{{ $project->description }}",
    "url": "{{ route('projects.show', $project->slug) }}",
    "image": "{{ asset('storage/'.$project->media->firstWhere('type','thumbnail')?->url) }}"
}
</script>
    @endverbatim
@endpush

@section('content')
    <projectdetail-component></projectdetail-component>
@endsection
