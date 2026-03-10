<?php

use App\Models\Checkin;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('submits a weekly check-in', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/checkins', [
        'bienestar' => 7,
        'dias_entrenados' => 4,
        'nutricion' => 'Parcial',
        'comentario' => 'Buena semana',
    ])->assertStatus(201);
});

it('upserts check-in for same day', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/checkins', ['bienestar' => 7]);
    $this->postJson('/api/v1/checkins', ['bienestar' => 9]);

    expect(Checkin::where('user_id', $user->id)->count())->toBe(1);
    expect(Checkin::where('user_id', $user->id)->first()->bienestar)->toBe(9);
});
