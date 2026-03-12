<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlanFeatureController extends Controller
{
    /**
     * GET /api/v1/plans/features
     * Returns the feature gating matrix — configurable from server.
     */
    public function index(Request $request)
    {
        return response()->json([
            'features' => [
                'plan_entrenamiento' => ['esencial', 'metodo', 'elite', 'rise'],
                'plan_nutricion' => ['metodo', 'elite', 'rise'],
                'plan_habitos' => ['elite', 'rise'],
                'plan_suplementacion' => ['esencial', 'metodo', 'elite', 'rise'],
                'checkin_semanal' => ['elite'],
                'nutricion_ia' => ['metodo', 'elite'],
                'video_checkins' => ['elite'],
                'photo_review' => ['metodo', 'elite'],
                'calc_nutricion' => ['metodo', 'elite'],
            ],
            'daily_limits' => [
                'nutricion_ia' => [
                    'metodo' => 5,
                    'elite' => 10,
                ],
            ],
            'sla_hours' => [
                'esencial' => 48,
                'metodo' => 24,
                'elite' => 8,
                'rise' => 48,
            ],
        ]);
    }
}
