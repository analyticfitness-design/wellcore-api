<?php

namespace App\Http\Controllers\Api\V1\Coach;

use App\Http\Controllers\Controller;
use App\Models\ClientPlan;
use App\Models\User;
use Illuminate\Http\Request;

class ClientPlanController extends Controller
{
    public function show(Request $request, $clientId)
    {
        $client = User::findOrFail($clientId);
        $plan = ClientPlan::where('user_id', $clientId)->first();

        return response()->json(['data' => $plan]);
    }

    public function upsert(Request $request, $clientId)
    {
        $validated = $request->validate([
            'training_plan'      => 'nullable|string',
            'nutrition_plan'     => 'nullable|string',
            'habits_plan'        => 'nullable|string',
            'supplements_plan'   => 'nullable|string',
            'cycle_plan'         => 'nullable|string',
            'bloodwork_plan'     => 'nullable|string',
        ]);

        $validated['coach_id'] = $request->user()->id;

        $plan = ClientPlan::updateOrCreate(
            ['user_id' => $clientId],
            $validated
        );

        return response()->json(['data' => $plan]);
    }
}
