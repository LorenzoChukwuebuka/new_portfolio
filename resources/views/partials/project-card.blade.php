@php
    $thumbnail = $project->media->firstWhere('type', 'thumbnail');
    $thumbnailUrl = $thumbnail?->url;
    if ($thumbnailUrl && ! \Illuminate\Support\Str::startsWith($thumbnailUrl, ['http://', 'https://', '/storage/'])) {
        $thumbnailUrl = asset('storage/'.ltrim($thumbnailUrl, '/'));
    }
    $statusLabel = match ($project->status) {
        'completed' => 'Completed',
        'in-progress' => 'In progress',
        'archived' => 'Archived',
        default => \Illuminate\Support\Str::headline($project->status),
    };
@endphp
<article class="project-card card" data-reveal>
    @if ($thumbnailUrl)
        <div class="project-card__media">
            <img src="{{ $thumbnailUrl }}" alt="" width="1200" height="675" loading="lazy" decoding="async" sizes="(min-width: 1024px) 38vw, (min-width: 640px) 48vw, 92vw">
        </div>
    @else
        <div class="project-card__media project-card__media--empty" aria-hidden="true"><span>{{ str_pad((string) $project->order, 2, '0', STR_PAD_LEFT) }}</span></div>
    @endif
    <div class="project-card__content">
        <div class="project-card__meta"><span>{{ $project->completed_at?->format('Y') ?? $project->created_at?->format('Y') }}</span><span class="project-status project-status--{{ $project->status }}">{{ $statusLabel }}</span></div>
        <h3>{{ $project->title }}</h3>
        <p>{{ $project->description }}</p>
        <ul class="project-card__tags" aria-label="Technologies">
            @foreach (array_slice($project->technologies ?? [], 0, 4) as $technology)
                <li>{{ $technology }}</li>
            @endforeach
        </ul>
        <span class="project-card__read">View case study <span aria-hidden="true">↗</span></span>
    </div>
    <a class="project-card__link" href="{{ route('seo.public.projects.show', ['slug' => $project->slug]) }}" aria-label="View {{ $project->title }} case study"></a>
</article>
