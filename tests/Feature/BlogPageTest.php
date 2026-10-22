<?php

use App\Models\BlogComment;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPublishedPost(string $title, string $slug, ?Category $category, string $publishedAt, string $content = '<p>Writeup content.</p>'): Post
{
    return Post::create([
        'category_id' => $category?->id,
        'title' => $title,
        'slug' => $slug,
        'excerpt' => 'A useful short introduction to this writeup.',
        'content' => $content,
        'author' => '',
        'read_time' => 0,
        'status' => 'published',
        'published_at' => $publishedAt,
    ]);
}

test('blog show is formatted in raw html with toc, neighbors, related posts and approved comments', function () {
    $category = Category::create(['name' => 'Engineering', 'slug' => 'engineering']);
    createPublishedPost('Older writeup', 'older-writeup', $category, '2026-01-01 10:00:00');
    $post = createPublishedPost('Current writeup', 'current-writeup', $category, '2026-01-02 10:00:00', <<<'HTML'
        <h2>Reliable releases</h2>
        <p>Formatted <strong>Quill content</strong>.</p>
        <ol><li data-list="bullet"><span class="ql-ui"></span>First principle</li></ol>
        <script>window.compromised = true</script>
        HTML);
    createPublishedPost('Newer writeup', 'newer-writeup', $category, '2026-01-03 10:00:00');
    createPublishedPost('Related writeup', 'related-writeup', $category, '2026-01-04 10:00:00');

    $approved = BlogComment::create([
        'post_id' => $post->id, 'author_name' => 'Reader', 'author_email' => 'reader@example.test',
        'body' => 'A useful comment.', 'is_approved' => true, 'status' => 'approved',
    ]);
    BlogComment::create([
        'post_id' => $post->id, 'author_name' => 'Waiting reader', 'author_email' => 'wait@example.test',
        'body' => 'This should stay private.', 'is_approved' => false, 'status' => 'pending',
    ]);
    BlogComment::create([
        'post_id' => $post->id, 'parent_id' => $approved->id, 'author_name' => 'Author reply', 'author_email' => 'author@example.test',
        'body' => 'Thanks for reading.', 'is_approved' => true, 'status' => 'approved',
    ]);

    $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0'])->get('/blog/current-writeup')->assertOk();
    $response->assertSee('<h1>Current writeup</h1>', false)
        ->assertSee('<h2 id="reliable-releases">Reliable releases</h2>', false)
        ->assertSee('<ul><li>First principle</li></ul>', false)
        ->assertSee('aria-label="Table of contents"', false)
        ->assertSee('Older writeup')
        ->assertSee('Newer writeup')
        ->assertSee('Related writeup')
        ->assertSee('A useful comment.')
        ->assertSee('Thanks for reading.')
        ->assertDontSee('This should stay private.')
        ->assertDontSee('window.compromised', false)
        ->assertSee('type="application/ld+json"', false);

    $this->assertSame(1, $post->fresh()->views_count);
    $this->get('/blog/current-writeup')->assertOk();
    $this->assertSame(1, $post->fresh()->views_count);
});

test('blog listing shows real published counts and applies category and tag filters on the server', function () {
    $engineering = Category::create(['name' => 'Engineering', 'slug' => 'engineering']);
    $design = Category::create(['name' => 'Design', 'slug' => 'design']);
    $matching = createPublishedPost('Engineering note', 'engineering-note', $engineering, '2026-01-01 10:00:00');
    $tag = \App\Models\Tag::create(['name' => 'Laravel']);
    $matching->tags()->attach($tag->id);
    createPublishedPost('Design note', 'design-note', $design, '2026-01-02 10:00:00');
    Post::create(['title' => 'Draft', 'slug' => 'draft', 'excerpt' => 'Hidden', 'content' => '', 'status' => 'draft']);

    $this->get('/blog')
        ->assertOk()
        ->assertSee('<h1 class="section-heading__title">The writeups</h1>', false)
        ->assertSee('2</strong><span>published articles</span>', false)
        ->assertSee('Engineering note')
        ->assertSee('Design note');

    $this->get('/blog?category=engineering&tag=Laravel')
        ->assertOk()
        ->assertSee('Engineering note')
        ->assertDontSee('Design note');
});

test('public routes include their main heading in server rendered html without client execution', function () {
    $category = Category::create(['name' => 'Engineering', 'slug' => 'engineering']);
    createPublishedPost('Smoke writeup', 'smoke-writeup', $category, '2026-01-01 10:00:00', '<p>Visible article body.</p>');
    \App\Models\Project::create([
        'title' => 'Smoke project', 'slug' => 'smoke-project', 'description' => 'Visible project description.',
        'project_link' => 'https://example.test', 'technologies' => ['Laravel'], 'status' => 'completed',
    ]);

    foreach (['/', '/blog', '/blog/smoke-writeup', '/projects', '/projects/smoke-project'] as $url) {
        $response = $this->get($url)->assertOk();
        $this->assertMatchesRegularExpression('/<h1\b[^>]*>.*?<\/h1>/s', $response->getContent(), "Missing server rendered h1 at {$url}");
    }
});
