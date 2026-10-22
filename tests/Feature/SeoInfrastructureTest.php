<?php

use App\Models\Post;
use App\Models\Project;
use App\Support\Seo\CanonicalUrl;
use App\Support\Seo\SitemapUrlDiscovery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['seo.canonical_url' => 'https://canonical.example']);
});

test('the sitemap is valid xml with the correct content type and canonical static routes', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/xml');

    $document = new DOMDocument;
    expect($document->loadXML($response->getContent()))->toBeTrue();

    $locations = collect($document->getElementsByTagName('loc'))
        ->map(fn (DOMElement $element) => $element->textContent)
        ->all();

    expect($locations)->toContain(
        'https://canonical.example/',
        'https://canonical.example/projects',
        'https://canonical.example/blog',
    );
    expect($locations)->not->toContain('https://canonical.example/sitemap.xml');
    expect($locations)->not->toContain('https://canonical.example/robots.txt');
    expect($locations)->not->toContain('https://canonical.example/admin');
    expect($locations)->not->toContain('https://canonical.example/_design');
    expect($locations)->not->toContain('https://canonical.example/blog/{slug}');
    expect($response->getContent())->not->toContain('<lastmod>');
});

test('route discovery opts in only eligible named public static get routes and deduplicates urls', function () {
    Route::get('/seo-discovery-probe', fn () => response('ok'))
        ->name('seo.public.test-probe');
    Route::get('/seo-private-probe', fn () => response('ok'))
        ->name('internal.test-probe');
    Route::get('/admin/seo-probe', fn () => response('ok'))
        ->name('seo.public.admin-probe');
    Route::get('/seo-param-probe/{slug}', fn () => response('ok'))
        ->name('seo.public.parameterized-probe');
    Route::redirect('/seo-redirect-probe', '/')
        ->name('seo.public.redirect-probe');
    Route::get('/seo-auth-probe', fn () => response('ok'))
        ->middleware('auth')
        ->name('seo.public.auth-probe');
    Route::get('/api/seo-probe', fn () => response('ok'))
        ->name('seo.public.api-probe');
    Route::post('/seo-post-probe', fn () => response('ok'))
        ->name('seo.public.post-probe');

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();
    $document = new DOMDocument;
    $document->loadXML($xml);
    $locations = collect($document->getElementsByTagName('loc'))
        ->map(fn (DOMElement $element) => $element->textContent)
        ->all();

    expect($locations)->toContain('https://canonical.example/blog');
    expect(array_values(array_unique($locations)))->toBe($locations);
    expect($xml)->toContain('<loc>https://canonical.example/seo-discovery-probe</loc>');
    expect($xml)->not->toContain('seo-private-probe');
    expect($xml)->not->toContain('/admin/seo-probe');
    expect($xml)->not->toContain('seo-param-probe');
    expect($xml)->not->toContain('seo-redirect-probe');
    expect($xml)->not->toContain('seo-auth-probe');
    expect($xml)->not->toContain('api/seo-probe');
    expect($xml)->not->toContain('seo-post-probe');
});

test('published posts and publicly accessible projects are discovered dynamically', function () {
    $post = Post::create([
        'title' => 'Published SEO post',
        'slug' => 'published-seo-post',
        'excerpt' => 'A published post.',
        'content' => 'Post content.',
        'author' => 'Lorenzo Chukwuebuka Obi',
        'read_time' => 3,
        'status' => 'draft',
    ]);

    $project = Project::create([
        'title' => 'Archived but public project',
        'slug' => 'archived-public-project',
        'description' => 'Public under the current project routes.',
        'project_link' => 'https://example.test/project',
        'technologies' => ['Laravel'],
        'status' => 'archived',
    ]);

    $draftSitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($draftSitemap)->not->toContain('/blog/published-seo-post');

    $post->update([
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);

    $publishedSitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($publishedSitemap)->toContain('<loc>https://canonical.example/blog/published-seo-post</loc>');
    expect($publishedSitemap)->toContain('<loc>https://canonical.example/projects/archived-public-project</loc>');

    $post->update(['status' => 'draft']);
    $unpublishedSitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($unpublishedSitemap)->not->toContain('/blog/published-seo-post');

    $post->update([
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);
    $post->delete();
    $project->delete();

    $removedSitemap = $this->get('/sitemap.xml')->assertOk()->getContent();
    expect($removedSitemap)->not->toContain('/blog/published-seo-post');
    expect($removedSitemap)->not->toContain('/projects/archived-public-project');
});

test('future posts are excluded until their publication time', function () {
    Post::create([
        'title' => 'Future SEO post',
        'slug' => 'future-seo-post',
        'excerpt' => 'Not public yet.',
        'content' => 'Future content.',
        'author' => 'Lorenzo Chukwuebuka Obi',
        'read_time' => 3,
        'status' => 'published',
        'published_at' => now()->addDay(),
    ]);

    expect($this->get('/sitemap.xml')->assertOk()->getContent())
        ->not->toContain('/blog/future-seo-post');
});

test('production canonical urls force https and discard query and fragment variants', function () {
    app()->detectEnvironment(fn () => 'production');
    config([
        'seo.canonical_url' => 'http://canonical.example/',
    ]);

    expect(app(CanonicalUrl::class)->forPath('/blog?category=laravel#latest'))
        ->toBe('https://canonical.example/blog');
});

test('invalid production canonical configuration fails instead of emitting local urls', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['seo.canonical_url' => 'http://localhost']);

    expect(fn () => app(SitemapUrlDiscovery::class)->discover())
        ->toThrow(RuntimeException::class);
});

test('production public pages stay indexable and use the canonical host', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['seo.canonical_url' => 'http://canonical.example']);

    $this->get('/')
        ->assertOk()
        ->assertSee('<title>'.e(config('portfolio.name').' | '.config('portfolio.role')).'</title>', false)
        ->assertDontSee('&amp;amp;')
        ->assertSee('<link rel="canonical" href="https://canonical.example/">', false)
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertDontSee('noindex');

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('<loc>https://canonical.example/</loc>', false);
});

test('robots is environment aware and names the canonical sitemap only in production', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /', false)
        ->assertDontSee('Sitemap:');

    app()->detectEnvironment(fn () => 'production');
    config([
        'seo.canonical_url' => 'http://canonical.example',
    ]);

    $productionRobots = $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Allow: /', false)
        ->assertSee('Disallow: /admin', false)
        ->assertSee('Disallow: /api/admin/', false)
        ->assertSee('Sitemap: https://canonical.example/sitemap.xml', false);

    expect(preg_match('/^Disallow: \/api\/$/m', $productionRobots->getContent()))->toBe(0);
});

test('public content has server rendered metadata and detail content while unknown routes are real 404s', function () {
    $post = Post::create([
        'title' => 'Server rendered title',
        'slug' => 'server-rendered-title',
        'excerpt' => 'Useful summary for this public article.',
        'content' => 'The article body is present before Vue runs.',
        'author' => 'Lorenzo Chukwuebuka Obi',
        'read_time' => 3,
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);

    $response = $this->get('/blog/'.$post->slug)->assertOk();
    $response->assertSee('<h1>Server rendered title</h1>', false);
    $response->assertSee('The article body is present before Vue runs.');
    $response->assertSee('<link rel="canonical" href="https://canonical.example/blog/server-rendered-title">', false);
    $response->assertSee('noindex, nofollow');

    $response->assertSee('"@type":"BlogPosting"', false);
    $this->get('/does-not-exist')->assertNotFound();
    $this->get('/admin/login')->assertOk()->assertSee('noindex, nofollow');
});

test('public listing pages include database content in the initial response', function () {
    Post::create([
        'title' => 'Initial HTML post',
        'slug' => 'initial-html-post',
        'excerpt' => 'Visible before Vue runs.',
        'content' => 'Long body omitted from the listing response.',
        'author' => 'Lorenzo Chukwuebuka Obi',
        'read_time' => 3,
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);
    Project::create([
        'title' => 'Initial HTML project',
        'slug' => 'initial-html-project',
        'description' => 'Also visible before Vue runs.',
        'project_link' => 'https://example.test/project',
        'technologies' => ['Laravel'],
        'status' => 'completed',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('I build reliable software for serious teams.')
        ->assertSee('Initial HTML project')
        ->assertSee('Initial HTML post');

    $this->get('/blog')
        ->assertOk()
        ->assertSee('Initial HTML post')
        ->assertDontSee('Long body omitted from the listing response.');

    $this->get('/projects')
        ->assertOk()
        ->assertSee('Initial HTML project');
});

test('project detail metadata and CreativeWork structured data match the canonical record', function () {
    $project = Project::create([
        'title' => 'Structured data project',
        'slug' => 'structured-data-project',
        'description' => 'A real project description.',
        'full_description' => 'Additional project detail.',
        'project_link' => 'https://example.test/project',
        'technologies' => ['Laravel'],
        'status' => 'completed',
    ]);

    $response = $this->get('/projects/'.$project->slug)->assertOk();
    $response->assertSee('<link rel="canonical" href="https://canonical.example/projects/structured-data-project">', false);
    $response->assertSee('<h1>Structured data project</h1>', false);

    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
    $schemas = array_map(
        fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR),
        $matches[1],
    );
    $creativeWork = collect($schemas)->firstWhere('@type', 'CreativeWork');

    expect($creativeWork)->not->toBeNull()
        ->and($creativeWork['name'])->toBe('Structured data project')
        ->and($creativeWork['url'])->toBe('https://canonical.example/projects/structured-data-project');
});
