<?php

use App\Models\CommunityPost;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('creates a community post', function () {
    $user = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/community/posts', [
        'content'   => '¡Logré mi meta de peso!',
        'post_type' => 'milestone',
        'audience'  => 'all',
    ])->assertCreated()
      ->assertJsonStructure(['data' => ['id', 'content', 'post_type']]);
});

it('strips HTML from post content for XSS protection', function () {
    $user = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/community/posts', [
        'content'  => '<script>alert("xss")</script>¡Hola!',
        'audience' => 'all',
    ])->assertCreated();

    expect($response->json('data.content'))->toBe('¡Hola!');
});

it('lists community posts', function () {
    $user = User::factory()->create(['role' => 'client']);
    CommunityPost::factory()->count(3)->create(['user_id' => $user->id, 'audience' => 'all']);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/community/posts')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('requires auth to post in community', function () {
    $this->postJson('/api/v1/community/posts', ['content' => 'test'])
        ->assertUnauthorized();
});
