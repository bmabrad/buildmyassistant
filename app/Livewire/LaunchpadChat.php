<?php

namespace App\Livewire;

use App\Mail\LaunchpadCompletionMail;
use App\Models\Chat;
use App\Models\Assistant;
use App\Services\ClaudeApiService;
use App\Services\FlowEngine;
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

        // Initialize flow state for new sessions
        if ($this->task->chats()->count() === 0) {
            if (! $this->task->flow_state) {
                $this->task->update(['flow_state' => FlowEngine::initState()]);
            }
            $this->needsGreeting = true;
        }
    }

    public function generateGreeting(): void
    {
        if (! $this->needsGreeting) {
            return;
        }

        $this->needsGreeting = false;

        // Use FlowEngine for new sessions, legacy for old ones
        if ($this->task->flow_state) {
            $this->generateFlowResponse(null);
        } else {
            $this->generateLegacyResponse();
        }
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
            'phase' => $this->task->flow_state['step'] ?? $this->task->phase,
        ]);

        // Set streaming state and trigger response in a second request
        // so the browser re-renders first (showing user message + typing dots)
        $this->isStreaming = true;
        $this->pendingUserMessage = $message;
        $this->js('$wire.streamResponse()');
    }

    public function streamResponse(): void
    {
        $message = $this->pendingUserMessage;
        $this->pendingUserMessage = null;

        if ($this->task->flow_state) {
            $this->generateFlowResponse($message);
        } else {
            $this->generateLegacyResponse($message);
        }
    }

    // ───────────────────────────────────────────────
    // New: FlowEngine-driven response generation
    // ───────────────────────────────────────────────

    private function generateFlowResponse(?string $userMessage): void
    {
        $this->isStreaming = true;
        $this->streamedContent = '';

        set_time_limit(120);

        $engine = new FlowEngine();
        $api = app(ClaudeApiService::class);

        // Ask FlowEngine what to do
        if ($userMessage === null) {
            // Greeting
            $action = $engine->processGreeting($this->task);
        } else {
            $action = $engine->processUserMessage($this->task, $userMessage);
        }

        // Save the updated state
        $this->task->update([
            'flow_state' => $action['state'],
            'phase' => $action['state']['step'],
        ]);

        if ($action['action'] === 'generate_playbook') {
            $this->generatePlaybook($api, $action['state']);
            return;
        }

        // action === 'call_ai' — stream with scoped directive
        $directive = $action['directive'] ?? '';
        $fullResponse = '';

        foreach ($api->streamWithDirective($this->task, $directive, $userMessage) as $chunk) {
            $fullResponse .= $chunk;
            $this->streamedContent = $fullResponse;

            $this->stream(
                content: $fullResponse,
                name: 'streamed-response',
                replace: true,
            );
        }

        // Record token usage
        $usage = $api->getLastStreamUsage();
        if ($usage['input_tokens'] > 0 || $usage['output_tokens'] > 0) {
            $this->task->recordTokenUsage($usage['input_tokens'], $usage['output_tokens']);
        }

        // Extract buyer name if provided via hidden marker
        if (preg_match('/<!-- BUYER_NAME:\s*(.+?)\s*-->/', $fullResponse, $nameMatch)) {
            $fullResponse = trim(str_replace($nameMatch[0], '', $fullResponse));
            $buyerName = trim($nameMatch[1]);
            if ($buyerName && ($this->task->name === 'Unknown' || ! $this->task->name)) {
                $this->task->update(['name' => $buyerName]);

                if ($this->task->user && ! $this->task->user->first_name) {
                    $parts = explode(' ', $buyerName, 2);
                    $this->task->user->update([
                        'first_name' => $parts[0],
                        'last_name' => $parts[1] ?? null,
                        'name' => $buyerName,
                    ]);
                }
            }
        }

        // If in 'noted' sub-state (Step 3), truncate response to prevent AI
        // from generating the Playbook inline. Keep only the acknowledgment.
        $currentState = $this->task->flow_state ?? [];
        if (($currentState['step'] ?? 0) === 3 && ($currentState['sub_state'] ?? '') === 'noted') {
            // Strip any playbook-like content: cut at first markdown heading or --- separator
            if (preg_match('/^(.+?)(?=\n\s*(?:#{1,3}\s|---|\*\*\d+\.))/s', $fullResponse, $trimMatch)) {
                $fullResponse = trim($trimMatch[1]);
            }
        }

        // Let FlowEngine process the AI's response (update turn count)
        $postProcess = $engine->processAiResponse($this->task, $fullResponse);
        $this->task->update(['flow_state' => $postProcess['state']]);

        // Update assistant_name on the model if captured in flow state
        $assistantName = $postProcess['state']['data']['assistant_name'] ?? null;
        if ($assistantName && ! $this->task->assistant_name) {
            $this->task->update(['assistant_name' => $assistantName]);
        }

        // Store the response
        $this->task->chats()->create([
            'role' => 'assistant',
            'content' => $fullResponse,
            'phase' => $postProcess['state']['step'],
        ]);

        $this->isStreaming = false;
        $this->streamedContent = '';
        $this->task->load('chats');
        $this->dispatch('response-complete');

        // Handle follow-up actions triggered by processAiResponse
        if (($postProcess['follow_up'] ?? null) === 'generate_playbook') {
            $this->task->refresh();
            $this->generatePlaybook($api, $this->task->flow_state);
        }
    }

    /**
     * Generate the Playbook via a dedicated API call.
     * The content is never shown in the chat — only the download card.
     */
    private function generatePlaybook(ClaudeApiService $api, array $state): void
    {
        // Show "generating" message during streaming
        $this->stream(
            content: 'Putting your Playbook together now...',
            name: 'streamed-response',
            replace: true,
        );

        $collectedData = $state['data'] ?? [];
        $fullResponse = '';

        foreach ($api->streamPlaybook($this->task, $collectedData) as $chunk) {
            $fullResponse .= $chunk;

            // Keep showing the loading message (don't show raw playbook)
            $this->stream(
                content: 'Putting your Playbook together now...',
                name: 'streamed-response',
                replace: true,
            );
        }

        // Record token usage
        $usage = $api->getLastStreamUsage();
        if ($usage['input_tokens'] > 0 || $usage['output_tokens'] > 0) {
            $this->task->recordTokenUsage($usage['input_tokens'], $usage['output_tokens']);
        }

        // Validate and repair the playbook output
        $validator = new \App\Services\PlaybookValidator();
        $validation = $validator->validate($fullResponse);

        if (! $validation['valid']) {
            // Auto-fix what we can (em dashes, missing marker)
            $autoFix = $validator->autoFix($fullResponse, $validation['issues']);
            if ($autoFix['fixed']) {
                $fullResponse = $autoFix['content'];
                \Illuminate\Support\Facades\Log::info('Playbook auto-fixed', [
                    'task_id' => $this->task->id,
                    'fixes' => $autoFix['fixes_applied'],
                ]);
            }

            // Re-validate after auto-fix
            $validation = $validator->validate($fullResponse);

            // If still issues that need AI repair, attempt one retry
            $remainingIssues = array_filter($validation['issues'], fn ($i) => $i !== 'contains_em_dashes');
            if (! empty($remainingIssues)) {
                $repairPrompt = $validator->buildRepairPrompt($remainingIssues);

                if ($repairPrompt) {
                    \Illuminate\Support\Facades\Log::warning('Playbook needs AI repair', [
                        'task_id' => $this->task->id,
                        'issues' => $remainingIssues,
                    ]);

                    // Attempt one repair pass
                    $repairResponse = '';
                    foreach ($api->streamPlaybook($this->task, $collectedData, $repairPrompt) as $chunk) {
                        $repairResponse .= $chunk;

                        $this->stream(
                            content: 'Putting your Playbook together now...',
                            name: 'streamed-response',
                            replace: true,
                        );
                    }

                    // Record repair token usage
                    $repairUsage = $api->getLastStreamUsage();
                    if ($repairUsage['input_tokens'] > 0 || $repairUsage['output_tokens'] > 0) {
                        $this->task->recordTokenUsage($repairUsage['input_tokens'], $repairUsage['output_tokens']);
                    }

                    // Use repair if it's better (has the marker and is long enough)
                    $repairValidation = $validator->validate($repairResponse);
                    if (count($repairValidation['issues']) < count($remainingIssues)) {
                        $fullResponse = $repairResponse;
                        // Auto-fix em dashes on repair too
                        $fullResponse = str_replace('—', '-', $fullResponse);

                        \Illuminate\Support\Facades\Log::info('Playbook repair successful', [
                            'task_id' => $this->task->id,
                            'remaining_issues' => $repairValidation['issues'],
                        ]);
                    }
                }
            }
        }

        // Strip any markers from the response
        $fullResponse = self::stripDeliverableMarker($fullResponse);

        // Parse into playbook + instructions
        $deliverableData = self::parseDeliverableContent($fullResponse);

        // Store the deliverable message
        $this->task->chats()->create([
            'role' => 'assistant',
            'content' => 'Your Playbook is ready.',
            'phase' => 4,
            'is_deliverable' => true,
            'playbook_content' => $deliverableData['playbook_content'] ?? $fullResponse,
            'instructions_content' => $deliverableData['instructions_content'] ?? null,
        ]);

        // Update assistant_name from collected data
        $assistantName = $collectedData['assistant_name'] ?? null;
        if ($assistantName) {
            $this->task->update(['assistant_name' => $assistantName]);
        }

        // Transition to Post-Playbook mode
        $this->task->update([
            'playbook_delivered' => true,
            'in_post_playbook' => true,
            'session_completed_at' => now(),
            'status' => 'completed',
            'flow_state' => array_merge($state, [
                'step' => 5,
                'sub_state' => 'ready_to_install',
                'ai_turns' => 0,
            ]),
            'phase' => 5,
        ]);

        Mail::to($this->task->email)->send(new LaunchpadCompletionMail($this->task));

        // Auto-send a confirmation message after the playbook card
        // Stay in Step 4 until they confirm they got their files
        $this->task->refresh();
        $buyerName = $this->task->name ?? 'there';
        $assistantName = $this->task->assistant_name ?? 'your assistant';
        $confirmMessage = "Nice work, {$buyerName}! {$assistantName} is ready to go. Your Playbook and install instructions are right above. Did you grab your files okay?";

        $this->task->chats()->create([
            'role' => 'assistant',
            'content' => $confirmMessage,
            'phase' => 4,
        ]);

        // Keep state in Step 4, waiting for confirmation
        $currentState = $this->task->flow_state;
        $currentState['step'] = 4;
        $currentState['sub_state'] = 'confirming_download';
        $this->task->update(['flow_state' => $currentState, 'phase' => 4]);

        $this->isStreaming = false;
        $this->streamedContent = '';
        $this->task->load('chats');
        $this->dispatch('response-complete');
    }

    // ───────────────────────────────────────────────
    // Legacy: marker-based response generation
    // (for sessions created before the state machine)
    // ───────────────────────────────────────────────

    private function generateLegacyResponse(?string $userMessage = null): void
    {
        set_time_limit(120);

        $this->isStreaming = true;
        $this->streamedContent = '';

        $api = app(ClaudeApiService::class);
        $fullResponse = '';

        $isGeneratingPlaybook = false;

        foreach ($api->streamChat($this->task, $userMessage) as $chunk) {
            $fullResponse .= $chunk;
            $this->streamedContent = $fullResponse;

            if (! $isGeneratingPlaybook && $this->detectDeliverable($fullResponse)) {
                $isGeneratingPlaybook = true;
            }

            if ($isGeneratingPlaybook) {
                $this->stream(
                    content: 'Putting your Playbook together now...',
                    name: 'streamed-response',
                    replace: true,
                );
            } else {
                $this->stream(
                    content: $fullResponse,
                    name: 'streamed-response',
                    replace: true,
                );
            }
        }

        $usage = $api->getLastStreamUsage();
        if ($usage['input_tokens'] > 0 || $usage['output_tokens'] > 0) {
            $this->task->recordTokenUsage($usage['input_tokens'], $usage['output_tokens']);
        }

        if (preg_match('/<!-- STEP:\s*(\d+)\s*-->/', $fullResponse, $stepMatch)) {
            $newPhase = (int) $stepMatch[1];
            if ($newPhase > $this->task->phase && $newPhase <= 5) {
                $this->task->update(['phase' => $newPhase]);
            }
            $fullResponse = trim(str_replace($stepMatch[0], '', $fullResponse));
        }

        if (preg_match('/<!-- BUYER_NAME:\s*(.+?)\s*-->/', $fullResponse, $nameMatch)) {
            $fullResponse = trim(str_replace($nameMatch[0], '', $fullResponse));
            $buyerName = trim($nameMatch[1]);
            if ($buyerName && ($this->task->name === 'Unknown' || ! $this->task->name)) {
                $this->task->update(['name' => $buyerName]);

                if ($this->task->user && ! $this->task->user->first_name) {
                    $parts = explode(' ', $buyerName, 2);
                    $this->task->user->update([
                        'first_name' => $parts[0],
                        'last_name' => $parts[1] ?? null,
                        'name' => $buyerName,
                    ]);
                }
            }
        }

        $isDeliverable = $this->detectDeliverable($fullResponse);
        if ($isDeliverable) {
            $fullResponse = $this->stripDeliverableMarker($fullResponse);
        }

        $deliverableData = [];
        if ($isDeliverable) {
            $deliverableData = $this->parseDeliverableContent($fullResponse);
        }

        $chatContent = $isDeliverable
            ? 'Your Playbook is ready.'
            : $fullResponse;

        $this->task->chats()->create([
            'role' => 'assistant',
            'content' => $chatContent,
            'phase' => $this->task->phase,
            'is_deliverable' => $isDeliverable,
            'playbook_content' => $deliverableData['playbook_content'] ?? null,
            'instructions_content' => $deliverableData['instructions_content'] ?? null,
        ]);

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
        $this->task->load('chats');
        $this->dispatch('response-complete');
    }

    // ───────────────────────────────────────────────
    // Deliverable detection and parsing (shared)
    // ───────────────────────────────────────────────

    public static function detectDeliverable(string $content): bool
    {
        if (str_contains($content, '<!-- INSTRUCTION_SHEET -->')) {
            return true;
        }

        $patterns = [
            '/##?\s*(Your Bottleneck|What \w+ Does|What \w+ does)/i',
            '/##?\s*(Your Process Map|How \w+ Works|Training \w+)/i',
            '/<!-- INSTRUCTIONS_START -->/',
            '/##?\s*Role\b.*\bYou are \w+,?\s*(an?\s+)?AI\s+assistant/is',
            '/##?\s*(Getting Started|Onboarding Sequence|How You Learn)/i',
            '/Your .+ Assistant:\s*\w+/i',
            '/Time saved:/i',
            '/##?\s*(Setup Instructions|Setup Steps|First test task)/i',
        ];

        $matches = 0;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $matches++;
            }
        }

        return $matches >= 2;
    }

    public static function stripDeliverableMarker(string $content): string
    {
        return trim(str_replace('<!-- INSTRUCTION_SHEET -->', '', $content));
    }

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

        $patterns = [
            '/\n(?=#+\s+.*(?:Assistant Instructions|AI Assistant for))/i',
            '/\n(?=#+\s+.*(?:Complete Instruction Sheet|Instruction Sheet))/i',
            '/\n---\s*\n(?=#+\s+.*(?:Complete Instruction|Instruction Sheet|Assistant Name|Core Function))/i',
        ];

        foreach ($patterns as $pattern) {
            $parts = preg_split($pattern, $content, 2);
            if (count($parts) === 2 && strlen(trim($parts[1])) > 100) {
                return [
                    'playbook_content' => trim($parts[0]),
                    'instructions_content' => trim($parts[1]),
                ];
            }
        }

        return [
            'playbook_content' => trim($content),
            'instructions_content' => null,
        ];
    }

    public function render()
    {
        $this->task->refresh();

        $currentStep = $this->task->flow_state['step'] ?? $this->task->phase;

        return view('livewire.launchpad-chat', [
            'messages' => $this->task->chats()->orderBy('created_at', 'asc')->get(),
            'isPostPlaybook' => $this->task->isPostPlaybook(),
            'isLocked' => $this->task->isChatLocked(),
            'lockReason' => $this->task->lockReason(),
            'daysRemaining' => $this->task->supportDaysRemaining(),
            'currentStep' => $currentStep,
        ]);
    }
}
