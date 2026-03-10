<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ClaudeAiService
{
    const ANTHROPIC_API = 'https://api.anthropic.com/v1/messages';

    public static function call(
        string $systemPrompt,
        string $userPrompt,
        string $model = 'claude-haiku-4-5-20251001',
        int $maxTokens = 4096
    ): array {
        $response = Http::timeout(600)
            ->withHeaders([
                'x-api-key'         => config('services.claude.key'),
                'anthropic-version' => config('services.claude.version', '2023-06-01'),
                'content-type'      => 'application/json',
            ])
            ->post(self::ANTHROPIC_API, [
                'model'      => $model,
                'max_tokens' => $maxTokens,
                'system'     => $systemPrompt,
                'messages'   => [['role' => 'user', 'content' => $userPrompt]],
            ]);

        if ($response->status() === 429) {
            throw new \Exception('Claude API rate limit exceeded — retry later');
        }

        if ($response->failed()) {
            throw new \Exception('Claude API error: ' . $response->status());
        }

        $body = $response->json();

        return [
            'text'          => $body['content'][0]['text'] ?? '',
            'input_tokens'  => $body['usage']['input_tokens'] ?? 0,
            'output_tokens' => $body['usage']['output_tokens'] ?? 0,
            'stop_reason'   => $body['stop_reason'] ?? '',
        ];
    }

    public static function extractJson(string $text): string
    {
        if (preg_match('/```json\s*([\s\S]*?)\s*```/', $text, $matches)) {
            return trim($matches[1]);
        }
        return trim($text);
    }

    public static function generateRisePlan(array $client, array $intake): string
    {
        $system = AiPromptsService::getRiseSystemPrompt();
        $prompt = AiPromptsService::buildRiseEnrichedPrompt($client, $intake);

        $result = self::call($system, $prompt, 'claude-haiku-4-5-20251001', 8192);
        return self::extractJson($result['text']);
    }

    public static function generateTrainingPlan(array $client, array $intake): string
    {
        $system = AiPromptsService::getTrainingSystemPrompt();
        $prompt = AiPromptsService::buildTrainingPrompt($client, $intake);

        $result = self::call($system, $prompt, 'claude-haiku-4-5-20251001', 6144);
        return self::extractJson($result['text']);
    }

    public static function generatePlanWithMethodology(
        array $client,
        array $intake,
        array $methodology
    ): string {
        $system = AiPromptsService::getMethodologySystemPrompt($methodology);
        $prompt = AiPromptsService::buildMethodologyPrompt($client, $intake, $methodology);

        $result = self::call($system, $prompt, 'claude-haiku-4-5-20251001', 8192);
        return self::extractJson($result['text']);
    }
}
