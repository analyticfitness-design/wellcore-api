<?php

namespace App\Http\Controllers\Api\V1\Coach;

use App\Http\Controllers\Controller;
use App\Services\CoachAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $data = CoachAnalyticsService::getDashboard($request->user());
        return response()->json($data);
    }
}
