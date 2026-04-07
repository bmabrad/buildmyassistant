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

        // If Phase 1 is complete, transition to Phase 2 on next user message
        if ($this->task->phase_1_complete && $this->task->phase === 1) {
            $this->task->update(['phase' => 2]);
        }

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

        // Detect and handle instruction sheet marker
        $isInstructionSheet = $this->detectInstructionSheet($fullResponse);
        if ($isInstructionSheet) {
            $fullResponse = $this->stripInstructionSheetMarker($fullResponse);
        }

        // Store the complete response
        $this->task->chats()->create([
            'role' => 'assistant',
            'content' => $fullResponse,
            'phase' => $this->task->phase,
            'is_instruction_sheet' => $isInstructionSheet,
        ]);

        // Handle phase transitions when instruction sheet is detected
        if ($isInstructionSheet) {
            if (! $this->task->phase_1_complete) {
                $this->task->update(['phase_1_complete' => true]);
            } else {
                $this->task->update(['phase' => 2, 'status' => 'completed']);
                Mail::to($this->task->email)->send(new LaunchpadCompletionMail($this->task));
            }
        }

        $this->isStreaming = false;
        $this->streamedContent = '';

        // Reload messages so the stored message appears in the list
        $this->task->load('chats');

        $this->dispatch('response-complete');
    }

    public static function detectInstructionSheet(string $content): bool
    {
        return str_contains($content, '<!-- INSTRUCTION_SHEET -->');
    }

    public static function stripInstructionSheetMarker(string $content): string
    {
        return trim(str_replace('<!-- INSTRUCTION_SHEET -->', '', $content));
    }

    public function render()
    {
        return view('livewire.launchpad-chat', [
            'messages' => $this->task->chats()->orderBy('created_at', 'asc')->get(),
        ]);
    }
}
