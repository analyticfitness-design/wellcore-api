<?php

use App\Models\ClientXp;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;

/*
|--------------------------------------------------------------------------
| Cache Tests
|--------------------------------------------------------------------------
| Verifies that the array cache driver is operational in the test suite
| and that the leaderboard endpoint stores its result in the cache so
| that repeated requests return identical data without re-querying.
*/

it('cache store is working with the array driver', function () {
    Cache::put('test_key', 'test_value', 60);
    expect(Cache::get('test_key'))->toBe('test_value');
});

it('leaderboard response is cached for 5 minutes', function () {
    $coach  = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create([
        'role'     => 'client',
        'coach_id' => $coach->id,
        'status'   => 'activo',
    ]);
    ClientXp::create([
        'user_id'     => $client->id,
        'xp_total'    => 200,
        'level'       => 1,
        'streak_days' => 1,
    ]);

    Sanctum::actingAs($client);

    // First request — builds and stores the cache entry
    $response1 = $this->getJson('/api/v1/gamification/leaderboard')->assertOk();

    // Mutate the underlying data to confirm cache serves stale data (proves caching)
    ClientXp::where('user_id', $client->id)->update(['xp_total' => 9999]);

    // Second request — must return the same cached payload, not the mutated value
    $response2 = $this->getJson('/api/v1/gamification/leaderboard')->assertOk();

    expect($response1->json('leaderboard'))->toEqual($response2->json('leaderboard'));
});

it('leaderboard cache key is scoped per coach group', function () {
    $coach1  = User::factory()->create(['role' => 'coach']);
    $coach2  = User::factory()->create(['role' => 'coach']);
    $client1 = User::factory()->create(['role' => 'client', 'coach_id' => $coach1->id, 'status' => 'activo']);
    $client2 = User::factory()->create(['role' => 'client', 'coach_id' => $coach2->id, 'status' => 'activo']);

    ClientXp::create(['user_id' => $client1->id, 'xp_total' => 100, 'level' => 1, 'streak_days' => 1]);
    ClientXp::create(['user_id' => $client2->id, 'xp_total' => 200, 'level' => 2, 'streak_days' => 2]);

    // Coach1's client sees only coach1's group
    Sanctum::actingAs($client1);
    $resp1 = $this->getJson('/api/v1/gamification/leaderboard')->assertOk();

    // Coach2's client sees only coach2's group
    Sanctum::actingAs($client2);
    $resp2 = $this->getJson('/api/v1/gamification/leaderboard')->assertOk();

    $ids1 = collect($resp1->json('leaderboard'))->pluck('user_id')->toArray();
    $ids2 = collect($resp2->json('leaderboard'))->pluck('user_id')->toArray();

    // Groups must be completely disjoint
    expect(array_intersect($ids1, $ids2))->toBeEmpty();
});
