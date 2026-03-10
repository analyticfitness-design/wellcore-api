<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Credenciales incorrectas'], 401);
        }

        if ($user->status !== 'activo') {
            return response()->json(['error' => 'Cuenta inactiva o pendiente'], 401);
        }

        // Single session for admins (mirrors PHP auth.php behavior)
        if ($user->isAdmin()) {
            $user->tokens()->delete();
        }

        $rememberMe = $request->boolean('remember_me');
        $expiresAt = $rememberMe
            ? now()->addDays(30)
            : ($user->isAdmin() ? now()->addHours(72) : now()->addHours(168));

        $token = $user->createToken('app', ['*'], $expiresAt)->plainTextToken;

        return response()->json([
            'token' => $token,
            'expires_in' => $expiresAt->toISOString(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'plan' => $user->plan,
                'status' => $user->status,
                'client_code' => $user->client_code,
                'coach_id' => $user->coach_id,
            ],
        ]);
    }
}
