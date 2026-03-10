<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NutritionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'calories_target' => 'nullable|integer|min:0',
            'calories_actual' => 'nullable|integer|min:0',
            'protein_g'       => 'nullable|integer|min:0',
            'carbs_g'         => 'nullable|integer|min:0',
            'fat_g'           => 'nullable|integer|min:0',
            'adherence_pct'   => 'nullable|integer|min:0|max:100',
            'meal_photo_url'  => 'nullable|url',
            'notes'           => 'nullable|string',
        ]);

        $log = $request->user()
            ->nutritionLogs()
            ->updateOrCreate(['log_date' => today()], $validated);

        $status = $log->wasRecentlyCreated ? 201 : 200;

        // Auto-XP
        app(\App\Services\GamificationService::class)->earnXp($request->user(), 'nutrition_log');

        return response()->json(['nutrition' => $log], $status);
    }

    public function today(Request $request): JsonResponse
    {
        $log = $request->user()
            ->nutritionLogs()
            ->whereDate('log_date', today())
            ->first();

        return response()->json(['nutrition' => $log]);
    }
}
