<?php

namespace App\Livewire;

use App\Mail\LaunchpadCompletionMail;
use App\Models\Chat;
use App\Models\Assistant;
use App\Services\ClaudeApiService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class LaunchpadChat extends Component
{
    public Assistant $task;
    public string $input = '';
    public bool $isStreaming = false;
    public string $streamedContent = '';
    public string $error = '';
    public ?string $pendingUserMessage = null;

    public bool $needsGreeting = false;

    public function mount(Assistant $task): void
    {
        $this->task = $task;

        // Flag for greeting — can't stream during mount, so defer to the browser
        if ($this->task->chats()->count() === 0) {
            $this->needsGreeting = true;
        }
    }

    public function generateGreeting(): void
    {
        if (! $this->needsGreeting) {
            return;
        }

        $this->needsGreeting = false;
        $this->generateResponse();
    }

    public function sendMessage(): void
    {
        $this->error = '';
        $message = trim($this->input);

        if ($message === '' || $this->isStreaming) {
            return;
        }

        // Check if chat is locked (support window expired or token limit reached)
        $this->task->refresh();
        if ($this->task->isChatLocked()) {
            $reason = $this->task->lockReason();
            $this->error = $reason === 'tokens'
                ? "You've used your available support messages."
                : 'Your 7-day support window has closed.';

            return;
        }

        if (mb_strlen($message) > 5000) {
            $this->error = 'Your message is too long. Please keep it under 5,000 characters.';

            return;
        }

        $rateLimitKey = 'launchpad-chat:' . $this->task->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->error = "You're sending messages too quickly. Please wait {$seconds} seconds.";

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        $this->input = '';

        // Store the user's message
        $this->task->chats()->create([
            'role' => 'user',
            'content' => $message,
            'phase' => $this->task->phase,
        ]);

        // Set streaming state and trigger generateResponse in a second request
        // so the browser re-renders first (showing user message + typing dots)
        $this->isStreaming = true;
        $this->pendingUserMessage = $message;
        $this->js('$wire.streamResponse()');
    }

    public function streamResponse(): void
    {
        $message = $this->pendingUserMessage;
        $this->pendingUserMessage = null;
        $this->generateResponse($message);
    }

    private function generateResponse(?string $userMessage = null): void
    {
        $this->isStreaming = true;
        $this->streamedContent = '';

        $api = app(ClaudeApiService::class);
        $fullResponse = '';

        foreach ($api->streamChat($this->task, $userMessage) as $chunk) {
            $fullResponse .= $chunk;
            $this->streamedContent = $fullResponse;

            $this->stream(
                content: $fullResponse,
                name: 'streamed-response',
                replace: true,
            );
        }

        // Record token usage from the completed stream
        $usage = $api->getLastStreamUsage();
        if ($usage['input_tokens'] > 0 || $usage['output_tokens'] > 0) {
            $this->task->recordTokenUsage($usage['input_tokens'], $usage['output_tokens']);
        }

        // Detect and handle deliverable marker
        $isDeliverable = $this->detectDeliverable($fullResponse);
        if ($isDeliverable) {
            $fullResponse = $this->stripDeliverableMarker($fullResponse);
        }

        // Parse deliverable content into separate Playbook and Instructions sections
        $deliverableData = [];
        if ($isDeliverable) {
            $deliverableData = $this->parseDeliverableContent($fullResponse);
        }

        // Store the complete response
        $this->task->chats()->create([
            'role' => 'assistant',
            'content' => $fullResponse,
            'phase' => $this->task->phase,
            'is_deliverable' => $isDeliverable,
            'playbook_content' => $deliverableData['playbook_content'] ?? null,
            'instructions_content' => $deliverableData['instructions_content'] ?? null,
        ]);

        // Handle Playbook delivery: set session_completed_at and transition to Post-Playbook
        if ($isDeliverable && ! $this->task->playbook_delivered) {
            $this->task->update([
                'playbook_delivered' => true,
                'in_post_playbook' => true,
                'session_completed_at' => now(),
                'status' => 'completed',
            ]);
            Mail::to($this->task->email)->send(new LaunchpadCompletionMail($this->task));
        }

        $this->isStreaming = false;
        $this->streamedContent = '';

        // Reload messages so the stored message appears in the list
        $this->task->load('chats');

        $this->dispatch('response-complete');
    }

    public static function detectDeliverable(string $content): bool
    {
        return str_contains($content, '<!-- INSTRUCTION_SHEET -->');
    }

    public static function stripDeliverableMarker(string $content): string
    {
        return trim(str_replace('<!-- INSTRUCTION_SHEET -->', '', $content));
    }

    /**
     * Parse the deliverable message into separate Playbook and Instructions sections.
     *
     * The guide outputs both in one message, separated by a marker like:
     * <!-- INSTRUCTIONS_START -->
     * If no marker is found, the full content is treated as the Playbook
     * and instructions_content is left null.
     */
    public static function parseDeliverableContent(string $content): array
    {
        $marker = '<!-- INSTRUCTIONS_START -->';

        if (str_contains($content, $marker)) {
            [$playbook, $instructions] = explode($marker, $content, 2);

            return [
                'playbook_content' => trim($playbook),
                'instructions_content' => trim($instructions),
            ];
        }

        // Fallback: try to split on the assistant instructions heading pattern
        // e.g. "## Assistant Instructions" or "# [Name] — AI Assistant for [Client]"
        $pattern = '/\n(?=#+\s+.*(?:Assistant Instructions|AI Assistant for))/i';
        $parts = preg_split($pattern, $content, 2);

        if (count($parts) === 2) {
            return [
                'playbook_content' => trim($parts[0]),
                'instructions_content' => trim($parts[1]),
            ];
        }

        // Cannot split — store full content as playbook, instructions null
        return [
            'playbook_content' => trim($content),
            'instructions_content' => null,
        ];
    }

    public function render()
    {
        $this->task->refresh();

        return view('livewire.launchpad-chat', [
            'messages' => $this->task->chats()->orderBy('created_at', 'asc')->get(),
            'isPostPlaybook' => $this->task->isPostPlaybook(),
            'isLocked' => $this->task->isChatLocked(),
            'lockReason' => $this->task->lockReason(),
            'daysRemaining' => $this->task->supportDaysRemaining(),
        ]);
    }
}
