<?php

namespace App\Http\Controllers\Api\V1\Challenges;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function index(): JsonResponse
    {
        $challenges = Challenge::where('is_active', true)
            ->where('end_date', '>=', today())
            ->orderBy('start_date')
            ->get();

        return response()->json(['data' => $challenges]);
    }

    public function leaderboard(Request $request, Challenge $challenge): JsonResponse
    {
        $top = ChallengeParticipant::with('user:id,name')
            ->where('challenge_id', $challenge->id)
            ->orderByDesc('current_value')
            ->limit(20)
            ->get()
            ->map(fn ($p, $i) => [
                'rank'          => $i + 1,
                'user_name'     => $p->user->name,
                'current_value' => $p->current_value,
            ]);

        return response()->json([
            'data'               => $top,
            'total_participants' => ChallengeParticipant::where('challenge_id', $challenge->id)->count(),
        ]);
    }

    public function join(Request $request, Challenge $challenge): JsonResponse
    {
        $participant = ChallengeParticipant::firstOrCreate([
            'challenge_id' => $challenge->id,
            'user_id'      => $request->user()->id,
        ]);

        return response()->json(['data' => $participant], 201);
    }
}
