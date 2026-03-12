<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\HabitLog;
use App\Models\XpEvent;
use App\Models\CommunityPost;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard/summary
     * Consolidated dashboard data for the client.
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;

        // Weeks active
        $weeksActive = 0;
        if ($user->fecha_inicio) {
            $weeksActive = (int) Carbon::parse($user->fecha_inicio)->diffInWeeks(now());
        }

        // Adherence: checkins completed / weeks active
        $totalCheckins = Checkin::where('user_id', $userId)->count();
        $adherencePct = $weeksActive > 0
            ? min(100, round(($totalCheckins / $weeksActive) * 100))
            : 0;

        // Activity feed: last 10 events from xp_events + checkins + community posts
        $feed = collect();

        $xpEvents = XpEvent::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($e) => [
                'type' => 'xp',
                'title' => $e->description ?? 'XP ganado',
                'subtitle' => "+{$e->xp_gained} XP",
                'date' => $e->created_at->toIso8601String(),
            ]);
        $feed = $feed->merge($xpEvents);

        $recentCheckins = Checkin::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(fn ($c) => [
                'type' => 'checkin',
                'title' => 'Check-in completado',
                'subtitle' => "Bienestar {$c->bienestar}/10",
                'date' => $c->created_at->toIso8601String(),
            ]);
        $feed = $feed->merge($recentCheckins);

        $recentPosts = CommunityPost::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get()
            ->map(fn ($p) => [
                'type' => 'community',
                'title' => 'Post en comunidad',
                'subtitle' => mb_substr($p->content, 0, 40) . '...',
                'date' => $p->created_at->toIso8601String(),
            ]);
        $feed = $feed->merge($recentPosts);

        // Sort by date descending, take 10
        $sortedFeed = $feed->sortByDesc('date')->take(10)->values();

        return response()->json([
            'plan_name' => $user->plan ?? 'esencial',
            'weeks_active' => $weeksActive,
            'adherence_pct' => $adherencePct,
            'next_delivery' => null, // TODO: implement from coach schedule
            'phase' => 'ejecucion', // TODO: derive from plan timeline
            'renewal_due' => null, // TODO: from payments table
            'activity_feed' => $sortedFeed,
        ]);
    }
}
