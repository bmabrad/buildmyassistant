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

        // Store the complete response
        $this->task->messages()->create([
            'role' => 'assistant',
            'content' => $fullResponse,
            'phase' => $this->task->phase,
        ]);

        $this->isStreaming = false;
        $this->streamedContent = '';

        // Reload messages so the stored message appears in the list
        $this->task->load('messages');

        $this->dispatch('response-complete');
    }

    public function render()
    {
        return view('livewire.launchpad-chat', [
            'messages' => $this->task->messages()->orderBy('created_at', 'asc')->get(),
        ]);
    }
}
