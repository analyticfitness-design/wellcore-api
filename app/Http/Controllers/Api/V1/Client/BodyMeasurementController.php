<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\BodyMeasurement;
use Illuminate\Http\Request;

class BodyMeasurementController extends Controller
{
    public function index(Request $request)
    {
        $measurements = BodyMeasurement::where('user_id', $request->user()->id)
            ->orderBy('logged_at', 'desc')
            ->limit(30)
            ->get();

        return response()->json(['data' => $measurements]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'weight'     => 'nullable|numeric|min:0|max:500',
            'waist'      => 'nullable|numeric|min:0|max:300',
            'hip'        => 'nullable|numeric|min:0|max:300',
            'chest'      => 'nullable|numeric|min:0|max:300',
            'arm'        => 'nullable|numeric|min:0|max:100',
            'thigh'      => 'nullable|numeric|min:0|max:200',
            'body_fat'   => 'nullable|numeric|min:0|max:100',
            'logged_at'  => 'nullable|date',
        ]);

        $validated['user_id']   = $request->user()->id;
        $validated['logged_at'] = $validated['logged_at'] ?? now()->toDateString();

        $measurement = BodyMeasurement::updateOrCreate(
            ['user_id' => $validated['user_id'], 'logged_at' => $validated['logged_at']],
            $validated
        );

        return response()->json(['data' => $measurement], 201);
    }
}
