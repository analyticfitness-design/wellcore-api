<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\ClientPlan;
use Illuminate\Http\Request;

class MyPlanController extends Controller
{
    public function show(Request $request)
    {
        $plan = ClientPlan::where('user_id', $request->user()->id)->first();

        if (!$plan) {
            return response()->json(['data' => null], 200);
        }

        return response()->json(['data' => $plan]);
    }
}
