<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\HabitLog;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    /**
     * GET /api/v1/habits/today
     * Returns today's habit state + streak + milestones.
     */
    public function today(Request $request)
    {
        $userId = $request->user()->id;
        $today = now()->toDateString();

        $logs = HabitLog::where('user_id', $userId)
            ->where('log_date', $today)
            ->get();

        $habits = [];
        foreach (['agua', 'sueno', 'nutricion', 'estres'] as $type) {
            $log = $logs->firstWhere('habit_type', $type);
            $habits[] = [
                'type' => $type,
                'value' => $log ? $log->value : 0,
            ];
        }

        $streak = HabitLog::calculateStreak($userId);

        $milestones = collect([7, 14, 21, 30])
            ->filter(fn ($days) => $streak >= $days)
            ->map(fn ($days) => ['days' => $days])
            ->values()
            ->toArray();

        return response()->json([
            'habits' => $habits,
            'streak' => $streak,
            'milestones' => $milestones,
        ]);
    }

    /**
     * POST /api/v1/habits/toggle
     * Toggle a habit for today.
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'habit_type' => 'required|in:agua,sueno,nutricion,estres',
            'value' => 'required|integer|in:0,1',
        ]);

        $userId = $request->user()->id;
        $today = now()->toDateString();

        $log = HabitLog::updateOrCreate(
            [
                'user_id' => $userId,
                'log_date' => $today,
                'habit_type' => $request->habit_type,
            ],
            [
                'value' => $request->value,
            ]
        );

        // Check if all 4 habits complete → award XP
        $allComplete = HabitLog::where('user_id', $userId)
            ->where('log_date', $today)
            ->where('value', 1)
            ->count() >= 4;

        return response()->json([
            'habit' => $log,
            'all_complete' => $allComplete,
        ]);
    }
}
