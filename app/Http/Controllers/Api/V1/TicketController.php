<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /** POST /v1/tickets */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject'  => 'required|string|max:255',
            'category' => 'in:tecnico,pago,coaching,otro',
            'priority' => 'in:low,medium,high',
            'message'  => 'required|string|max:2000',
        ]);

        $ticket = SupportTicket::create([
            'user_id'  => $request->user()->id,
            'subject'  => $validated['subject'],
            'category' => $validated['category'] ?? 'otro',
            'priority' => $validated['priority'] ?? 'medium',
            'status'   => 'open',
        ]);

        $ticket->messages()->create([
            'user_id'  => $request->user()->id,
            'content'  => $validated['message'],
            'is_staff' => false,
        ]);

        return response()->json(['data' => $ticket->load('messages.user:id,name,role')], 201);
    }

    /** GET /v1/tickets */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SupportTicket::with(['user:id,name'])
            ->withCount('messages')
            ->orderByDesc('updated_at');

        if ($user->isClient()) {
            $query->where('user_id', $user->id);
        }

        return response()->json(['data' => $query->limit(50)->get()]);
    }

    /** GET /v1/tickets/{ticket} */
    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        $user = $request->user();

        if ($user->isClient() && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json([
            'data' => $ticket->load(['user:id,name', 'messages.user:id,name,role']),
        ]);
    }

    /** POST /v1/tickets/{ticket}/messages */
    public function addMessage(Request $request, SupportTicket $ticket): JsonResponse
    {
        $user = $request->user();

        if ($user->isClient() && $ticket->user_id !== $user->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message = $ticket->messages()->create([
            'user_id'  => $user->id,
            'content'  => $validated['content'],
            'is_staff' => $user->isAdmin() || $user->isCoach(),
        ]);

        if ($ticket->status === 'resolved' && $user->isClient()) {
            $ticket->update(['status' => 'open']);
        }

        if ($ticket->status === 'open' && ($user->isAdmin() || $user->isCoach())) {
            $ticket->update(['status' => 'in_progress']);
        }

        $ticket->touch();

        return response()->json(['data' => $message->load('user:id,name,role')], 201);
    }
}
