<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicService
{
    private const MESSAGES_URL = 'https://api.anthropic.com/v1/messages';
    private const ANTHROPIC_VERSION = '2023-06-01';

    public function __construct(
        private readonly ?string $apiKey = null,
        private readonly string $model = 'claude-sonnet-4-6',
        private readonly int $maxTokens = 4096,
        private readonly int $timeoutSeconds = 60,
    ) {}

    /**
     * Send a structured messages call.
     *
     * @param  array<int, array<string, mixed>>|string  $systemPrompt  string for simple use, or array of content blocks for cache_control
     * @param  array<int, array<string, mixed>>  $messages  messages array per Anthropic API
     */
    public function complete(array|string $systemPrompt, array $messages, ?string $model = null, ?int $maxTokens = null): array
    {
        $apiKey = $this->apiKey ?? config('services.anthropic.api_key');
        if (empty($apiKey)) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not set.');
        }

        $payload = [
            'model' => $model ?? $this->model,
            'max_tokens' => $maxTokens ?? $this->maxTokens,
            'system' => $systemPrompt,
            'messages' => $messages,
        ];

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::ANTHROPIC_VERSION,
            'content-type' => 'application/json',
        ])
            ->timeout($this->timeoutSeconds)
            ->post(self::MESSAGES_URL, $payload);

        return $this->decode($response);
    }

    private function decode(Response $response): array
    {
        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'Anthropic API call failed: HTTP %d — %s',
                $response->status(),
                $response->body(),
            ));
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data['content']) || ! is_array($data['content'])) {
            throw new RuntimeException('Anthropic API returned an unexpected payload.');
        }

        $text = '';
        foreach ($data['content'] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= $block['text'] ?? '';
            }
        }

        return [
            'text' => $text,
            'usage' => $data['usage'] ?? [],
            'stop_reason' => $data['stop_reason'] ?? null,
            'model' => $data['model'] ?? null,
            'raw' => $data,
        ];
    }
}
