<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    /**
     * Register or refresh an FCM device token for the authenticated user.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string|max:500',
            'platform'  => 'required|in:android,ios,web',
        ]);

        DeviceToken::updateOrCreate(
            [
                'user_id'   => $request->user()->id,
                'fcm_token' => $request->fcm_token,
            ],
            [
                'platform'       => $request->platform,
                'last_active_at' => now(),
            ]
        );

        return response()->json(['message' => 'Token registered']);
    }

    /**
     * Remove a device token (e.g., on logout).
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        DeviceToken::where('user_id', $request->user()->id)
            ->where('fcm_token', $request->fcm_token)
            ->delete();

        return response()->json(['message' => 'Token removed']);
    }
}
