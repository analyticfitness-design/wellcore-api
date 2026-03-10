<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    const META_API = 'https://graph.facebook.com/v19.0';

    public static function sendMessage(string $phone, string $template, array $params = []): bool
    {
        $phone = self::normalizePhone($phone);

        $phoneId = config('services.whatsapp.phone_id');
        $token   = config('services.whatsapp.token');

        if (empty($phoneId) || empty($token)) {
            Log::warning("WhatsApp not configured — skipping message to {$phone}");
            return false;
        }

        $response = Http::withToken($token)
            ->post(self::META_API . "/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => $phone,
                'type'              => 'template',
                'template'          => [
                    'name'       => $template,
                    'language'   => ['code' => 'es'],
                    'components' => [
                        [
                            'type'       => 'body',
                            'parameters' => array_map(
                                fn ($p) => ['type' => 'text', 'text' => (string) $p],
                                $params
                            ),
                        ],
                    ],
                ],
            ]);

        if (! $response->successful()) {
            Log::error("WhatsApp API error: " . $response->body());
            return false;
        }

        return true;
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (! str_starts_with($phone, '+')) {
            $phone = '+57' . $phone; // Colombia default
        }
        return $phone;
    }

    public static function sendWelcomeMessage(string $phone, string $name): bool
    {
        return self::sendMessage($phone, 'wellcore_welcome', [$name]);
    }

    public static function sendInactivityReminder(string $phone, string $name, int $days): bool
    {
        return self::sendMessage($phone, 'wellcore_inactive', [$name, (string) $days]);
    }
}
