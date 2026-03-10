<?php

use App\Models\User;
use App\Models\ClientXp;

it('correctly identifies client role', function () {
    $client = User::factory()->create(['role' => 'client']);
    expect($client->isClient())->toBeTrue()
        ->and($client->isCoach())->toBeFalse()
        ->and($client->isAdmin())->toBeFalse();
});

it('correctly identifies coach role', function () {
    $coach = User::factory()->create(['role' => 'coach']);
    expect($coach->isCoach())->toBeTrue()
        ->and($coach->isClient())->toBeFalse();
});

it('correctly identifies admin roles', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    expect($admin->isAdmin())->toBeTrue()
        ->and($superadmin->isAdmin())->toBeTrue()
        ->and($superadmin->isSuperAdmin())->toBeTrue();
});

it('checks plan hierarchy correctly', function () {
    $elite = User::factory()->create(['plan' => 'elite']);
    $metodo = User::factory()->create(['plan' => 'metodo']);
    $esencial = User::factory()->create(['plan' => 'esencial']);

    expect($elite->hasPlan('metodo'))->toBeTrue()
        ->and($elite->hasPlan('elite'))->toBeTrue()
        ->and($metodo->hasPlan('elite'))->toBeFalse()
        ->and($esencial->hasPlan('metodo'))->toBeFalse();
});

it('has profile relationship', function () {
    $user = User::factory()->create();
    expect($user->profile())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
});

it('has xp relationship', function () {
    $user = User::factory()->create();
    ClientXp::factory()->create(['user_id' => $user->id]);
    expect($user->xp)->toBeInstanceOf(ClientXp::class);
});
