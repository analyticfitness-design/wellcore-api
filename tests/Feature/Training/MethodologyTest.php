<?php

use App\Models\User;
use App\Services\TrainingMethodologyService;
use Laravel\Sanctum\Sanctum;

it('returns full methodology catalog', function () {
    $user = User::factory()->create(['role' => 'coach']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/training/methodologies')
        ->assertOk()
        ->assertJsonStructure(['methodologies', 'total', 'categories'])
        ->assertJsonPath('total', count(TrainingMethodologyService::getCatalog()));
});

it('filters methodologies by category', function () {
    $user = User::factory()->create(['role' => 'coach']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/training/methodologies?category=fuerza')
        ->assertOk()
        ->assertJsonFragment(['category' => 'fuerza']);
});

it('returns specific methodology by id', function () {
    $user = User::factory()->create(['role' => 'coach']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/training/methodologies/ppl')
        ->assertOk()
        ->assertJsonPath('methodology.id', 'ppl')
        ->assertJsonPath('methodology.name', 'Push / Pull / Legs (PPL)');
});

it('returns 404 for unknown methodology', function () {
    $user = User::factory()->create(['role' => 'coach']);
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/training/methodologies/unknown_method')
        ->assertNotFound();
});

it('coach can request plan generation with methodology for their client', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $coach  = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create(['role' => 'client', 'coach_id' => $coach->id]);

    Sanctum::actingAs($coach);

    $this->postJson('/api/v1/training/generate', [
        'client_id'      => $client->id,
        'methodology_id' => 'ppl',
        'intake'         => [
            'level' => 'intermedio',
            'place' => 'gym',
            'days'  => ['lunes', 'martes', 'jueves', 'viernes', 'sábado', 'domingo'],
            'goals' => ['hipertrofia'],
        ],
    ])->assertStatus(202)
      ->assertJsonPath('methodology.id', 'ppl')
      ->assertJsonPath('status', 'generating');

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\GenerateAiPlan::class);
});

it('client cannot list their own coach clients via training routes', function () {
    $client = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($client);

    $this->postJson('/api/v1/training/generate', [
        'client_id'      => $client->id,
        'methodology_id' => 'ppl',
    ])->assertForbidden();
});
