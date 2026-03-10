<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile', 'xp');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'plan' => $user->plan,
                'status' => $user->status,
                'client_code' => $user->client_code,
                'coach_id' => $user->coach_id,
                'avatar_url' => $user->profile?->avatar_url,
                'xp' => $user->xp ? [
                    'xp_total' => $user->xp->xp_total,
                    'level' => $user->xp->level,
                    'streak_days' => $user->xp->streak_days,
                ] : null,
            ],
        ]);
    }
}
