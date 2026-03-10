<?php

use App\Models\BiometricLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('saves biometric log from wearable sync', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/metrics/biometric', [
        'steps'        => 8543,
        'sleep_hours'  => 7.5,
        'heart_rate'   => 68,
        'energy_level' => 8,
        'source'       => 'apple_health',
    ])->assertCreated();

    expect(BiometricLog::where('user_id', $user->id)->exists())->toBeTrue();
});

it('updates biometric log if already exists today', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/metrics/biometric', ['steps' => 5000])->assertCreated();
    $this->postJson('/api/v1/metrics/biometric', ['steps' => 10000])->assertOk();

    expect(BiometricLog::where('user_id', $user->id)->count())->toBe(1);
    expect(BiometricLog::where('user_id', $user->id)->first()->steps)->toBe(10000);
});

it('returns today biometric log', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    BiometricLog::create([
        'user_id'    => $user->id,
        'log_date'   => today()->toDateString(),
        'steps'      => 7500,
        'heart_rate' => 65,
    ]);

    $this->getJson('/api/v1/metrics/biometric/today')
        ->assertOk()
        ->assertJsonPath('biometric.steps', 7500);
});

it('rejects invalid heart rate above 300', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/metrics/biometric', ['heart_rate' => 500])
        ->assertUnprocessable();
});

it('rejects invalid source value', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/metrics/biometric', ['source' => 'fitbit'])
        ->assertUnprocessable();
});

it('requires authentication to log biometrics', function () {
    $this->postJson('/api/v1/metrics/biometric', ['steps' => 5000])
        ->assertUnauthorized();
});
