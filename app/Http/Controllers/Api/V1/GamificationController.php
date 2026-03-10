<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientXp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GamificationController extends Controller
{
    /**
     * Return the XP leaderboard for the coach group of the authenticated user.
     *
     * Results are cached for 5 minutes (300 seconds) per coach group.
     * In tests the array cache driver is used, so no Redis is required.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $coachId  = $request->user()->coach_id ?? $request->user()->id;
        $cacheKey = "leaderboard.{$coachId}.week";

        $data = Cache::remember($cacheKey, 300, function () use ($coachId) {
            return ClientXp::whereHas('user', fn ($q) => $q->where('coach_id', $coachId))
                ->with('user:id,name,plan')
                ->orderByDesc('xp_total')
                ->limit(20)
                ->get()
                ->map(fn ($xp) => [
                    'user_id'     => $xp->user_id,
                    'name'        => $xp->user?->name,
                    'plan'        => $xp->user?->plan,
                    'xp_total'    => $xp->xp_total,
                    'level'       => $xp->level,
                    'streak_days' => $xp->streak_days,
                ]);
        });

        return response()->json(['leaderboard' => $data]);
    }
}
