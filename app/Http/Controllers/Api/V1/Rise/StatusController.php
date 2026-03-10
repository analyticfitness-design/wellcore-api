<?php

namespace App\Http\Controllers\Api\V1\Rise;

use App\Http\Controllers\Controller;
use App\Models\RiseProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $program = RiseProgram::where('user_id', $request->user()->id)
            ->whereIn('status', ['active', 'expired'])
            ->latest()
            ->firstOrFail();

        $daysElapsed   = (int) today()->diffInDays($program->start_date);
        $daysRemaining = (int) $program->end_date->diffInDays(today(), false);
        $expired       = today()->gt($program->end_date);

        if ($expired && $program->status === 'active') {
            $program->update(['status' => 'expired']);
        }

        return response()->json([
            'active'         => ! $expired,
            'start_date'     => $program->start_date->toDateString(),
            'end_date'       => $program->end_date->toDateString(),
            'days_elapsed'   => $daysElapsed,
            'days_remaining' => max(0, $daysRemaining),
            'expired'        => $expired,
            'message'        => "Día {$daysElapsed} de 30 — ¡Vas muy bien!",
        ]);
    }
}
