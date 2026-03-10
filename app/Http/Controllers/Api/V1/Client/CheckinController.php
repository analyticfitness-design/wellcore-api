<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Events\NewCheckinReceived;
use App\Http\Controllers\Controller;
use App\Models\Checkin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $checkins = $request->user()
            ->checkins()
            ->orderByDesc('checkin_date')
            ->limit($request->integer('limit', 8))
            ->get();

        return response()->json(['data' => $checkins]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bienestar' => 'nullable|integer|min:1|max:10',
            'dias_entrenados' => 'nullable|integer|min:0|max:7',
            'nutricion' => 'nullable|in:Si,No,Parcial',
            'comentario' => 'nullable|string|max:1000',
        ]);

        $checkin = $request->user()->checkins()->updateOrCreate(
            ['checkin_date' => today()],
            array_merge($validated, ['week' => now()->format('Y-\WW')])
        );

        event(new NewCheckinReceived($checkin->load('user')));

        // Auto-XP
        app(\App\Services\GamificationService::class)->earnXp($request->user(), 'checkin');
        app(\App\Services\GamificationService::class)->updateStreak($request->user());

        return response()->json(['data' => $checkin, 'message' => 'Check-in guardado'], 201);
    }
}
