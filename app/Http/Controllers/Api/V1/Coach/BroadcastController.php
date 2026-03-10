<?php

namespace App\Http\Controllers\Api\V1\Coach;

use App\Http\Controllers\Controller;
use App\Models\CoachBroadcast;
use App\Models\ClientNotification;
use App\Models\User;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        $broadcasts = CoachBroadcast::where('coach_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $broadcasts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'   => 'required|string|max:255',
            'message' => 'required|string',
            'target'  => 'nullable|in:all,esencial,metodo,elite',
        ]);

        $validated['coach_id'] = $request->user()->id;
        $validated['target']   = $validated['target'] ?? 'all';

        $broadcast = CoachBroadcast::create($validated);

        // Send notification to target clients
        $clientsQuery = User::where('role', 'client');
        if ($validated['target'] !== 'all') {
            $clientsQuery->where('plan_type', $validated['target']);
        }
        $clients = $clientsQuery->get();

        foreach ($clients as $client) {
            ClientNotification::send(
                $client->id,
                'broadcast',
                $validated['title'],
                $validated['message']
            );
        }

        return response()->json([
            'data' => $broadcast,
            'sent_to' => $clients->count()
        ], 201);
    }
}
