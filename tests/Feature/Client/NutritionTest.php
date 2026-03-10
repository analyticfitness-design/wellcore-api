<?php

use App\Models\NutritionLog;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('saves daily nutrition log', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/nutrition', [
        'calories_actual' => 2100,
        'protein_g'       => 150,
        'carbs_g'         => 220,
        'fat_g'           => 70,
        'adherence_pct'   => 85,
    ])->assertCreated();

    expect(NutritionLog::where('user_id', $user->id)->exists())->toBeTrue();
});

it('updates existing nutrition log for same day (upsert)', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/nutrition', ['calories_actual' => 1800])->assertCreated();
    $this->postJson('/api/v1/nutrition', ['calories_actual' => 2000])->assertOk();

    expect(NutritionLog::where('user_id', $user->id)->count())->toBe(1);
    expect(NutritionLog::where('user_id', $user->id)->first()->calories_actual)->toBe(2000);
});

it('returns today nutrition log', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    NutritionLog::create([
        'user_id'         => $user->id,
        'log_date'        => today()->toDateString(),
        'calories_actual' => 2200,
        'protein_g'       => 160,
    ]);

    $this->getJson('/api/v1/nutrition/today')
        ->assertOk()
        ->assertJsonPath('nutrition.calories_actual', 2200);
});

it('requires auth to log nutrition', function () {
    $this->postJson('/api/v1/nutrition', ['calories_actual' => 2000])->assertUnauthorized();
});
