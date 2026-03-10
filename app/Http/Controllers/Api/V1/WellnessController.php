<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WellnessController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'energy_level'  => 'nullable|integer|min:1|max:10',
            'stress_level'  => 'nullable|integer|min:1|max:10',
            'sleep_hours'   => 'nullable|numeric|min:0|max:24',
            'sleep_quality' => 'nullable|integer|min:1|max:10',
            'mood'          => 'nullable|integer|min:1|max:10',
            'notes'         => 'nullable|string',
        ]);

        $log = $request->user()
            ->wellnessLogs()
            ->updateOrCreate(['log_date' => today()], $validated);

        $status = $log->wasRecentlyCreated ? 201 : 200;

        return response()->json(['wellness' => $log], $status);
    }

    public function today(Request $request): JsonResponse
    {
        $log = $request->user()
            ->wellnessLogs()
            ->whereDate('log_date', today())
            ->first();

        return response()->json(['wellness' => $log]);
    }
}
