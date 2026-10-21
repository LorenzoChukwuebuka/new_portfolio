<?php

namespace App\Observers;

use App\Models\Project;
use App\Support\IndexNow\PublicContentIndexNowNotifier;

class ProjectObserver
{
    private const MEANINGFUL_FIELDS = [
        'title', 'slug', 'description', 'full_description', 'project_link',
        'github_link', 'technologies', 'status', 'completed_at', 'order', 'is_featured',
    ];

    public function created(Project $project): void
    {
        app(PublicContentIndexNowNotifier::class)->projectChanged($project, source: 'project-created');
    }

    public function updated(Project $project): void
    {
        $changed = array_intersect(array_keys($project->getChanges()), self::MEANINGFUL_FIELDS);

        if ($changed === []) {
            return;
        }

        $old = $project->getRawOriginal();

        app(PublicContentIndexNowNotifier::class)->projectChanged(
            $project,
            $project->wasChanged('slug') ? (string) ($old['slug'] ?? '') : null,
            'project-updated',
            array_diff($changed, ['order']) !== [],
            array_diff($changed, ['full_description']) !== [],
        );
    }

    public function deleted(Project $project): void
    {
        app(PublicContentIndexNowNotifier::class)->projectWasRemoved((string) $project->slug, 'project-deleted');
    }
}
