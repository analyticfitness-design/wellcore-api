<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('logs in a client with valid credentials', function () {
    $user = User::factory()->create([
        'role' => 'client',
        'plan' => 'elite',
        'password' => bcrypt('password123'),
        'status' => 'activo',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'token', 'expires_in', 'user' => ['id', 'name', 'email', 'role', 'plan']
        ]);
});

it('rejects invalid password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct')]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertUnauthorized();
});

it('rejects inactive account', function () {
    $user = User::factory()->create([
        'status' => 'inactivo',
        'password' => bcrypt('password'),
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertUnauthorized();
});

it('returns authenticated user on /me', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonStructure(['user' => ['id', 'name', 'email', 'role', 'plan', 'status']]);
});

it('logs out and invalidates token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/auth/logout')->assertOk();
    expect($user->tokens()->count())->toBe(0);
});

it('admin single session — revokes old tokens on new login', function () {
    $admin = User::factory()->admin()->create(['password' => bcrypt('admin123')]);

    // First login creates token
    $this->postJson('/api/v1/auth/login', ['email' => $admin->email, 'password' => 'admin123']);
    expect($admin->fresh()->tokens()->count())->toBe(1);

    // Second login should revoke previous and create new
    $this->postJson('/api/v1/auth/login', ['email' => $admin->email, 'password' => 'admin123']);
    expect($admin->fresh()->tokens()->count())->toBe(1);
});
