<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiometricController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'steps'        => 'nullable|integer|min:0',
            'sleep_hours'  => 'nullable|numeric|min:0|max:24',
            'heart_rate'   => 'nullable|integer|min:30|max:300',
            'weight_kg'    => 'nullable|numeric|min:20|max:500',
            'body_fat_pct' => 'nullable|numeric|min:1|max:70',
            'energy_level' => 'nullable|integer|min:1|max:10',
            'source'       => 'nullable|string|in:apple_health,google_fit,manual',
        ]);

        $log = $request->user()->biometricLogs()->updateOrCreate(
            ['log_date' => today()],
            $validated
        );

        return response()->json(
            ['biometric' => $log],
            $log->wasRecentlyCreated ? 201 : 200
        );
    }

    public function today(Request $request): JsonResponse
    {
        $log = $request->user()->biometricLogs()
            ->whereDate('log_date', today())
            ->first();

        return response()->json(['biometric' => $log]);
    }
}
