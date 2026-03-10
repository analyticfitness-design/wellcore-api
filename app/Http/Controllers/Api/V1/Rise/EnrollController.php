<?php

namespace App\Http\Controllers\Api\V1\Rise;

use App\Http\Controllers\Controller;
use App\Models\RiseProgram;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EnrollController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:100',
            'email'             => 'required|email|unique:users,email',
            'password'          => 'required|string|min:6',
            'experience_level'  => 'nullable|in:principiante,intermedio,avanzado',
            'training_location' => 'nullable|in:gym,home,hybrid',
            'gender'            => 'nullable|in:male,female,other',
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'role'        => 'client',
            'plan'        => 'rise',
            'status'      => 'activo',
            'client_code' => 'rise-' . strtoupper(Str::random(6)),
        ]);

        $program = RiseProgram::create([
            'user_id'            => $user->id,
            'start_date'         => today(),
            'end_date'           => today()->addDays(30),
            'experience_level'   => $validated['experience_level'] ?? null,
            'training_location'  => $validated['training_location'] ?? null,
            'gender'             => $validated['gender'] ?? null,
        ]);

        return response()->json([
            'client'  => [
                'id'   => $user->id,
                'code' => $user->client_code,
                'name' => $user->name,
            ],
            'program' => [
                'id'         => $program->id,
                'start_date' => $program->start_date->toDateString(),
                'end_date'   => $program->end_date->toDateString(),
            ],
        ], 201);
    }
}
