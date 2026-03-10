<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

it('processes Wompi webhook and activates pending user', function () {
    $user = User::factory()->create(['status' => 'pendiente', 'email' => 'payer@example.com']);

    $payload = [
        'event' => 'transaction.updated',
        'data'  => [
            'transaction' => [
                'status'          => 'APPROVED',
                'reference'       => 'RISE-' . $user->id . '-' . time(),
                'amount_in_cents' => 19500000,
                'currency'        => 'COP',
                'customer_data'   => ['email' => $user->email],
            ],
        ],
    ];

    $this->postJson('/api/v1/payments/wompi/webhook', $payload)
        ->assertOk()
        ->assertJson(['ok' => true]);

    expect($user->fresh()->status)->toBe('activo');
    $this->assertDatabaseHas('payments', [
        'user_id' => $user->id,
        'status'  => 'APPROVED',
    ]);
});

it('ignores non-transaction-updated events', function () {
    $this->postJson('/api/v1/payments/wompi/webhook', [
        'event' => 'nequi.token.updated',
        'data'  => [],
    ])->assertOk()->assertJson(['ok' => true]);
});

it('rejects webhook with invalid signature when secret is configured', function () {
    Config::set('services.wompi.integrity_secret', 'test_integrity_secret');

    $this->postJson('/api/v1/payments/wompi/webhook', [
        'event' => 'transaction.updated',
        'data'  => [
            'transaction' => [
                'id'              => 'txn-abc123',
                'status'          => 'APPROVED',
                'amount_in_cents' => 19500000,
                'currency'        => 'COP',
                'signature'       => ['checksum' => 'invalid_checksum_value'],
                'customer_data'   => ['email' => 'payer@example.com'],
            ],
        ],
    ])->assertStatus(400)->assertJson(['ok' => false]);
});

it('accepts webhook with valid HMAC-SHA256 signature', function () {
    $secret = 'test_integrity_secret';
    Config::set('services.wompi.integrity_secret', $secret);

    $user = User::factory()->create(['status' => 'pendiente', 'email' => 'signed@example.com']);

    $id          = 'txn-valid-001';
    $amountCents = 19500000;
    $currency    = 'COP';
    $status      = 'APPROVED';
    $checksum    = hash('sha256', "{$id}{$amountCents}{$currency}{$status}{$secret}");

    $this->postJson('/api/v1/payments/wompi/webhook', [
        'event' => 'transaction.updated',
        'data'  => [
            'transaction' => [
                'id'              => $id,
                'status'          => $status,
                'amount_in_cents' => $amountCents,
                'currency'        => $currency,
                'reference'       => 'REF-signed',
                'signature'       => ['checksum' => $checksum],
                'customer_data'   => ['email' => $user->email],
            ],
        ],
    ])->assertOk()->assertJson(['ok' => true]);

    expect($user->fresh()->status)->toBe('activo');
});

it('rejects webhook with missing checksum when secret is configured', function () {
    Config::set('services.wompi.integrity_secret', 'test_integrity_secret');

    $this->postJson('/api/v1/payments/wompi/webhook', [
        'event' => 'transaction.updated',
        'data'  => [
            'transaction' => [
                'id'              => 'txn-no-sig',
                'status'          => 'APPROVED',
                'amount_in_cents' => 1000,
                'currency'        => 'COP',
                'customer_data'   => ['email' => 'nosig@example.com'],
            ],
        ],
    ])->assertStatus(400);
});

it('ignores declined transactions without activating user', function () {
    $user = User::factory()->create(['status' => 'pendiente', 'email' => 'declined@example.com']);

    $this->postJson('/api/v1/payments/wompi/webhook', [
        'event' => 'transaction.updated',
        'data'  => [
            'transaction' => [
                'status'          => 'DECLINED',
                'reference'       => 'ref-test-declined',
                'amount_in_cents' => 19500000,
                'currency'        => 'COP',
                'customer_data'   => ['email' => $user->email],
            ],
        ],
    ])->assertOk();

    expect($user->fresh()->status)->toBe('pendiente');
});
