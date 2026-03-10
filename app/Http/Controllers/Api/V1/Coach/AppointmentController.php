<?php

namespace App\Http\Controllers\Api\V1\Coach;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    /** GET /v1/appointments */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Appointment::with(['coach:id,name', 'client:id,name,plan'])
            ->where('status', '!=', 'cancelled')
            ->orderBy('scheduled_at');

        if ($user->isClient()) {
            $query->where('client_id', $user->id);
        } elseif ($user->isCoach()) {
            $query->where('coach_id', $user->id);
        }

        return response()->json(['data' => $query->limit(50)->get()]);
    }

    /** POST /v1/appointments */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id'        => 'required|exists:users,id',
            'scheduled_at'     => 'required|date|after:now',
            'duration_minutes' => 'integer|min:15|max:120',
            'type'             => 'in:video,presencial,chat',
            'notes'            => 'nullable|string|max:500',
            'meeting_url'      => 'nullable|url',
        ]);

        $appointment = Appointment::create(array_merge($validated, [
            'coach_id' => $request->user()->id,
            'status'   => 'pending',
        ]));

        return response()->json(['data' => $appointment->load(['coach:id,name', 'client:id,name'])], 201);
    }

    /** PUT /v1/appointments/{appointment} */
    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();

        if ($user->isClient() && $appointment->client_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ($user->isCoach() && $appointment->coach_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'status'      => 'in:pending,confirmed,cancelled,completed',
            'meeting_url' => 'nullable|url',
            'notes'       => 'nullable|string|max:500',
        ]);

        $appointment->update($validated);

        return response()->json(['data' => $appointment->fresh(['coach:id,name', 'client:id,name'])]);
    }

    /** DELETE /v1/appointments/{appointment} */
    public function destroy(Request $request, Appointment $appointment): JsonResponse
    {
        $user = $request->user();

        if ($user->isClient() && $appointment->client_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        if ($user->isCoach() && $appointment->coach_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $appointment->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Cita cancelada']);
    }
}
