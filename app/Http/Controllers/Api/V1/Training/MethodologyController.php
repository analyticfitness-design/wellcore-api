<?php

namespace App\Http\Controllers\Api\V1\Training;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAiPlan;
use App\Models\AssignedPlan;
use App\Models\User;
use App\Services\TrainingMethodologyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MethodologyController extends Controller
{
    /**
     * Listar todas las metodologías disponibles con su info completa.
     * El coach las ve y elige antes de generar el plan.
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('category');

        $methodologies = $category
            ? TrainingMethodologyService::getByCategory($category)
            : TrainingMethodologyService::getCatalog();

        return response()->json([
            'methodologies' => $methodologies,
            'total'         => count($methodologies),
            'categories'    => TrainingMethodologyService::getCategories(),
        ]);
    }

    /**
     * Ver detalle de una metodología específica.
     */
    public function show(string $id): JsonResponse
    {
        $methodology = TrainingMethodologyService::getById($id);

        if (! $methodology) {
            return response()->json(['error' => 'Metodología no encontrada'], 404);
        }

        return response()->json(['methodology' => $methodology]);
    }

    /**
     * El coach elige una metodología y la asigna a un cliente para generar el plan.
     * El plan se genera async via Job.
     */
    public function generateForClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id'      => 'required|exists:users,id',
            'methodology_id' => 'required|string',
            'intake'         => 'sometimes|array',
        ]);

        $coach       = $request->user();
        $client      = User::findOrFail($validated['client_id']);
        $methodology = TrainingMethodologyService::getById($validated['methodology_id']);

        if (! $methodology) {
            return response()->json(['error' => 'Metodología no válida'], 422);
        }

        // Solo coaches pueden generar planes para sus clientes
        if ($client->coach_id !== $coach->id && ! $coach->isAdmin()) {
            return response()->json(['error' => 'No tienes acceso a este cliente'], 403);
        }

        $intake = $validated['intake'] ?? [];

        // Preparar metadata para el prompt
        $methodology['key_principles_text'] = implode("\n", array_map(
            fn ($p) => "- {$p}",
            $methodology['key_principles']
        ));
        $methodology['split_text'] = implode(' → ', $methodology['split']);

        // Despachar el Job async — genera el plan con Claude usando la metodología elegida
        GenerateAiPlan::dispatch($client, 'entrenamiento', $intake, $methodology);

        return response()->json([
            'message'     => "Plan generando con metodología {$methodology['name']}",
            'methodology' => [
                'id'   => $methodology['id'],
                'name' => $methodology['name'],
            ],
            'client_id'  => $client->id,
            'status'     => 'generating',
            'check_at'   => now()->addSeconds(30)->toISOString(),
        ], 202);
    }

    /**
     * Ver el plan generado para un cliente.
     */
    public function getClientPlan(Request $request, int $clientId): JsonResponse
    {
        $coach  = $request->user();
        $client = User::findOrFail($clientId);

        if ($client->coach_id !== $coach->id && ! $coach->isAdmin()) {
            return response()->json(['error' => 'No tienes acceso a este cliente'], 403);
        }

        $plan = AssignedPlan::where('user_id', $clientId)
            ->where('plan_type', 'entrenamiento')
            ->where('active', true)
            ->latest()
            ->first();

        if (! $plan) {
            return response()->json(['plan' => null, 'status' => 'not_generated']);
        }

        return response()->json([
            'plan'       => $plan,
            'status'     => 'ready',
            'updated_at' => $plan->updated_at->toISOString(),
        ]);
    }
}
