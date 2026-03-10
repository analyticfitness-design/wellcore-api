<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientXp;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GamificationController extends Controller
{
    public function __construct(private GamificationService $gamification) {}

    /** GET /v1/gamification/leaderboard */
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

    /** GET /v1/gamification/my-stats */
    public function myStats(Request $request): JsonResponse
    {
        $stats = $this->gamification->getStats($request->user());
        return response()->json(['stats' => $stats]);
    }

    /** GET /v1/gamification/achievements */
    public function achievements(Request $request): JsonResponse
    {
        $achievements = $this->gamification->getAchievements($request->user());
        return response()->json(['achievements' => $achievements]);
    }

    /** POST /v1/gamification/earn-xp */
    public function earnXp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_type' => 'required|string|in:' . implode(',', array_keys(GamificationService::XP_MAP)),
            'amount'     => 'nullable|integer|min:1|max:1000',
        ]);

        $xp = $this->gamification->earnXp(
            $request->user(),
            $validated['event_type'],
            $validated['amount'] ?? null
        );

        return response()->json([
            'message'   => 'XP earned',
            'xp_gained' => GamificationService::XP_MAP[$validated['event_type']] ?? $validated['amount'],
            'new_total'  => $xp->xp_total,
            'new_level'  => $xp->level,
        ]);
    }
}
