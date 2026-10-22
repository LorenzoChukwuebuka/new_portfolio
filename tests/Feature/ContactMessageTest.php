<?php

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('contact messages validate and store with an optional subject', function () {
    $response = $this->postJson('/api/contact', [
        'name' => 'A Visitor',
        'email' => 'visitor@example.test',
        'message' => 'I would like to discuss a project.',
    ]);

    $response->assertOk()->assertJsonPath('success', true);
    $this->assertDatabaseHas('contact_messages', [
        'email' => 'visitor@example.test',
        'subject' => null,
    ]);
});

test('contact form returns field validation errors', function () {
    $this->postJson('/api/contact', [
        'name' => '',
        'email' => 'not-an-email',
        'message' => 'short',
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'message']);
});

test('contact honeypot returns success without storing a message', function () {
    $response = $this->postJson('/api/contact', ['website' => 'https://spam.example']);

    $response->assertOk()->assertJsonPath('success', true);
    expect(ContactMessage::query()->count())->toBe(0);
});
