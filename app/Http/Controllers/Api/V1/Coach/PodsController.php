<?php

namespace App\Http\Controllers\Api\V1\Coach;

use App\Http\Controllers\Controller;
use App\Models\Pod;
use App\Models\PodMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PodsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $pods = Pod::where('coach_id', $request->user()->id)
            ->withCount('members')
            ->get();

        return response()->json(['pods' => $pods]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'description'  => 'nullable|string',
            'privacy'      => 'in:public,private',
            'max_members'  => 'integer|min:2|max:8',
            'client_ids'   => 'array|max:8',
            'client_ids.*' => 'integer|exists:users,id',
        ]);

        $maxMembers = $validated['max_members'] ?? 8;
        $clientIds  = $validated['client_ids'] ?? [];

        if (count($clientIds) > $maxMembers) {
            return response()->json([
                'message' => 'El número de clientes supera el máximo del pod.',
                'errors'  => ['client_ids' => ['Máximo ' . $maxMembers . ' miembros permitidos.']],
            ], 422);
        }

        $pod = Pod::create([
            'coach_id'    => $request->user()->id,
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'privacy'     => $validated['privacy'] ?? 'private',
            'max_members' => $maxMembers,
        ]);

        foreach ($clientIds as $clientId) {
            PodMember::create([
                'pod_id'  => $pod->id,
                'user_id' => $clientId,
            ]);
        }

        return response()->json(['pod' => $pod], 201);
    }
}
