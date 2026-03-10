<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = ClientNotification::where('user_id', $request->user()->id)
            ->orderByRaw('read_at IS NULL DESC')
            ->orderByDesc('created_at')
            ->limit(50)->get();

        return response()->json([
            'data'         => $notifications,
            'unread_count' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        ClientNotification::where('user_id', $request->user()->id)
            ->findOrFail($id)->update(['read_at' => now()]);

        return response()->json(['message' => 'Marcada como leída']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        ClientNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Todas marcadas como leídas']);
    }
}
