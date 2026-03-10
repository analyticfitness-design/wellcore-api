<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns coach client roster with last activity', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    User::factory()->count(3)->create(['coach_id' => $coach->id, 'role' => 'client']);

    Sanctum::actingAs($coach);

    $this->getJson('/api/v1/coach/clients')
        ->assertOk()
        ->assertJsonCount(3, 'clients')
        ->assertJsonStructure(['clients' => [['id', 'name', 'plan', 'status']]]);
});

it('coach can see individual client details', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create(['coach_id' => $coach->id, 'role' => 'client']);

    Sanctum::actingAs($coach);

    $this->getJson("/api/v1/coach/clients/{$client->id}")
        ->assertOk()
        ->assertJsonStructure(['client', 'recent_checkins', 'recent_metrics']);
});

it('coach cannot see clients that are not theirs', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    $otherClient = User::factory()->create(['role' => 'client']); // sin coach_id del coach

    Sanctum::actingAs($coach);

    $this->getJson("/api/v1/coach/clients/{$otherClient->id}")
        ->assertForbidden();
});

it('allows coach to create note for client', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create(['coach_id' => $coach->id, 'role' => 'client']);

    Sanctum::actingAs($coach);

    $this->postJson("/api/v1/coach/notes/{$client->id}", [
        'content' => 'Cliente mejoró su forma de sentadilla',
        'note_type' => 'logro',
    ])->assertCreated();
});

it('coach can reply to client checkin', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create(['coach_id' => $coach->id, 'role' => 'client']);
    $checkin = \App\Models\Checkin::factory()->create(['user_id' => $client->id]);

    Sanctum::actingAs($coach);

    $this->postJson("/api/v1/coach/checkins/{$checkin->id}/reply", [
        'reply' => '¡Excelente semana! Sigue así.',
    ])->assertOk()
      ->assertJsonPath('data.coach_reply', '¡Excelente semana! Sigue así.');
});

it('blocks non-coach from accessing coach routes', function () {
    $client = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($client);

    $this->getJson('/api/v1/coach/clients')->assertForbidden();
});
