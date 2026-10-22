<?php

namespace App\Support\IndexNow;

use App\Models\Post;
use App\Models\Project;
use App\Support\Seo\SitemapUrlDiscovery;
use Illuminate\Support\Carbon;

class PublicContentIndexNowNotifier
{
    private const HOME_ROUTE = 'seo.public.home';

    private const BLOG_INDEX_ROUTE = 'seo.public.blog.index';

    private const PROJECT_INDEX_ROUTE = 'seo.public.projects.index';

    public function postBecamePublic(
        Post $post,
        ?string $previousSlug = null,
        string $source = 'post',
        bool $notifyListings = true,
    ): void {
        $discovery = app(SitemapUrlDiscovery::class);
        $this->enqueue($discovery->publicRouteUrl('seo.public.blog.show', ['slug' => $post->slug]), $source);

        if ($previousSlug !== null && $previousSlug !== $post->slug) {
            $this->enqueue($discovery->publicRouteUrl('seo.public.blog.show', ['slug' => $previousSlug]), $source, 'removal');
        }

        if ($notifyListings) {
            $this->enqueueStatic(self::BLOG_INDEX_ROUTE, $source);
            $this->enqueueStatic(self::HOME_ROUTE, $source);
        }
    }

    public function postWasRemoved(string $slug, string $source = 'post'): void
    {
        $discovery = app(SitemapUrlDiscovery::class);
        $this->enqueue($discovery->publicRouteUrl('seo.public.blog.show', ['slug' => $slug]), $source, 'removal');
        $this->enqueueStatic(self::BLOG_INDEX_ROUTE, $source);
        $this->enqueueStatic(self::HOME_ROUTE, $source);
    }

    public function postScheduled(Post $post): void
    {
        $availableAt = Carbon::parse($post->published_at);
        $discovery = app(SitemapUrlDiscovery::class);
        $subjectType = 'scheduled-post';

        $this->enqueue(
            $discovery->publicRouteUrl('seo.public.blog.show', ['slug' => $post->slug]),
            'scheduled-post',
            'change',
            $availableAt,
            $subjectType,
            $post->getKey(),
        );
        $this->enqueueStatic(self::BLOG_INDEX_ROUTE, 'scheduled-post', 'change', $availableAt, $subjectType, $post->getKey());
        $this->enqueueStatic(self::HOME_ROUTE, 'scheduled-post', 'change', $availableAt, $subjectType, $post->getKey());
    }

    public function projectChanged(
        Project $project,
        ?string $previousSlug = null,
        string $source = 'project',
        bool $notifyDetail = true,
        bool $notifyListings = true,
    ): void {
        $discovery = app(SitemapUrlDiscovery::class);

        if ($notifyDetail) {
            $this->enqueue($discovery->publicRouteUrl('seo.public.projects.show', ['slug' => $project->slug]), $source);

            if ($previousSlug !== null && $previousSlug !== $project->slug) {
                $this->enqueue($discovery->publicRouteUrl('seo.public.projects.show', ['slug' => $previousSlug]), $source, 'removal');
            }
        }

        if ($notifyListings) {
            $this->enqueueStatic(self::PROJECT_INDEX_ROUTE, $source);
            $this->enqueueStatic(self::HOME_ROUTE, $source);
        }
    }

    public function projectWasRemoved(string $slug, string $source = 'project'): void
    {
        $discovery = app(SitemapUrlDiscovery::class);
        $this->enqueue($discovery->publicRouteUrl('seo.public.projects.show', ['slug' => $slug]), $source, 'removal');
        $this->enqueueStatic(self::PROJECT_INDEX_ROUTE, $source);
        $this->enqueueStatic(self::HOME_ROUTE, $source);
    }

    public function cancelScheduledPost(int $postId): void
    {
        app(IndexNowOutbox::class)->cancelScheduledPost($postId);
    }

    private function enqueueStatic(
        string $routeName,
        string $source,
        string $kind = 'change',
        ?Carbon $availableAt = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
    ): void {
        $url = app(SitemapUrlDiscovery::class)->publicStaticRouteUrl($routeName);
        $this->enqueue($url, $source, $kind, $availableAt, $subjectType, $subjectId);
    }

    private function enqueue(
        string $url,
        string $source,
        string $kind = 'change',
        ?Carbon $availableAt = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?int $bulkRunId = null,
    ): void {
        app(IndexNowOutbox::class)->enqueue(
            $url,
            $source,
            $kind,
            $availableAt,
            $bulkRunId,
            $subjectType,
            $subjectId,
        );
    }
}
