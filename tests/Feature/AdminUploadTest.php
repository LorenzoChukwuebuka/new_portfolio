<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('admin uploads require authentication and return a public storage url', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('cover.png', 120, 80);

    $this->postJson('/api/admin/uploads', ['image' => $file])->assertUnauthorized();

    $response = $this->actingAs(User::factory()->create(), 'sanctum')
        ->postJson('/api/admin/uploads', ['image' => $file])
        ->assertCreated()
        ->assertJsonStructure(['url']);

    expect($response->json('url'))->toStartWith('/storage/uploads/posts/'.now()->format('Y/m').'/');
    Storage::disk('public')->assertExists(str_replace('/storage/', '', $response->json('url')));
});

test('admin uploads reject invalid types and files over five megabytes', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/admin/uploads', ['image' => UploadedFile::fake()->create('notes.txt', 10)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/admin/uploads', ['image' => UploadedFile::fake()->image('large.jpg')->size(5121)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('image');
});
