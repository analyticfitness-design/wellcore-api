<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoachTipsController extends Controller
{
    /**
     * GET /api/v1/coach-tips
     * Returns audio and video tips organized by type.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Audio tips — sorted by newest first
        $audioTips = DB::table('coach_tips')
            ->where('type', 'audio')
            ->where('active', true)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($t) => [
                'id'               => $t->id,
                'title'            => $t->title,
                'description'      => $t->description,
                'category'         => $t->category,
                'audio_url'        => $t->media_url,
                'duration_seconds' => $t->duration_seconds,
                'created_at'       => $t->created_at,
            ]);

        // Video tips — sorted by newest first
        $videoTips = DB::table('coach_tips')
            ->where('type', 'video')
            ->where('active', true)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($t) => [
                'id'               => $t->id,
                'title'            => $t->title,
                'description'      => $t->description,
                'category'         => $t->category,
                'video_url'        => $t->media_url,
                'thumbnail_url'    => $t->thumbnail_url,
                'duration_seconds' => $t->duration_seconds,
                'created_at'       => $t->created_at,
            ]);

        return response()->json([
            'audio' => $audioTips,
            'video' => $videoTips,
        ]);
    }
}
