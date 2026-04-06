<?php

namespace App\Livewire;

use App\Models\LaunchpadMessage;
use App\Models\LaunchpadTask;
use App\Services\ClaudeApiService;
use Livewire\Component;

class LaunchpadChat extends Component
{
    public LaunchpadTask $task;
    public string $input = '';
    public bool $isStreaming = false;
    public string $streamedContent = '';

    public function mount(LaunchpadTask $task): void
    {
        $this->task = $task;

        // Auto-generate opening greeting on first visit
        if ($this->task->messages()->count() === 0) {
            $this->generateResponse();
        }
    }

    public function sendMessage(): void
    {
        $message = trim($this->input);

        if ($message === '' || $this->isStreaming) {
            return;
        }

        $this->input = '';

        // If Phase 1 is complete, transition to Phase 2 on next user message
        if ($this->task->phase_1_complete && $this->task->phase === 1) {
            $this->task->update(['phase' => 2]);
        }

        // Store the user's message
        $this->task->messages()->create([
            'role' => 'user',
            'content' => $message,
            'phase' => $this->task->phase,
        ]);

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
        $this->task->messages()->create([
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
            }
        }

        $this->isStreaming = false;
        $this->streamedContent = '';

        // Reload messages so the stored message appears in the list
        $this->task->load('messages');

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
            'messages' => $this->task->messages()->orderBy('created_at', 'asc')->get(),
        ]);
    }
}
