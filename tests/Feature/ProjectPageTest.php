<?php

use App\Models\Media;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('project detail is server rendered with status image description and neighbors', function () {
    $previous = Project::create([
        'title' => 'Previous case study', 'slug' => 'previous-case-study', 'description' => 'An earlier project.',
        'project_link' => 'https://example.test/previous', 'technologies' => ['Laravel'], 'status' => 'completed', 'order' => 1,
    ]);
    $project = Project::create([
        'title' => 'Current case study', 'slug' => 'current-case-study', 'description' => 'A private project with useful details.',
        'full_description' => "Opening paragraph.\n\n## What changed\n- Safer releases\n- Clearer operations",
        'project_link' => 'https://example.test/current', 'technologies' => ['Laravel', 'Vue'],
        'status' => 'in-progress', 'order' => 2, 'is_featured' => true,
    ]);
    Project::create([
        'title' => 'Next case study', 'slug' => 'next-case-study', 'description' => 'A later project.',
        'project_link' => 'https://example.test/next', 'technologies' => ['Go'], 'status' => 'archived', 'order' => 3,
    ]);
    Media::create([
        'mediable_id' => $project->id,
        'mediable_type' => Project::class,
        'url' => 'images/current-project.webp',
        'type' => 'image',
    ]);

    $response = $this->get('/projects/current-case-study')->assertOk();

    $response->assertSee('<h1>Current case study</h1>', false)
        ->assertSee('In progress')
        ->assertSee('Featured work')
        ->assertSee('<h2>What changed</h2>', false)
        ->assertSee('<li>Safer releases</li>', false)
        ->assertSee('/storage/images/current-project.webp')
        ->assertSee(route('seo.public.projects.show', $previous), false)
        ->assertSee(route('seo.public.projects.show', ['slug' => 'next-case-study']), false);
});

test('project filters are server-side and keep technology selection in the URL', function () {
    Project::create([
        'title' => 'Matching project', 'slug' => 'matching-project', 'description' => 'Matching result.',
        'project_link' => 'https://example.test/matching', 'technologies' => ['Laravel'], 'status' => 'in-progress', 'order' => 1,
    ]);
    Project::create([
        'title' => 'Other project', 'slug' => 'other-project', 'description' => 'Filtered out.',
        'project_link' => 'https://example.test/other', 'technologies' => ['Go'], 'status' => 'completed', 'order' => 2,
    ]);

    $this->get('/projects?status=in-progress&tech=Laravel')
        ->assertOk()
        ->assertSee('Matching project')
        ->assertDontSee('Other project');
});
