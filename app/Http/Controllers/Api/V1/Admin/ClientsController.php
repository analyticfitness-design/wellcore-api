<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['profile', 'xp'])
            ->where('role', 'client');

        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
            );
        }

        $clients = $query->orderByDesc('created_at')->paginate(20);

        $kpis = [
            'total_activos' => User::where('role', 'client')->where('status', 'activo')->count(),
            'elite'         => User::where('role', 'client')->where('plan', 'elite')->count(),
            'metodo'        => User::where('role', 'client')->where('plan', 'metodo')->count(),
            'esencial'      => User::where('role', 'client')->where('plan', 'esencial')->count(),
        ];

        return response()->json([
            'clients' => $clients->items(),
            'total'   => $clients->total(),
            'kpis'    => $kpis,
        ]);
    }

    public function impersonate(Request $request, User $client): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403);
        abort_if($client->role !== 'client', 422, 'Solo se puede impersonar clientes');

        $token = $client->createToken(
            'impersonate',
            ['*'],
            now()->addHours(2)
        )->plainTextToken;

        return response()->json([
            'token'      => $token,
            'expires_in' => now()->addHours(2)->toISOString(),
            'client'     => [
                'id'   => $client->id,
                'name' => $client->name,
                'plan' => $client->plan,
            ],
        ]);
    }
}
