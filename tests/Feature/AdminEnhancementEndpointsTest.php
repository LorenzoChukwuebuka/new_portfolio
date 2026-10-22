<?php

use App\Models\PageVisit;
use App\Models\Post;
use App\Models\Cv;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function adminApiTestUser(): User
{
    return User::factory()->create();
}

test('project reorder is authenticated transactional and validates its list', function () {
    $projects = collect(range(1, 3))->map(fn (int $number) => Project::create([
        'title' => "Project {$number}", 'slug' => "project-{$number}", 'description' => 'Project description',
        'project_link' => null, 'technologies' => ['Laravel'], 'status' => 'completed', 'order' => $number,
    ]));

    $this->postJson('/api/admin/projects/reorder', ['projects' => []])->assertUnauthorized();
    $response = $this->actingAs(adminApiTestUser(), 'sanctum')
        ->postJson('/api/admin/projects/reorder', ['projects' => [
            ['id' => $projects[0]->id, 'order' => 3],
            ['id' => $projects[1]->id, 'order' => 1],
            ['id' => $projects[2]->id, 'order' => 2],
        ]])->assertOk();

    $response->assertJson(['message' => 'Projects reordered successfully']);
    expect($projects[0]->fresh()->order)->toBe(3)
        ->and($projects[1]->fresh()->order)->toBe(1)
        ->and($projects[2]->fresh()->order)->toBe(2);

    $this->actingAs(adminApiTestUser(), 'sanctum')
        ->postJson('/api/admin/projects/reorder', ['projects' => [['id' => 99999, 'order' => 1]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('projects.0.id');
});

test('project featured toggle is authenticated and resolves by project id', function () {
    $project = Project::create([
        'title' => 'Toggle me', 'slug' => 'toggle-me', 'description' => 'Project description',
        'project_link' => null, 'technologies' => ['Laravel'], 'status' => 'completed', 'order' => 1,
    ]);

    $this->patchJson("/api/admin/projects/{$project->id}/featured", ['is_featured' => true])->assertUnauthorized();
    $this->actingAs(adminApiTestUser(), 'sanctum')
        ->patchJson("/api/admin/projects/{$project->id}/featured", ['is_featured' => true])
        ->assertOk()
        ->assertJsonPath('project.is_featured', true);

    expect($project->fresh()->is_featured)->toBeTrue();
});

test('project links are optional for private work and list endpoints cap page size at fifty', function () {
    $user = adminApiTestUser();
    $payload = [
        'title' => 'Private work', 'slug' => 'private-work', 'description' => 'Private client work.',
        'project_link' => null, 'technologies' => ['Laravel'], 'status' => 'completed',
        'completed_at' => '2026-08-15', 'is_featured' => 'false',
    ];
    $this->actingAs($user, 'sanctum')->postJson('/api/admin/projects', $payload)->assertCreated();

    $this->actingAs($user, 'sanctum')->getJson('/api/admin/projects?per_page=50')->assertOk()->assertJsonPath('per_page', 50);
    $this->actingAs($user, 'sanctum')->getJson('/api/admin/projects?per_page=51')->assertUnprocessable()->assertJsonValidationErrors('per_page');
});

test('admin tag deletion explains when a tag is still used', function () {
    $tag = Tag::create(['name' => 'Laravel']);
    $project = Project::create([
        'title' => 'Tagged work', 'slug' => 'tagged-work', 'description' => 'Project description',
        'project_link' => null, 'technologies' => ['Laravel'], 'status' => 'completed', 'order' => 1,
    ]);
    $project->tags()->attach($tag);

    $this->actingAs(adminApiTestUser(), 'sanctum')
        ->deleteJson("/api/admin/tags/{$tag->id}")
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Cannot delete a tag while it is assigned to posts or projects.');

    expect($tag->fresh())->not->toBeNull();
});

test('admin paginated lists accept page sizes up to fifty', function () {
    $user = adminApiTestUser();
    $endpoints = [
        '/api/admin/categories',
        '/api/admin/tags',
        '/api/admin/contact-messages',
        '/api/admin/comments',
        '/api/admin/cv',
    ];

    foreach ($endpoints as $endpoint) {
        $this->getJson("{$endpoint}?per_page=40")->assertUnauthorized();
    }

    foreach ($endpoints as $endpoint) {
        $this->actingAs($user, 'sanctum')->getJson("{$endpoint}?per_page=40")->assertOk()->assertJsonPath('per_page', 40);
        $this->actingAs($user, 'sanctum')->getJson("{$endpoint}?per_page=51")->assertUnprocessable()->assertJsonValidationErrors('per_page');
    }
});

test('CV library actions accept the Vue form boolean and expose active selection', function () {
    Storage::fake('public');
    $user = adminApiTestUser();
    $this->actingAs($user, 'sanctum')->postJson('/api/admin/cv', [
        'title' => 'Portfolio CV',
        'version' => '2026',
        'is_active' => 'true',
        'file' => UploadedFile::fake()->create('portfolio.pdf', 32, 'application/pdf'),
    ])->assertCreated()->assertJsonPath('cv.is_active', true);

    $cv = Cv::firstOrFail();
    $cv->update(['is_active' => false]);
    $this->actingAs($user, 'sanctum')->postJson("/api/admin/cv/{$cv->id}/active")
        ->assertOk()->assertJsonPath('cv.is_active', true);
});

test('visit analytics supports the allowed days and rejects unsupported ranges', function () {
    $user = adminApiTestUser();
    foreach ([0, 6, 13, 29] as $daysAgo) {
        PageVisit::create(['path' => '/blog', 'ip_hash' => 'visitor-'.$daysAgo, 'referer' => $daysAgo % 2 ? 'https://search.example/q' : null, 'created_at' => now()->subDays($daysAgo)]);
    }

    $this->actingAs($user, 'sanctum')->getJson('/api/admin/dashboard/page-visits-analytics?days=7')->assertOk()->assertJsonCount(7);
    $this->actingAs($user, 'sanctum')->getJson('/api/admin/dashboard/referrer-sources?days=30')->assertOk()->assertJsonPath('unique_visitors', 4)->assertJsonFragment(['source' => 'Direct']);
    $this->actingAs($user, 'sanctum')->getJson('/api/admin/dashboard/page-visits-analytics?days=15')->assertUnprocessable()->assertJsonValidationErrors('days');
});
