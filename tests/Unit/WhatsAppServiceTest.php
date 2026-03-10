<?php

use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Http;

it('normalizes Colombian phone number correctly', function () {
    $normalized = WhatsAppService::normalizePhone('3101234567');
    expect($normalized)->toBe('+573101234567');
});

it('keeps number with + prefix unchanged', function () {
    $normalized = WhatsAppService::normalizePhone('+573101234567');
    expect($normalized)->toBe('+573101234567');
});

it('returns false when WhatsApp not configured', function () {
    config(['services.whatsapp.phone_id' => '']);
    config(['services.whatsapp.token' => '']);

    $result = WhatsAppService::sendMessage('+573001234567', 'test_template', ['name']);
    expect($result)->toBeFalse();
});
