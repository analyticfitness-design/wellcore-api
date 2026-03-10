<?php

namespace App\Http\Controllers\Api\V1\Coach;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckinReplyController extends Controller
{
    public function reply(Request $request, Checkin $checkin): JsonResponse
    {
        abort_if($checkin->user->coach_id !== $request->user()->id, 403);

        $validated = $request->validate(['reply' => 'required|string|max:2000']);

        $checkin->update([
            'coach_reply' => $validated['reply'],
            'replied_at'  => now(),
        ]);

        return response()->json(['data' => $checkin->fresh()]);
    }
}
