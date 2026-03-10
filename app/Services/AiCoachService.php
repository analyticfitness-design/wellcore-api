<?php
namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AiCoachService {
    private const RATE_LIMIT_PER_HOUR = 30;
    private const SYSTEM_PROMPT = <<<PROMPT
Eres el Coach AI de WellCore Fitness, un asistente de bienestar y fitness personalizado.
Eres empático, motivador y basas tus consejos en evidencia científica.
Conoces los planes de WellCore: Esencial (entrenamiento base), Método (nutrición + entrenamiento), Elite (todo + coaching 1:1).
Respondes SIEMPRE en español, de forma concisa (máximo 200 palabras por respuesta).
NO das consejos médicos específicos — siempre recomienda consultar un profesional para temas de salud.
Ayudas con: planes de entrenamiento, nutrición, motivación, técnica de ejercicios, progreso, recuperación.
PROMPT;

    public function sendMessage(User $user, string $message, ?int $conversationId = null): array {
        $this->checkRateLimit($user);

        $conversation = $conversationId
            ? AiConversation::where('user_id', $user->id)->findOrFail($conversationId)
            : AiConversation::create([
                'user_id' => $user->id,
                'title'   => substr($message, 0, 60),
            ]);

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $message,
        ]);

        $history = $conversation->messages()->latest()->limit(10)->get()->reverse();
        $apiMessages = $history->map(fn($m) => [
            'role'    => $m->role,
            'content' => $m->content,
        ])->values()->toArray();

        $response = Http::withHeaders([
            'x-api-key'         => config('services.claude.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(60)->post(config('services.claude.base_url') . '/v1/messages', [
            'model'      => config('services.claude.model', 'claude-sonnet-4-6'),
            'max_tokens' => 512,
            'system'     => self::SYSTEM_PROMPT,
            'messages'   => $apiMessages,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('AI no disponible: ' . $response->body());
        }

        $data = $response->json();
        $content = $data['content'][0]['text'] ?? 'Lo siento, no pude procesar tu mensaje.';
        $tokensUsed = ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0);

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => $content,
            'tokens_used'     => $tokensUsed,
            'model'           => config('services.claude.model'),
        ]);

        $conversation->increment('message_count', 2);
        $conversation->increment('total_tokens_used', $tokensUsed);

        $this->incrementRateLimit($user);

        return [
            'conversation_id' => $conversation->id,
            'message'         => $content,
            'tokens_used'     => $tokensUsed,
        ];
    }

    public function getConversations(User $user): array {
        return AiConversation::where('user_id', $user->id)
            ->with(['latestMessage'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getConversation(User $user, int $id): AiConversation {
        return AiConversation::where('user_id', $user->id)
            ->with('messages')
            ->findOrFail($id);
    }

    private function checkRateLimit(User $user): void {
        $key = "ai_rate_limit:{$user->id}:" . now()->format('Y-m-d-H');
        $count = Cache::get($key, 0);
        if ($count >= self::RATE_LIMIT_PER_HOUR) {
            throw ValidationException::withMessages([
                'rate_limit' => ['Has alcanzado el límite de 30 mensajes por hora. Intenta más tarde.'],
            ]);
        }
    }

    private function incrementRateLimit(User $user): void {
        $key = "ai_rate_limit:{$user->id}:" . now()->format('Y-m-d-H');
        Cache::increment($key);
        Cache::expire($key, 3600);
    }
}
