<?php

use App\Models\Referral;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('external coach can only see their own clients', function () {
    $externalCoach = User::factory()->create(['role' => 'coach_external']);
    $myClient = User::factory()->create(['coach_id' => $externalCoach->id, 'role' => 'client']);
    $otherClient = User::factory()->create(['role' => 'client']); // sin coach_id

    Sanctum::actingAs($externalCoach);

    $response = $this->getJson('/api/v1/coach/clients')->assertOk();
    $clientIds = collect($response->json('clients'))->pluck('id');

    expect($clientIds->contains($myClient->id))->toBeTrue()
        ->and($clientIds->contains($otherClient->id))->toBeFalse();
});

it('generates unique referral code for user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/referral/my-link')->assertOk();

    expect($response->json('code'))->toHaveLength(8)
        ->and($response->json('link'))->toContain('/r/');
});

it('returns same referral code on repeated requests', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $code1 = $this->getJson('/api/v1/referral/my-link')->json('code');
    $code2 = $this->getJson('/api/v1/referral/my-link')->json('code');

    expect($code1)->toBe($code2);
});

it('requires auth to get referral link', function () {
    $this->getJson('/api/v1/referral/my-link')->assertUnauthorized();
});
