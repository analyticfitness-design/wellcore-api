<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('superadmin can list all clients with KPIs', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    User::factory()->count(5)->create(['role' => 'client', 'status' => 'activo']);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/clients')
        ->assertOk()
        ->assertJsonStructure(['clients', 'total', 'kpis' => ['total_activos', 'elite', 'metodo', 'esencial']]);
});

it('admin can impersonate a client', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $client = User::factory()->create(['role' => 'client']);

    Sanctum::actingAs($admin);

    $this->postJson("/api/v1/admin/impersonate/{$client->id}")
        ->assertOk()
        ->assertJsonStructure(['token', 'expires_in', 'client']);
});

it('non-admin cannot access admin routes', function () {
    $client = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($client);

    $this->getJson('/api/v1/admin/clients')->assertForbidden();
});

it('coach cannot access admin routes', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    Sanctum::actingAs($coach);

    $this->getJson('/api/v1/admin/clients')->assertForbidden();
});

it('admin can filter clients by plan', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    User::factory()->count(2)->create(['role' => 'client', 'plan' => 'elite']);
    User::factory()->count(3)->create(['role' => 'client', 'plan' => 'esencial']);

    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/clients?plan=elite')
        ->assertOk()
        ->assertJsonCount(2, 'clients');
});
