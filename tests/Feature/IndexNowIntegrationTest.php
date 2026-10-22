<?php

use App\Models\IndexNowBulkRun;
use App\Models\IndexNowNotification;
use App\Models\PageVisit;
use App\Models\Post;
use App\Models\Project;
use App\Support\IndexNow\IndexNowOutboxDispatcher;
use App\Support\IndexNow\IndexNowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'seo.canonical_url' => 'https://canonical.example',
        'indexnow.key' => 'test-indexnow-key-1234567890abcdef',
        'indexnow.request_batch_size' => 100,
    ]);
    app()->detectEnvironment(fn () => 'production');

    Http::preventStrayRequests();
});

function publishedPost(array $overrides = []): Post
{
    return Post::query()->create(array_merge([
        'title' => 'Public article',
        'slug' => 'public-article',
        'excerpt' => 'A public article excerpt.',
        'content' => 'Public article content.',
        'author' => 'Lorenzo Obi',
        'read_time' => 4,
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ], $overrides));
}

function postNotificationUrls(): array
{
    return IndexNowNotification::query()->pluck('url')->all();
}

test('the IndexNow key URL returns the exact configured plain text key', function () {
    $key = config('indexnow.key');

    $this->get('/'.$key.'.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertContent($key);
    expect(PageVisit::count())->toBe(0);

    $this->get('/'.str_repeat('a', 32).'.txt')->assertNotFound();
});

test('production release command writes the exact configured key as a public text file', function () {
    $key = config('indexnow.key');
    $publicDirectory = storage_path('framework/testing/indexnow-public-'.uniqid('', true));
    mkdir($publicDirectory, 0777, true);
    $originalPublicDirectory = app()->publicPath();
    app()->usePublicPath($publicDirectory);

    try {
        $this->artisan('indexnow:publish-key-file')
            ->expectsOutputToContain('Published IndexNow verification file')
            ->assertSuccessful();

        $path = $publicDirectory.'/'.$key.'.txt';
        expect(file_get_contents($path))->toBe($key)
            ->and(fileperms($path) & 0777)->toBe(0644);
    } finally {
        @unlink($publicDirectory.'/'.$key.'.txt');
        @rmdir($publicDirectory);
        app()->usePublicPath($originalPublicDirectory);
    }
});

test('IndexNow URLs are canonical HTTPS URLs on eligible public routes only', function () {
    $service = app(IndexNowService::class);

    expect($service->canonicalizeUrl('https://canonical.example/blog/example-post/'))
        ->toBe('https://canonical.example/blog/example-post');

    expect(fn () => $service->canonicalizeUrl('https://elsewhere.example/blog/example-post'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->canonicalizeUrl('http://canonical.example/blog/example-post'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->canonicalizeUrl('https://canonical.example/admin'))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->canonicalizeUrl('https://canonical.example/blog/example-post?preview=1'))
        ->toThrow(InvalidArgumentException::class);
});

test('drafts are ignored and publishing a post queues its page and affected public listings', function () {
    $post = Post::query()->create([
        'title' => 'Draft article',
        'slug' => 'draft-article',
        'excerpt' => 'Draft excerpt.',
        'content' => 'Draft body.',
        'status' => 'draft',
    ]);

    expect(IndexNowNotification::count())->toBe(0);

    $post->update(['status' => 'published', 'published_at' => now()->subMinute()]);

    expect(postNotificationUrls())->toContain(
        'https://canonical.example/blog/draft-article',
        'https://canonical.example/blog',
        'https://canonical.example/',
    );
});

test('published post updates notify the current URL and slug changes notify both URL versions', function () {
    $post = publishedPost();
    $post->update(['content' => 'A meaningful public update.']);

    expect(IndexNowNotification::query()->where('url', 'https://canonical.example/blog/public-article')->count())->toBe(1);
    expect(IndexNowNotification::query()->where('url', 'https://canonical.example/blog/public-article')->value('source'))
        ->toBe('post-updated');
    expect(IndexNowNotification::query()->where('url', 'https://canonical.example/blog')->value('source'))
        ->toBe('post-created');

    $post->update(['slug' => 'renamed-article']);

    expect(postNotificationUrls())->toContain(
        'https://canonical.example/blog/public-article',
        'https://canonical.example/blog/renamed-article',
    );
    expect(IndexNowNotification::query()->where('url', 'https://canonical.example/blog/public-article')->value('kind'))
        ->toBe('removal');
});

test('unpublishing and soft deletion notify the former post URL while restoration republishes it', function () {
    $post = publishedPost();
    $post->delete();

    $removal = IndexNowNotification::query()
        ->where('url', 'https://canonical.example/blog/public-article')
        ->firstOrFail();
    expect($removal->kind)->toBe('removal');

    $this->travel(2)->minutes();
    $post->restore();

    $restored = IndexNowNotification::query()
        ->where('url', 'https://canonical.example/blog/public-article')
        ->orderByDesc('id')
        ->firstOrFail();
    expect($restored->kind)->toBe('change');
});

test('unpublishing, hard deletion, and restoration follow the actual post lifecycle', function () {
    $post = publishedPost();
    $post->update(['status' => 'draft']);

    expect(IndexNowNotification::query()
        ->where('url', 'https://canonical.example/blog/public-article')
        ->value('kind'))->toBe('removal');

    $this->travel(2)->minutes();
    $post->update(['status' => 'published', 'published_at' => now()->subMinute()]);
    $this->travel(2)->minutes();
    $post->forceDelete();

    expect(IndexNowNotification::query()
        ->where('url', 'https://canonical.example/blog/public-article')
        ->where('kind', 'removal')->exists())->toBeTrue();
});

test('scheduled posts wait until public and are cancelled if unpublished before their date', function () {
    $post = publishedPost([
        'slug' => 'scheduled-article',
        'published_at' => now()->addDay(),
    ]);

    expect(IndexNowNotification::query()->where('subject_type', 'scheduled-post')->count())->toBe(3);
    expect(IndexNowNotification::query()->where('available_at', '<=', now())->count())->toBe(0);

    app()->detectEnvironment(fn () => 'production');
    Http::fake(['https://api.indexnow.org/indexnow' => Http::response('', 202)]);
    expect(app(IndexNowOutboxDispatcher::class)->dispatchDue())->toBe(0);

    $post->update(['status' => 'draft']);
    expect(IndexNowNotification::query()->where('subject_type', 'scheduled-post')->where('status', 'cancelled')->count())
        ->toBe(3);
    Http::assertNothingSent();
});

test('rescheduling a public post keeps its immediate removal separate from its future publication', function () {
    $post = publishedPost([
        'slug' => 'rescheduled-article',
    ]);

    $post->update(['published_at' => now()->addSeconds(30)]);

    $sameUrlNotifications = IndexNowNotification::query()
        ->where('url', 'https://canonical.example/blog/rescheduled-article')
        ->orderBy('id')
        ->get();

    expect($sameUrlNotifications)->toHaveCount(2);
    expect($sameUrlNotifications[0]->kind)->toBe('removal');
    expect($sameUrlNotifications[0]->available_at->lte(now()))->toBeTrue();
    expect($sameUrlNotifications[1]->subject_type)->toBe('scheduled-post');
    expect($sameUrlNotifications[1]->available_at->isFuture())->toBeTrue();
});

test('a scheduled post is sent after its publication time arrives', function () {
    $post = publishedPost([
        'slug' => 'scheduled-article',
        'published_at' => now()->addDay(),
    ]);

    app()->detectEnvironment(fn () => 'production');
    Http::fake(['https://api.indexnow.org/indexnow' => Http::response('', 202)]);
    expect(app(IndexNowOutboxDispatcher::class)->dispatchDue())->toBe(0);

    $this->travel(2)->days();
    expect(app(IndexNowOutboxDispatcher::class)->dispatchDue(10))->toBe(3);
    expect(IndexNowNotification::query()->where('status', 'submitted')->count())->toBe(3);
    Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://api.indexnow.org/indexnow'
        && in_array('https://canonical.example/blog/scheduled-article', $request['urlList'], true));
});

test('all current project statuses are public and project changes, slug changes, and deletion are reported', function () {
    $project = Project::query()->create([
        'title' => 'Archived public project',
        'slug' => 'archived-public-project',
        'description' => 'Still public under the existing route policy.',
        'project_link' => 'https://example.test/project',
        'technologies' => ['Laravel'],
        'status' => 'archived',
    ]);

    expect(postNotificationUrls())->toContain('https://canonical.example/projects/archived-public-project');

    $project->update(['slug' => 'renamed-project', 'description' => 'Updated description.']);
    expect(postNotificationUrls())->toContain(
        'https://canonical.example/projects/archived-public-project',
        'https://canonical.example/projects/renamed-project',
        'https://canonical.example/projects',
    );

    $project->delete();
    expect(IndexNowNotification::query()
        ->where('url', 'https://canonical.example/projects/renamed-project')
        ->value('kind'))->toBe('removal');
});

test('model events inside rolled back transactions do not leave notifications', function () {
    try {
        DB::transaction(function (): void {
            publishedPost(['slug' => 'rolled-back-post']);

            throw new RuntimeException('Rollback this content save.');
        });
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Rollback this content save.');
    }

    expect(Post::query()->where('slug', 'rolled-back-post')->exists())->toBeFalse();
    expect(IndexNowNotification::count())->toBe(0);
});

test('duplicate model events coalesce per URL and view counters do not notify', function () {
    $post = publishedPost();
    $post->update(['title' => 'Changed once.']);
    $post->update(['title' => 'Changed twice in the same minute.']);
    $post->incrementViews();

    expect(IndexNowNotification::query()
        ->where('url', 'https://canonical.example/blog/public-article')
        ->count())->toBe(1);
});

test('projects that receive bulk reordering through Eloquent updates get notifications', function () {
    $project = Project::query()->create([
        'title' => 'Sortable project',
        'slug' => 'sortable-project',
        'description' => 'Public description.',
        'project_link' => 'https://example.test/project',
        'technologies' => ['Laravel'],
        'status' => 'in-progress',
    ]);

    $project->update(['order' => 5]);

    expect(IndexNowNotification::query()->where('url', 'https://canonical.example/projects')->count())->toBe(1);
    expect(IndexNowNotification::query()
        ->where('url', 'https://canonical.example/projects/sortable-project')
        ->value('source'))->toBe('project-created');
});

test('the HTTP client accepts 200 and 202 and sends the official JSON payload', function () {
    app()->detectEnvironment(fn () => 'production');
    Http::fakeSequence()
        ->push('', 200)
        ->push('', 202);

    $service = app(IndexNowService::class);
    $first = $service->submit(['https://canonical.example/blog/one']);
    $second = $service->submit(['https://canonical.example/projects/two']);

    expect($first->accepted)->toBeTrue();
    expect($first->statusCode)->toBe(200);
    expect($second->accepted)->toBeTrue();
    expect($second->statusCode)->toBe(202);

    Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://api.indexnow.org/indexnow'
        && $request['host'] === 'canonical.example'
        && $request['key'] === config('indexnow.key')
        && $request['keyLocation'] === 'https://canonical.example/'.config('indexnow.key').'.txt'
        && count($request['urlList']) === 1);
});

test('HTTP transient failures retry with a bounded attempt count and permanent errors are classified', function () {
    app()->detectEnvironment(fn () => 'production');
    Http::fakeSequence()
        ->push('', 503)
        ->push('', 503)
        ->push('', 200)
        ->push('', 403)
        ->push('', 429, ['Retry-After' => '30'])
        ->push('', 422);

    $success = app(IndexNowService::class)->submit(['https://canonical.example/blog/retry']);
    expect($success->accepted)->toBeTrue();
    Http::assertSentCount(3);

    $invalidKey = app(IndexNowService::class)->submit(['https://canonical.example/blog/invalid-key']);
    expect($invalidKey->accepted)->toBeFalse();
    expect($invalidKey->retryable)->toBeFalse();
    expect($invalidKey->errorCode)->toBe('invalid_key');

    $rateLimited = app(IndexNowService::class)->submit(['https://canonical.example/blog/rate-limited']);
    expect($rateLimited->retryable)->toBeTrue();
    expect($rateLimited->retryAfterSeconds)->toBe(30);

    $invalidUrl = app(IndexNowService::class)->submit(['https://canonical.example/blog/unprocessable']);
    expect($invalidUrl->retryable)->toBeFalse();
    expect($invalidUrl->errorCode)->toBe('invalid_url_or_host');
});

test('connection timeouts are retried a bounded number of times and retained for outbox recovery', function () {
    app()->detectEnvironment(fn () => 'production');
    Http::fakeSequence()
        ->pushFailedConnection('temporary network failure')
        ->pushFailedConnection('temporary network failure')
        ->pushFailedConnection('temporary network failure');

    $result = app(IndexNowService::class)->submit(['https://canonical.example/blog/timeout']);

    expect($result->accepted)->toBeFalse();
    expect($result->retryable)->toBeTrue();
    expect($result->errorCode)->toBe('connection_error');
    Http::assertSentCount(3);
});

test('nonproduction environments never send IndexNow requests', function () {
    app()->detectEnvironment(fn () => 'testing');
    config(['indexnow.enabled' => true]);

    publishedPost(['slug' => 'nonproduction-post']);

    expect(app(IndexNowService::class)->canSubmit())->toBeFalse();
    expect(app(IndexNowService::class)->submit(['https://canonical.example/blog/not-sent'])->accepted)->toBeFalse();
    expect(IndexNowNotification::count())->toBe(0);
    Http::assertNothingSent();
});

test('outbox keeps failed submissions for bounded backoff and manual recovery', function () {
    $post = publishedPost();
    app()->detectEnvironment(fn () => 'production');
    Http::fake(['https://api.indexnow.org/indexnow' => Http::response('', 503)]);

    expect(app(IndexNowOutboxDispatcher::class)->dispatchDue(10))->toBe(3);

    $notification = IndexNowNotification::query()
        ->where('url', 'https://canonical.example/blog/public-article')
        ->firstOrFail();
    expect($notification->status)->toBe('pending');
    expect($notification->attempts)->toBe(1);
    expect($notification->available_at->isFuture())->toBeTrue();

    expect(app(IndexNowOutboxDispatcher::class)->retryFailed())->toBe(0);
});

test('initial URL command is a dry run by default and can only complete once after key verification', function () {
    app()->detectEnvironment(fn () => 'production');
    $key = config('indexnow.key');
    Http::fake(function (ClientRequest $request) use ($key) {
        if ($request->url() === 'https://canonical.example/'.$key.'.txt') {
            return Http::response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        if ($request->url() === 'https://api.indexnow.org/indexnow') {
            return Http::response('', 202);
        }

        return Http::response('', 404);
    });

    $this->artisan('indexnow:submit-existing')
        ->expectsOutputToContain('Dry run only')
        ->assertSuccessful();
    expect(IndexNowBulkRun::count())->toBe(0);

    $this->artisan('indexnow:submit-existing --execute --batch=2')
        ->expectsOutputToContain('Run status: complete')
        ->assertSuccessful();
    expect(IndexNowBulkRun::query()->where('name', 'existing-public-urls-v1')->value('status'))->toBe('complete');

    $postCount = Http::recorded()->filter(fn (array $pair) => $pair[0]->url() === 'https://api.indexnow.org/indexnow')->count();
    expect($postCount)->toBe(2);
    $this->artisan('indexnow:submit-existing --execute')
        ->expectsOutputToContain('already complete')
        ->assertFailed();
    expect(Http::recorded()->filter(fn (array $pair) => $pair[0]->url() === 'https://api.indexnow.org/indexnow')->count())
        ->toBe($postCount);
});

test('initial URL command can verify the production key without queuing or submitting URLs', function () {
    app()->detectEnvironment(fn () => 'production');
    $key = config('indexnow.key');
    Http::fake([
        'https://canonical.example/'.$key.'.txt' => Http::response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']),
        'https://api.indexnow.org/indexnow' => Http::response('', 202),
    ]);

    $this->artisan('indexnow:submit-existing --verify-key')
        ->expectsOutputToContain('Verified the configured production key file: https://canonical.example/'.$key.'.txt')
        ->expectsOutputToContain('No URLs were queued or submitted')
        ->assertSuccessful();

    expect(IndexNowBulkRun::count())->toBe(0);
    Http::assertSentCount(1);
    Http::assertNotSent(fn (ClientRequest $request) => $request->url() === 'https://api.indexnow.org/indexnow');
});

test('bulk submission execution is rejected outside production', function () {
    app()->detectEnvironment(fn () => 'testing');
    $this->artisan('indexnow:submit-existing --execute')
        ->expectsOutputToContain('only in production')
        ->assertFailed();

    expect(IndexNowBulkRun::count())->toBe(0);
    Http::assertNothingSent();
});
