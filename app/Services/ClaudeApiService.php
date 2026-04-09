<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\PromptSegment;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ClaudeApiService
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private array $lastStreamUsage = ['input_tokens' => 0, 'output_tokens' => 0];

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        $this->model = config('services.anthropic.model');
        $this->maxTokens = (int) env('CLAUDE_MAX_TOKENS', 4096);
    }

    /**
     * @return array{text: string, input_tokens: int, output_tokens: int}
     */
    public function chat(Assistant $task, ?string $userMessage = null): array
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

                return ['text' => 'Something went wrong. Please try again.', 'input_tokens' => 0, 'output_tokens' => 0];
            }

            $data = $response->json();

            return [
                'text' => $data['content'][0]['text'] ?? 'Something went wrong. Please try again.',
                'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Claude API exception', [
                'message' => $e->getMessage(),
                'task_id' => $task->id,
            ]);

            return ['text' => 'Something went wrong. Please try again.', 'input_tokens' => 0, 'output_tokens' => 0];
        }
    }

    /**
     * Stream a chat response, yielding text chunks as they arrive.
     *
     * After the generator is exhausted, call getLastStreamUsage() to
     * retrieve the token counts from the completed stream.
     */
    public function streamChat(Assistant $task, ?string $userMessage = null): Generator
    {
        $this->lastStreamUsage = ['input_tokens' => 0, 'output_tokens' => 0];

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

                    // Capture usage from message_start (input tokens)
                    if ($event['type'] === 'message_start'
                        && isset($event['message']['usage'])) {
                        $this->lastStreamUsage['input_tokens'] = $event['message']['usage']['input_tokens'] ?? 0;
                    }

                    // Capture usage from message_delta (output tokens)
                    if ($event['type'] === 'message_delta'
                        && isset($event['usage'])) {
                        $this->lastStreamUsage['output_tokens'] = $event['usage']['output_tokens'] ?? 0;
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

    /**
     * Get the token usage from the last completed streamChat() call.
     *
     * @return array{input_tokens: int, output_tokens: int}
     */
    public function getLastStreamUsage(): array
    {
        return $this->lastStreamUsage ?? ['input_tokens' => 0, 'output_tokens' => 0];
    }

    /**
     * Stream a chat response using a FlowEngine directive instead of the full step prompt.
     */
    public function streamWithDirective(Assistant $task, string $directive, ?string $userMessage = null): Generator
    {
        $assembler = new PromptAssembler();
        $systemPrompt = $assembler->assembleForSubState($task, $directive);

        return $this->streamWithPrompt($task, $systemPrompt, $userMessage);
    }

    /**
     * Stream the Playbook generation using a dedicated prompt.
     */
    public function streamPlaybook(Assistant $task, array $collectedData, ?string $repairDirective = null): Generator
    {
        $assembler = new PromptAssembler();
        $systemPrompt = $assembler->assembleForPlaybook($task, $collectedData);

        if ($repairDirective) {
            $systemPrompt .= "\n\n" . $repairDirective;
        }

        return $this->streamWithPrompt($task, $systemPrompt, null, 8192);
    }

    /**
     * Core streaming method used by both directive and playbook generation.
     */
    private function streamWithPrompt(Assistant $task, string $systemPrompt, ?string $userMessage = null, ?int $maxTokens = null): Generator
    {
        $this->lastStreamUsage = ['input_tokens' => 0, 'output_tokens' => 0];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(120)->withOptions([
                'stream' => true,
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => $maxTokens ?? $this->maxTokens,
                'stream' => true,
                'system' => $systemPrompt,
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
                        break 2;
                    }

                    $data = json_decode($jsonStr, true);

                    if (! $data) {
                        continue;
                    }

                    if (($data['type'] ?? '') === 'message_start') {
                        $this->lastStreamUsage['input_tokens'] = $data['message']['usage']['input_tokens'] ?? 0;
                    }

                    if (($data['type'] ?? '') === 'content_block_delta') {
                        $text = $data['delta']['text'] ?? '';
                        if ($text !== '') {
                            yield $text;
                        }
                    }

                    if (($data['type'] ?? '') === 'message_delta') {
                        $this->lastStreamUsage['output_tokens'] = $data['usage']['output_tokens'] ?? 0;
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
        // Use database-driven prompt segments if available
        if (PromptSegment::active()->exists()) {
            $assembler = new PromptAssembler();
            return $assembler->assemble($task);
        }

        // Fallback to file-based prompt if no segments in DB
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
