<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\XpEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    public function myLink(Request $request): JsonResponse
    {
        $user = $request->user();
        $referral = Referral::firstOrCreate(
            ['referrer_id' => $user->id],
            ['code' => strtoupper(Str::random(8))]
        );

        return response()->json([
            'code'           => $referral->code,
            'link'           => url("/r/{$referral->code}"),
            'total_referred' => $referral->conversions()->count(),
            'conversions'    => $referral->conversions()->where('status', 'converted')->count(),
            'xp_earned'      => XpEvent::where('user_id', $user->id)
                ->where('event_type', 'referral')
                ->sum('xp_gained'),
        ]);
    }
}
