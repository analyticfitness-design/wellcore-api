<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkoutLog;
use App\Models\VideoCheckin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller
{
    public function index(Request $request)
    {
        $totalClients = User::where('role', 'client')->count();
        $activeThisWeek = User::where('role', 'client')
            ->where('last_login_at', '>=', now()->subDays(7))
            ->count();

        $planDistribution = User::where('role', 'client')
            ->select('plan_type', DB::raw('count(*) as total'))
            ->groupBy('plan_type')
            ->get()
            ->pluck('total', 'plan_type');

        $workoutsThisWeek = WorkoutLog::where('logged_at', '>=', now()->subDays(7))->count();

        $videoCheckinsThisMonth = class_exists(VideoCheckin::class)
            ? VideoCheckin::where('created_at', '>=', now()->startOfMonth())->count()
            : 0;

        $newClientsThisMonth = User::where('role', 'client')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return response()->json([
            'data' => [
                'total_clients'          => $totalClients,
                'active_this_week'       => $activeThisWeek,
                'new_this_month'         => $newClientsThisMonth,
                'plan_distribution'      => $planDistribution,
                'workouts_this_week'     => $workoutsThisWeek,
                'video_checkins_month'   => $videoCheckinsThisMonth,
            ]
        ]);
    }
}
