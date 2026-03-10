<?php

use App\Models\ClientXp;
use App\Models\User;
use App\Services\GamificationService;

it('awards XP for checkin event', function () {
    $user = User::factory()->create();
    ClientXp::create(['user_id' => $user->id, 'xp_total' => 0, 'level' => 1]);

    GamificationService::earnXp($user, 'checkin');

    expect($user->fresh()->xp->xp_total)->toBe(50);
});

it('promotes level when threshold is reached', function () {
    $user = User::factory()->create();
    ClientXp::create(['user_id' => $user->id, 'xp_total' => 190, 'level' => 1]);

    GamificationService::earnXp($user, 'checkin'); // +50 = 240 XP → nivel 2

    expect($user->fresh()->xp->level)->toBe(2);
});

it('increments streak on daily activity', function () {
    $user = User::factory()->create();
    ClientXp::create([
        'user_id' => $user->id,
        'xp_total' => 0,
        'level' => 1,
        'streak_days' => 6,
        'last_activity_date' => today()->subDay(),
    ]);

    GamificationService::earnXp($user, 'checkin');

    $xp = $user->fresh()->xp;
    expect($xp->streak_days)->toBe(7);
});

it('grants bonus XP on 7-day streak milestone', function () {
    $user = User::factory()->create();
    ClientXp::create([
        'user_id' => $user->id,
        'xp_total' => 0,
        'level' => 1,
        'streak_days' => 6,
        'last_activity_date' => today()->subDay(),
    ]);

    GamificationService::earnXp($user, 'checkin');

    // 50 (checkin) + 150 (streak_7 bonus) = 200
    expect($user->fresh()->xp->xp_total)->toBe(200);
});
