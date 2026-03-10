<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AiCoachService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiCoachController extends Controller {
    public function __construct(private AiCoachService $ai) {}

    // GET /ai/conversations
    public function index(Request $request): JsonResponse {
        $convos = $this->ai->getConversations($request->user());
        return response()->json(['data' => $convos]);
    }

    // GET /ai/conversations/{id}
    public function show(Request $request, int $id): JsonResponse {
        $convo = $this->ai->getConversation($request->user(), $id);
        return response()->json(['data' => $convo]);
    }

    // POST /ai/send
    public function send(Request $request): JsonResponse {
        $validated = $request->validate([
            'message'         => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'integer'],
        ]);

        $result = $this->ai->sendMessage(
            $request->user(),
            $validated['message'],
            $validated['conversation_id'] ?? null
        );

        return response()->json(['data' => $result]);
    }

    // DELETE /ai/conversations/{id}
    public function destroy(Request $request, int $id): JsonResponse {
        $convo = $this->ai->getConversation($request->user(), $id);
        $convo->delete();
        return response()->json(['message' => 'Conversación eliminada']);
    }
}
