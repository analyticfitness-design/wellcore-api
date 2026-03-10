<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\Metric;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetricController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $metrics = $request->user()
            ->metrics()
            ->orderByDesc('log_date')
            ->limit($request->integer('limit', 12))
            ->get();

        return response()->json(['data' => $metrics]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'peso' => 'nullable|numeric|min:20|max:300',
            'porcentaje_grasa' => 'nullable|numeric|min:0|max:100',
            'porcentaje_musculo' => 'nullable|numeric|min:0|max:100',
            'pecho' => 'nullable|numeric',
            'cintura' => 'nullable|numeric',
            'cadera' => 'nullable|numeric',
            'muslo' => 'nullable|numeric',
            'brazo' => 'nullable|numeric',
            'notas' => 'nullable|string|max:500',
        ]);

        $metric = $request->user()->metrics()->updateOrCreate(
            ['log_date' => today()],
            $validated
        );

        return response()->json(['data' => $metric, 'message' => 'Métricas guardadas'], 201);
    }
}
