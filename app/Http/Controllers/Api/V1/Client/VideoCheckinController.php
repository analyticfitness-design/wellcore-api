<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\VideoCheckin;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VideoCheckinController extends Controller
{
    private const PLAN_LIMITS = ['esencial' => 2, 'metodo' => 5, 'elite' => PHP_INT_MAX];

    public function __construct(private GamificationService $gamification)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $checkins = VideoCheckin::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($checkins);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $user->plan ?? 'esencial';
        $limit = self::PLAN_LIMITS[$plan] ?? 2;

        $used = VideoCheckin::where('user_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        if ($used >= $limit) {
            throw ValidationException::withMessages([
                'limit' => ["Tu plan {$plan} permite " . ($limit === PHP_INT_MAX ? 'ilimitados' : $limit) . " video check-ins por mes. Ya usaste {$used}."],
            ]);
        }

        $validated = $request->validate([
            'video_url'        => ['required', 'url', 'max:500'],
            'thumbnail_url'    => ['nullable', 'url', 'max:500'],
            'title'            => ['nullable', 'string', 'max:120'],
            'description'      => ['nullable', 'string', 'max:500'],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:7200'],
            'type'             => ['nullable', 'in:entrenamiento,nutricion,progreso,motivacional'],
        ]);

        $checkin = VideoCheckin::create(['user_id' => $user->id, ...$validated]);

        $this->gamification->earnXp($user, 'video_checkin');

        return response()->json([
            'data'      => $checkin,
            'remaining' => $limit === PHP_INT_MAX ? 'ilimitado' : $limit - $used - 1,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $checkin = VideoCheckin::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json(['data' => $checkin]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        VideoCheckin::where('user_id', $request->user()->id)->findOrFail($id)->delete();

        return response()->json(['message' => 'Video eliminado']);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $plan = $user->plan ?? 'esencial';
        $limit = self::PLAN_LIMITS[$plan] ?? 2;

        $used = VideoCheckin::where('user_id', $user->id)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        return response()->json([
            'plan'      => $plan,
            'limit'     => $limit === PHP_INT_MAX ? null : $limit,
            'used'      => $used,
            'remaining' => $limit === PHP_INT_MAX ? null : max(0, $limit - $used),
            'unlimited' => $limit === PHP_INT_MAX,
        ]);
    }
}
