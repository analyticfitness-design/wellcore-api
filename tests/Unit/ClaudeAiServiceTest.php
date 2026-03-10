<?php

use App\Services\ClaudeAiService;
use Illuminate\Support\Facades\Http;

it('calls Claude API and returns text response', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content'     => [['type' => 'text', 'text' => '{"plan": "test plan"}']],
            'usage'       => ['input_tokens' => 100, 'output_tokens' => 50],
            'stop_reason' => 'end_turn',
        ], 200),
    ]);

    $result = ClaudeAiService::call(
        systemPrompt: 'Eres un coach fitness',
        userPrompt:   'Genera un plan de entrenamiento',
        model:        'claude-haiku-4-5-20251001',
        maxTokens:    4096,
    );

    expect($result['text'])->toContain('plan');
    expect($result['input_tokens'])->toBe(100);
    expect($result['output_tokens'])->toBe(50);
});

it('extracts JSON from markdown code block', function () {
    $text = "Aquí el plan:\n```json\n{\"dias\": [1,2,3]}\n```\nEspero que te guste.";
    $json = ClaudeAiService::extractJson($text);
    expect($json)->toBe('{"dias": [1,2,3]}');
});

it('returns text as-is when no JSON block found', function () {
    $text = '{"direct": "json"}';
    $json = ClaudeAiService::extractJson($text);
    expect($json)->toBe('{"direct": "json"}');
});

it('handles Claude API rate limit gracefully', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(['error' => 'rate limit'], 429),
    ]);

    expect(fn () => ClaudeAiService::call('system', 'user'))
        ->toThrow(\Exception::class, 'rate limit');
});
