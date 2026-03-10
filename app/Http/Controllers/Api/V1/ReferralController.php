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

    public function myReferrals(Request $request): JsonResponse
    {
        $user = $request->user();

        // Ensure user has a referral code
        $referral = Referral::firstOrCreate(
            ['referrer_id' => $user->id],
            ['code' => strtoupper(Str::random(8))]
        );

        // Get all conversions with referred user details
        $referrals = $referral->conversions()
            ->with('referredUser:id,name,email,created_at')
            ->orderByDesc('created_at')
            ->get();

        $stats = [
            'total'      => $referrals->count(),
            'converted'  => $referrals->where('status', 'converted')->count(),
            'pending'    => $referrals->where('status', 'pending')->count(),
            'xp_earned'  => XpEvent::where('user_id', $user->id)
                ->where('event_type', 'referral')
                ->sum('xp_gained') ?? 0,
        ];

        return response()->json([
            'data' => [
                'referral_code' => $referral->code,
                'referral_link' => url("/r/{$referral->code}"),
                'stats'         => $stats,
                'referrals'     => $referrals->map(fn($ref) => [
                    'id'               => $ref->id,
                    'referred_user_id' => $ref->referred_user_id,
                    'status'           => $ref->status,
                    'referred_at'      => $ref->created_at,
                    'user'             => $ref->referredUser,
                ]),
            ]
        ]);
    }
}
