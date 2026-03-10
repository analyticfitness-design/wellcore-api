<?php

namespace App\Http\Controllers\Api\V1\Coach;

use App\Http\Controllers\Controller;
use App\Models\CoachNote;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotesController extends Controller
{
    public function store(Request $request, User $client): JsonResponse
    {
        $coach = $request->user();
        abort_if($client->coach_id !== $coach->id, 403);

        $validated = $request->validate([
            'content'   => 'required|string|max:2000',
            'note_type' => 'in:general,seguimiento,alerta,logro',
        ]);

        $note = CoachNote::create([
            'coach_id'  => $coach->id,
            'user_id'   => $client->id,
            'content'   => $validated['content'],
            'note_type' => $validated['note_type'] ?? 'general',
        ]);

        return response()->json(['data' => $note], 201);
    }

    public function index(Request $request, User $client): JsonResponse
    {
        abort_if($client->coach_id !== $request->user()->id, 403);

        $notes = CoachNote::where('user_id', $client->id)
            ->where('coach_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json(['data' => $notes]);
    }
}
