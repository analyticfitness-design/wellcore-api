<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    /**
     * GET /api/v1/admin/activity-log
     * Returns a paginated admin activity log.
     */
    public function index(Request $request)
    {
        $query = DB::table('admin_activity_log')
            ->join('users', 'admin_activity_log.user_id', '=', 'users.id')
            ->select(
                'admin_activity_log.*',
                'users.name as user_name',
                'users.email as user_email',
                'users.role as user_role'
            )
            ->orderByDesc('admin_activity_log.created_at');

        // Optional filters
        if ($type = $request->query('type')) {
            $query->where('admin_activity_log.action_type', $type);
        }

        $perPage = min((int) $request->query('per_page', 20), 50);
        $results = $query->paginate($perPage);

        return response()->json($results);
    }
}
