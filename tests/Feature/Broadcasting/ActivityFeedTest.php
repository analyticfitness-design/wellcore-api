<?php

use App\Events\LeaderboardUpdated;
use App\Events\NewCheckinReceived;
use App\Models\Checkin;
use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

it('broadcasts NewCheckinReceived event when checkin is submitted', function () {
    Event::fake([NewCheckinReceived::class]);

    $coach = User::factory()->create(['role' => 'coach', 'plan' => null, 'client_code' => null]);
    $user = User::factory()->create(['role' => 'client', 'coach_id' => $coach->id]);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/checkins', [
        'bienestar' => 8,
    ]);

    Event::assertDispatched(NewCheckinReceived::class, fn ($event) =>
        $event->checkin->user_id === $user->id
    );
});

it('broadcasts LeaderboardUpdated when XP is earned', function () {
    Event::fake([LeaderboardUpdated::class]);

    $user = User::factory()->create();

    GamificationService::earnXp($user, 'checkin');

    Event::assertDispatched(LeaderboardUpdated::class);
});

it('NewCheckinReceived broadcasts on correct channels', function () {
    $coach = User::factory()->create(['role' => 'coach', 'plan' => null, 'client_code' => null]);
    $user = User::factory()->create(['coach_id' => $coach->id]);
    $checkin = Checkin::factory()->create(['user_id' => $user->id]);

    $event = new NewCheckinReceived($checkin->load('user'));
    $channels = $event->broadcastOn();

    $channelNames = collect($channels)->map(fn ($ch) => $ch->name)->toArray();

    expect(in_array('activity-feed', $channelNames))->toBeTrue()
        ->and(in_array("private-coach.{$coach->id}", $channelNames))->toBeTrue();
});

it('NewCheckinReceived broadcastWith returns correct structure', function () {
    $user = User::factory()->create(['plan' => 'elite']);
    $checkin = Checkin::factory()->create(['user_id' => $user->id, 'bienestar' => 9]);

    $event = new NewCheckinReceived($checkin->load('user'));
    $data = $event->broadcastWith();

    expect($data)->toHaveKeys(['type', 'client_name', 'plan', 'bienestar', 'timestamp'])
        ->and($data['type'])->toBe('checkin')
        ->and($data['bienestar'])->toBe(9);
});
