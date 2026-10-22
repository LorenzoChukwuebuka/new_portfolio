<?php

namespace App\Observers;

use App\Models\Post;
use App\Support\IndexNow\PublicContentIndexNowNotifier;
use Illuminate\Support\Carbon;

class PostObserver
{
    private const MEANINGFUL_FIELDS = [
        'title', 'slug', 'excerpt', 'content', 'author', 'read_time',
        'status', 'published_at', 'category_id', 'meta_data',
    ];

    private const LISTING_FIELDS = [
        'title', 'slug', 'excerpt', 'author', 'read_time', 'published_at', 'category_id',
    ];

    public function created(Post $post): void
    {
        if ($this->isPublic($post->getAttributes())) {
            app(PublicContentIndexNowNotifier::class)->postBecamePublic($post, source: 'post-created');
        } elseif ($this->isScheduled($post->getAttributes())) {
            app(PublicContentIndexNowNotifier::class)->postScheduled($post);
        }
    }

    public function updated(Post $post): void
    {
        $old = $post->getRawOriginal();
        $current = $post->getAttributes();
        $wasPublic = $this->isPublic($old);
        $isPublic = $this->isPublic($current);
        $wasScheduled = $this->isScheduled($old);
        $isScheduled = $this->isScheduled($current);
        $changed = array_intersect(array_keys($post->getChanges()), self::MEANINGFUL_FIELDS);
        $notifier = app(PublicContentIndexNowNotifier::class);

        if ($wasScheduled || $isScheduled) {
            $notifier->cancelScheduledPost($post->getKey());
        }

        if ($wasPublic && ! $isPublic) {
            $notifier->postWasRemoved((string) ($old['slug'] ?? $post->slug), 'post-unpublished');

            if ($isScheduled) {
                $notifier->postScheduled($post);
            }

            return;
        }

        if (! $wasPublic && $isPublic) {
            $notifier->postBecamePublic($post, source: 'post-published');

            return;
        }

        if ($isPublic && $changed !== []) {
            $notifier->postBecamePublic(
                $post,
                in_array('slug', $changed, true) ? (string) ($old['slug'] ?? '') : null,
                'post-updated',
                array_intersect($changed, self::LISTING_FIELDS) !== [],
            );

            return;
        }

        if (! $isPublic && $isScheduled) {
            $notifier->postScheduled($post);
        }
    }

    public function deleted(Post $post): void
    {
        $notifier = app(PublicContentIndexNowNotifier::class);
        $notifier->cancelScheduledPost($post->getKey());

        // A force delete of an already soft-deleted post was not a new public
        // removal; its URL was notified when it was first soft-deleted.
        if ($post->isForceDeleting() && ! empty($post->getRawOriginal('deleted_at'))) {
            return;
        }

        if ($this->isPublic($post->getAttributes(), ignoreDeletedAt: true)) {
            $notifier->postWasRemoved((string) $post->slug, 'post-deleted');
        }
    }

    /** @param array<string, mixed> $attributes */
    private function isPublic(array $attributes, bool $ignoreDeletedAt = false): bool
    {
        if (($attributes['status'] ?? null) !== 'published'
            || (! $ignoreDeletedAt && ! empty($attributes['deleted_at']))
            || empty($attributes['published_at'])
        ) {
            return false;
        }

        return Carbon::parse($attributes['published_at'])->lte(now());
    }

    /** @param array<string, mixed> $attributes */
    private function isScheduled(array $attributes): bool
    {
        return ($attributes['status'] ?? null) === 'published'
            && empty($attributes['deleted_at'])
            && ! empty($attributes['published_at'])
            && Carbon::parse($attributes['published_at'])->gt(now());
    }
}
