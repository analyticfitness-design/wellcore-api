<?php

use App\Models\Pod;
use App\Models\PodMember;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows coach to create a pod', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    Sanctum::actingAs($coach);

    $response = $this->postJson('/api/v1/coach/pods', [
        'name'        => 'Equipo Fuerza Enero',
        'max_members' => 8,
    ])->assertCreated();

    expect($response->json('pod.name'))->toBe('Equipo Fuerza Enero');
});

it('allows coach to create pod and add clients as members', function () {
    $coach   = User::factory()->create(['role' => 'coach']);
    $clients = User::factory()->count(5)->create(['coach_id' => $coach->id, 'role' => 'client']);
    Sanctum::actingAs($coach);

    $response = $this->postJson('/api/v1/coach/pods', [
        'name'        => 'Equipo Fuerza',
        'max_members' => 8,
        'client_ids'  => $clients->pluck('id')->toArray(),
    ])->assertCreated();

    expect(PodMember::where('pod_id', $response->json('pod.id'))->count())->toBe(5);
});

it('rejects pod with more than 8 client_ids', function () {
    $coach   = User::factory()->create(['role' => 'coach']);
    $clients = User::factory()->count(10)->create(['coach_id' => $coach->id, 'role' => 'client']);
    Sanctum::actingAs($coach);

    $this->postJson('/api/v1/coach/pods', [
        'name'        => 'Pod Grande',
        'max_members' => 8,
        'client_ids'  => $clients->pluck('id')->toArray(),
    ])->assertUnprocessable();
});

it('lists pods for authenticated coach', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    Pod::factory()->count(3)->create(['coach_id' => $coach->id]);
    Sanctum::actingAs($coach);

    $this->getJson('/api/v1/coach/pods')
        ->assertOk()
        ->assertJsonCount(3, 'pods');
});

it('requires coach role to manage pods', function () {
    $client = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($client);

    $this->postJson('/api/v1/coach/pods', ['name' => 'Test'])->assertForbidden();
});
