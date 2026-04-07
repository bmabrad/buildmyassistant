<?php

namespace App\Services;

use App\Models\Assistant;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClaudeApiService
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->model = config('services.anthropic.model');
        $this->maxTokens = (int) env('CLAUDE_MAX_TOKENS', 4096);
    }

    public function chat(Assistant $task, ?string $userMessage = null): string
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'system' => $this->getSystemPrompt($task),
                'messages' => $this->buildMessages($task, $userMessage),
            ]);

            if ($response->failed()) {
                Log::error('Claude API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'task_id' => $task->id,
                ]);

                return 'Something went wrong. Please try again.';
            }

            $data = $response->json();

            return $data['content'][0]['text'] ?? 'Something went wrong. Please try again.';
        } catch (\Exception $e) {
            Log::error('Claude API exception', [
                'message' => $e->getMessage(),
                'task_id' => $task->id,
            ]);

            return 'Something went wrong. Please try again.';
        }
    }

    public function streamChat(Assistant $task, ?string $userMessage = null): Generator
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->withOptions([
                'stream' => true,
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'stream' => true,
                'system' => $this->getSystemPrompt($task),
                'messages' => $this->buildMessages($task, $userMessage),
            ]);

            if ($response->failed()) {
                Log::error('Claude API stream error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'task_id' => $task->id,
                ]);
                yield 'Something went wrong. Please try again.';
                return;
            }

            $body = $response->toPsrResponse()->getBody();
            $buffer = '';

            while (! $body->eof()) {
                $chunk = $body->read(1024);
                $buffer .= $chunk;

                while (($newlinePos = strpos($buffer, "\n")) !== false) {
                    $line = substr($buffer, 0, $newlinePos);
                    $buffer = substr($buffer, $newlinePos + 1);

                    $line = trim($line);

                    if (empty($line) || ! str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $jsonStr = substr($line, 6);

                    if ($jsonStr === '[DONE]') {
                        return;
                    }

                    $event = json_decode($jsonStr, true);

                    if (! $event) {
                        continue;
                    }

                    if ($event['type'] === 'content_block_delta'
                        && isset($event['delta']['text'])) {
                        yield $event['delta']['text'];
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Claude API stream exception', [
                'message' => $e->getMessage(),
                'task_id' => $task->id,
            ]);
            yield 'Something went wrong. Please try again.';
        }
    }

    public function getSystemPrompt(Assistant $task): string
    {
        $prompt = file_get_contents(resource_path('prompts/system_prompt.md'));

        if (! $prompt) {
            Log::error('System prompt file not found');
            return '';
        }

        $exchangeCount = $task->chats()->count();

        return str_replace(
            ['{{BUYER_NAME}}', '{{BUYER_EMAIL}}', '{{EXCHANGE_COUNT}}'],
            [$task->name, $task->email, $exchangeCount],
            $prompt
        );
    }

    public function buildMessages(Assistant $task, ?string $userMessage = null): array
    {
        $messages = $task->chats()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($msg) => $msg->toClaudeFormat())
            ->toArray();

        if ($userMessage !== null) {
            $messages[] = [
                'role' => 'user',
                'content' => $userMessage,
            ];
        }

        // Claude API requires at least one message. For the auto-greeting
        // (first visit, no history, no user message), send a minimal prompt
        // so the system prompt's opening message instructions take effect.
        if (empty($messages)) {
            $messages[] = [
                'role' => 'user',
                'content' => 'Hello',
            ];
        }

        return $messages;
    }
}
