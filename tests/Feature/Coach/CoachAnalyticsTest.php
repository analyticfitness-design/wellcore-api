<?php

use App\Models\Checkin;
use App\Models\ClientXp;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('returns adherence metrics for coach clients', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    $client = User::factory()->create(['coach_id' => $coach->id, 'role' => 'client', 'status' => 'activo']);

    foreach ([1, 2, 3] as $weeksAgo) {
        Checkin::factory()->for($client)->create([
            'checkin_date' => now()->subWeeks($weeksAgo)->toDateString(),
        ]);
    }

    Sanctum::actingAs($coach);

    $this->getJson('/api/v1/coach/analytics')
        ->assertOk()
        ->assertJsonStructure([
            'overview' => ['total_clients', 'active_clients', 'avg_adherence_pct', 'clients_with_streak'],
            'clients_at_risk',
            'top_performers',
            'weekly_trends',
        ]);
});

it('identifies clients at churn risk when no checkins in 14+ days', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    $riskClient = User::factory()->create([
        'coach_id' => $coach->id,
        'role' => 'client',
        'status' => 'activo',
    ]);
    // Sin check-ins → alto riesgo

    Sanctum::actingAs($coach);

    $response = $this->getJson('/api/v1/coach/analytics');
    $atRisk = collect($response->json('clients_at_risk'));

    expect($atRisk->contains('id', $riskClient->id))->toBeTrue();
});

it('only shows analytics for coach own clients', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    $otherCoach = User::factory()->create(['role' => 'coach']);
    $myClient = User::factory()->create(['coach_id' => $coach->id, 'role' => 'client', 'status' => 'activo']);
    $otherClient = User::factory()->create(['coach_id' => $otherCoach->id, 'role' => 'client', 'status' => 'activo']);

    Sanctum::actingAs($coach);

    $response = $this->getJson('/api/v1/coach/analytics')->assertOk();

    expect($response->json('overview.total_clients'))->toBe(1);
});

it('requires coach role to access analytics', function () {
    $client = User::factory()->create(['role' => 'client']);
    Sanctum::actingAs($client);

    $this->getJson('/api/v1/coach/analytics')->assertForbidden();
});
