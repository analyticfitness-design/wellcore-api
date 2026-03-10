<?php

use App\Jobs\SendBehavioralTrigger;
use App\Models\AutoMessageLog;
use App\Models\Checkin;
use App\Models\User;

it('identifies inactive clients for 7-day trigger', function () {
    // Cliente que no ha hecho check-in en 10 días
    $client = User::factory()->create(['role' => 'client', 'status' => 'activo']);
    Checkin::factory()->create([
        'user_id'      => $client->id,
        'checkin_date' => now()->subDays(10)->toDateString(),
    ]);

    $trigger = new SendBehavioralTrigger();
    $candidates = $trigger->getInactiveCandidates(7, 13);

    expect($candidates->contains('id', $client->id))->toBeTrue();
});

it('does not resend trigger already sent today', function () {
    $client = User::factory()->create(['role' => 'client']);
    AutoMessageLog::create([
        'user_id'      => $client->id,
        'trigger_type' => 'inactive_7d',
        'channel'      => 'email',
        'date_sent'    => today()->toDateString(),
    ]);

    $trigger = new SendBehavioralTrigger();
    $alreadySent = $trigger->alreadySentToday($client->id, 'inactive_7d');

    expect($alreadySent)->toBeTrue();
});

it('does not flag client as inactive if they have recent checkin', function () {
    $client = User::factory()->create(['role' => 'client', 'status' => 'activo']);
    Checkin::factory()->create([
        'user_id'      => $client->id,
        'checkin_date' => now()->subDays(3)->toDateString(),
    ]);

    $trigger = new SendBehavioralTrigger();
    $candidates = $trigger->getInactiveCandidates(7, 13);

    expect($candidates->contains('id', $client->id))->toBeFalse();
});

it('logs trigger as sent after processing', function () {
    $client = User::factory()->create(['role' => 'client']);

    $trigger = new SendBehavioralTrigger();
    $trigger->logTriggerSent($client->id, 'inactive_7d', 'email');

    expect(AutoMessageLog::where('user_id', $client->id)
        ->where('trigger_type', 'inactive_7d')
        ->whereDate('date_sent', today())
        ->exists()
    )->toBeTrue();
});
