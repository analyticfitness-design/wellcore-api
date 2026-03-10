<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile', 'xp');

        return response()->json([
            'data' => [
                'user' => $user->only(['id', 'name', 'email', 'role', 'plan', 'status', 'client_code']),
                'profile' => $user->profile,
                'xp' => $user->xp,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'profile.peso' => 'sometimes|numeric',
            'profile.altura' => 'sometimes|numeric',
            'profile.objetivo' => 'sometimes|string|max:200',
            'profile.ciudad' => 'sometimes|string|max:100',
            'profile.whatsapp' => 'sometimes|string|max:20',
            'profile.bio' => 'sometimes|string|max:500',
        ]);

        if (isset($validated['name'])) {
            $request->user()->update(['name' => $validated['name']]);
        }

        if (isset($validated['profile'])) {
            $request->user()->profile()->updateOrCreate(
                ['user_id' => $request->user()->id],
                $validated['profile']
            );
        }

        return response()->json(['data' => $request->user()->fresh()->load('profile')]);
    }
}
