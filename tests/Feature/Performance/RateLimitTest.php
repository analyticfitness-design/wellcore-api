<?php

use App\Models\ClientXp;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Rate Limiting Tests
|--------------------------------------------------------------------------
| These tests verify named rate limiters work correctly with the array
| cache driver. No Redis connection is required.
*/

beforeEach(function () {
    // Clear all rate limiter state before every test so that each test
    // starts with a clean slate regardless of execution order.
    RateLimiter::clear('login|127.0.0.1');
});

it('rate limits login endpoint after 5 failed attempts in 15 minutes', function () {
    // Exhaust the 5-attempt allowance
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email'    => 'notexist@test.com',
            'password' => 'wrong',
        ]);
    }

    // The 6th attempt must be rejected with HTTP 429
    $this->postJson('/api/v1/auth/login', [
        'email'    => 'notexist@test.com',
        'password' => 'wrong',
    ])->assertTooManyRequests();
});

it('leaderboard endpoint is accessible to authenticated clients', function () {
    $coach  = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create([
        'role'     => 'client',
        'coach_id' => $coach->id,
        'status'   => 'activo',
    ]);
    Sanctum::actingAs($client);

    $this->getJson('/api/v1/gamification/leaderboard')
        ->assertOk()
        ->assertJsonStructure(['leaderboard']);
});

it('leaderboard returns coach clients sorted by XP descending', function () {
    $coach   = User::factory()->create(['role' => 'coach']);
    $client1 = User::factory()->create([
        'role'     => 'client',
        'coach_id' => $coach->id,
        'status'   => 'activo',
    ]);
    $client2 = User::factory()->create([
        'role'     => 'client',
        'coach_id' => $coach->id,
        'status'   => 'activo',
    ]);

    ClientXp::create(['user_id' => $client1->id, 'xp_total' => 500, 'level' => 3, 'streak_days' => 5]);
    ClientXp::create(['user_id' => $client2->id, 'xp_total' => 300, 'level' => 2, 'streak_days' => 2]);

    Sanctum::actingAs($client1);

    $response    = $this->getJson('/api/v1/gamification/leaderboard')->assertOk();
    $leaderboard = $response->json('leaderboard');

    expect($leaderboard)->not->toBeEmpty();
    expect($leaderboard[0]['xp_total'])->toBeGreaterThanOrEqual($leaderboard[1]['xp_total'] ?? 0);
});
