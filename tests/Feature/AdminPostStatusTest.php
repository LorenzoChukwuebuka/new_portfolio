<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('post status changes require auth and publish a draft with a timestamp', function () {
    $post = Post::create([
        'title' => 'Draft article', 'slug' => 'draft-article', 'excerpt' => 'A short excerpt.',
        'content' => '<p>Draft content.</p>', 'status' => 'draft', 'read_time' => 1,
    ]);

    $this->patchJson('/api/admin/posts/draft-article/status', ['status' => 'published'])->assertUnauthorized();
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->patchJson('/api/admin/posts/draft-article/status', ['status' => 'published'])
        ->assertOk()
        ->assertJsonPath('post.status', 'published');

    expect($post->fresh()->published_at)->not->toBeNull();
    $this->actingAs(User::factory()->create(), 'sanctum')
        ->patchJson('/api/admin/posts/draft-article/status', ['status' => 'hidden'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});
