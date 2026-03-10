<?php

use App\Models\Metric;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns metrics history for authenticated client', function () {
    $user = User::factory()->create();
    Metric::factory()->count(5)->create(['user_id' => $user->id]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/metrics')
        ->assertOk()
        ->assertJsonCount(5, 'data');
});

it('saves new metric entry with upsert for today', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/metrics', ['peso' => 75.5])->assertStatus(201);
    $this->postJson('/api/v1/metrics', ['peso' => 76.0])->assertStatus(201);

    // Should be upserted — only 1 record for today
    expect(Metric::where('user_id', $user->id)->whereDate('log_date', today())->count())->toBe(1);
    expect(Metric::where('user_id', $user->id)->first()->peso)->toBe(76.0);
});

it('requires authentication for metrics', function () {
    $this->getJson('/api/v1/metrics')->assertUnauthorized();
});
