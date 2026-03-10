<?php

namespace App\Http\Controllers\Api\V1\Coach;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coach = $request->user();

        $clients = User::with(['profile', 'xp'])
            ->where('coach_id', $coach->id)
            ->where('role', 'client')
            ->withCount(['checkins as sessions_last_7d' => function ($q) {
                $q->where('checkin_date', '>=', now()->subDays(7));
            }])
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'               => $c->id,
                'name'             => $c->name,
                'email'            => $c->email,
                'plan'             => $c->plan,
                'status'           => $c->status,
                'peso'             => $c->profile?->peso,
                'objetivo'         => $c->profile?->objetivo,
                'whatsapp'         => $c->profile?->whatsapp,
                'sessions_last_7d' => $c->sessions_last_7d,
                'xp_level'         => $c->xp?->level ?? 1,
                'streak_days'      => $c->xp?->streak_days ?? 0,
            ]);

        return response()->json(['clients' => $clients, 'count' => $clients->count()]);
    }

    public function show(Request $request, User $client): JsonResponse
    {
        abort_if($client->coach_id !== $request->user()->id, 403);

        return response()->json([
            'client'          => $client->load(['profile', 'xp']),
            'recent_checkins' => $client->checkins()->orderByDesc('checkin_date')->limit(4)->get(),
            'recent_metrics'  => $client->metrics()->orderByDesc('log_date')->limit(4)->get(),
        ]);
    }
}
