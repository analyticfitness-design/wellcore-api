<?php

use App\Models\WellnessLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('saves daily wellness check', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/wellness', [
        'energy_level'  => 7,
        'stress_level'  => 4,
        'sleep_hours'   => 7.5,
        'sleep_quality' => 8,
        'mood'          => 8,
    ])->assertCreated();

    expect(WellnessLog::where('user_id', $user->id)->exists())->toBeTrue();
});

it('updates existing wellness log for same day', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/wellness', ['energy_level' => 5, 'mood' => 6])->assertCreated();
    $this->postJson('/api/v1/wellness', ['energy_level' => 8, 'mood' => 9])->assertOk();

    expect(WellnessLog::where('user_id', $user->id)->count())->toBe(1);
    expect(WellnessLog::where('user_id', $user->id)->first()->mood)->toBe(9);
});

it('validates wellness score range 1-10', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/wellness', ['energy_level' => 11])
        ->assertUnprocessable();
});

it('returns today wellness log', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    WellnessLog::create([
        'user_id'      => $user->id,
        'log_date'     => today()->toDateString(),
        'energy_level' => 9,
        'mood'         => 8,
    ]);

    $this->getJson('/api/v1/wellness/today')
        ->assertOk()
        ->assertJsonPath('wellness.energy_level', 9);
});
