<?php

namespace App\Actions;

use App\Mail\AdminLaunchpadAlertMail;
use App\Models\Generation;
use App\Models\Lead;
use App\Models\PromptVersion;
use App\Services\AnthropicService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class GenerateLaunchpadOutput
{
    private const PRODUCT = 'launchpad';
    private const ARCHETYPE_LIBRARY_PATH = 'prompts/launchpad_archetype_library.md';

    public function __construct(private readonly AnthropicService $anthropic) {}

    public function __invoke(Lead $lead): Generation
    {
        $prompt = PromptVersion::activeFor(self::PRODUCT);
        if (! $prompt) {
            throw new RuntimeException('No active Launchpad prompt version.');
        }

        $inputPayload = $this->buildInputPayload($lead);
        $archetypeLibrary = $this->loadArchetypeLibrary();

        $generation = $this->attempt($lead, $prompt, $archetypeLibrary, $inputPayload);

        if ($generation->status === 'success') {
            $this->applyToLead($lead, $generation);

            return $generation;
        }

        $retry = $this->attempt($lead, $prompt, $archetypeLibrary, $inputPayload, $generation);

        if ($retry->status === 'success') {
            $this->applyToLead($lead, $retry);

            return $retry;
        }

        $fallback = $this->fallback($lead, $prompt, $retry);
        $this->applyToLead($lead, $fallback);
        Log::warning('Launchpad generation fell back after two failures', [
            'lead_id' => $lead->id,
            'first_generation_id' => $generation->id,
            'retry_generation_id' => $retry->id,
        ]);
        $this->alertAdmin($lead, 'generation fell back after two failures', [
            'first_generation_id' => $generation->id,
            'first_status' => $generation->status,
            'first_error' => $generation->error,
            'retry_generation_id' => $retry->id,
            'retry_status' => $retry->status,
            'retry_error' => $retry->error,
            'fallback_generation_id' => $fallback->id,
        ]);

        return $fallback;
    }

    private function alertAdmin(Lead $lead, string $reason, array $context): void
    {
        $to = config('launchpad.admin_alert_email');
        if (! $to) {
            return;
        }
        try {
            Mail::to($to)->send(new AdminLaunchpadAlertMail($lead, $reason, $context));
        } catch (Throwable $e) {
            Log::error('Failed to send admin launchpad alert', ['error' => $e->getMessage()]);
        }
    }

    private function attempt(Lead $lead, PromptVersion $prompt, string $archetypeLibrary, array $inputPayload, ?Generation $retryOf = null): Generation
    {
        $generation = Generation::create([
            'lead_id' => $lead->id,
            'prompt_version_id' => $prompt->id,
            'retry_of_id' => $retryOf?->id,
            'product' => self::PRODUCT,
            'model' => config('services.anthropic.model', 'claude-sonnet-4-6'),
            'status' => 'pending',
            'input_payload' => $inputPayload,
        ]);

        $startedAt = microtime(true);

        try {
            $response = $this->anthropic->complete(
                systemPrompt: [
                    ['type' => 'text', 'text' => $prompt->system_prompt, 'cache_control' => ['type' => 'ephemeral']],
                ],
                messages: [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $archetypeLibrary, 'cache_control' => ['type' => 'ephemeral']],
                            ['type' => 'text', 'text' => $this->buildUserMessage($inputPayload)],
                        ],
                    ],
                ],
                model: $generation->model,
            );
        } catch (Throwable $e) {
            $generation->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);

            return $generation;
        }

        $latency = (int) round((microtime(true) - $startedAt) * 1000);
        $usage = $response['usage'] ?? [];

        $parsed = $this->parseJson($response['text'] ?? '');
        if ($parsed === null) {
            $generation->update([
                'status' => 'invalid_json',
                'error' => 'Model output was not valid JSON.',
                'raw_response' => $response['text'] ?? null,
                'latency_ms' => $latency,
                'input_tokens' => $usage['input_tokens'] ?? null,
                'output_tokens' => $usage['output_tokens'] ?? null,
            ]);

            return $generation;
        }

        $schemaError = $this->validateSchema($parsed);
        if ($schemaError !== null) {
            $generation->update([
                'status' => 'schema_failed',
                'error' => $schemaError,
                'raw_response' => $response['text'] ?? null,
                'output' => $parsed,
                'latency_ms' => $latency,
                'input_tokens' => $usage['input_tokens'] ?? null,
                'output_tokens' => $usage['output_tokens'] ?? null,
            ]);

            return $generation;
        }

        $generation->update([
            'status' => 'success',
            'output' => $parsed,
            'raw_response' => $response['text'] ?? null,
            'latency_ms' => $latency,
            'input_tokens' => $usage['input_tokens'] ?? null,
            'output_tokens' => $usage['output_tokens'] ?? null,
        ]);

        return $generation;
    }

    private function fallback(Lead $lead, PromptVersion $prompt, Generation $retryOf): Generation
    {
        $buyerName = $lead->buyer_name ?: 'there';
        $toolName = $lead->tools_used ?: 'Claude CoWork';

        $fallbackOutput = [
            'business_type' => 'Generic solo',
            'assistant_pick' => [
                'role' => 'Exec',
                'reason' => 'Based on what you shared, the Exec Assistant is the most common place to start.',
            ],
            'cover' => [
                'subtitle' => 'A starter prompt for your Exec Assistant.',
                'time_savings_line' => 'Most solo practitioners recover three to five hours a week on inbox and admin alone.',
            ],
            'your_first_assistant' => [
                'remit_paragraph' => 'Your Exec Assistant handles the daily admin that drains your time. Inbox triage, scheduling, meeting prep, follow-ups.',
                'pain_paragraph' => 'You told us you have repeatable admin eating into your week. This assistant takes the first pass on it.',
                'time_savings_paragraph' => 'Most practitioners who run an email-heavy practice recover three to five hours a week on triage alone.',
            ],
            'starter_prompt' => "You are the Exec Assistant for {$buyerName}. Your job is to keep the inbox, calendar, and daily admin clean so the buyer can focus on their core work.\n\nWhat you handle:\n- Triage incoming emails by urgency. Flag anything that needs their direct attention.\n- Draft short replies in their voice when given context.\n- Prep a one-page brief before client meetings.\n- Keep their schedule clean and surface conflicts.\n\nHow to handle it:\n- When given an inbox snapshot, respond with a suggested triage or draft reply.\n- Ask one clarifying question when the ask is ambiguous.\n- Never send anything on their behalf without explicit approval.\n\nStart by asking them for three things: a recent email that drained them, a recent reply they were proud of, and the voice preferences they want you to match.",
            'install_steps' => [
                "Open {$toolName} and create a new project called 'Exec Assistant.'",
                'Paste the prompt above into the project instructions.',
                'Drop in a recent inbox snapshot and ask it to triage. Give it three or four real tasks in the first session so it learns your voice.',
            ],
            'email_body' => [
                'subject' => "Your starter prompt for the Exec Assistant, {$buyerName}",
                'opening_line' => 'Based on what you told us, your first assistant should be the Exec Assistant. Here is a starter prompt you can use today.',
                'upgrade_line' => 'This starter works. If you want a version trained on your specific business, voice, and clients, the Assistant Builder takes you through a short build and hands you the custom version for seven dollars.',
            ],
            'other_four' => [
                ['role' => 'Finance', 'one_line_remit' => 'Handles the numbers. Invoicing, cashflow, expense tracking.', 'typical_pain' => 'When late invoices and chasing payment start eating an afternoon a week.'],
                ['role' => 'Sales', 'one_line_remit' => 'Handles leads and pipeline. Research, proposals, follow-up.', 'typical_pain' => 'When discovery requests sit unanswered for days.'],
                ['role' => 'Marketing', 'one_line_remit' => 'Handles content and promotion. Newsletters, posts, copy.', 'typical_pain' => 'When the same point gets made to five clients a month and none of it is on your website.'],
                ['role' => 'Operations', 'one_line_remit' => 'Handles delivery. Onboarding, SOPs, client workflow.', 'typical_pain' => 'When you are repeating the same setup steps with every new client.'],
            ],
            'no_ai_appendix' => $lead->tools_used === 'None yet' ? $this->noAiAppendix() : null,
        ];

        return Generation::create([
            'lead_id' => $lead->id,
            'prompt_version_id' => $prompt->id,
            'retry_of_id' => $retryOf->id,
            'product' => self::PRODUCT,
            'model' => 'fallback',
            'status' => 'fallback',
            'input_payload' => $this->buildInputPayload($lead),
            'output' => $fallbackOutput,
        ]);
    }

    private function applyToLead(Lead $lead, Generation $generation): void
    {
        if (! is_array($generation->output)) {
            return;
        }
        $lead->update([
            'business_type' => $generation->output['business_type'] ?? null,
            'assistant_pick' => $generation->output['assistant_pick']['role'] ?? null,
            'status' => 'generated',
            'generated_at' => now(),
        ]);
    }

    private function buildInputPayload(Lead $lead): array
    {
        return [
            'buyer_name' => $lead->buyer_name,
            'buyer_email' => $lead->buyer_email,
            'business_description' => $lead->business_description,
            'business_role' => $lead->business_role,
            'who_they_serve' => $lead->who_they_serve,
            'tools_used' => $lead->tools_used,
            'time_drains' => $lead->time_drains,
            'tedious_work' => $lead->tedious_work,
            'one_handoff_today' => $lead->one_handoff_today,
            'ai_usage_level' => $lead->ai_usage_level ?: 'Light',
        ];
    }

    private function buildUserMessage(array $payload): string
    {
        $lines = ['Buyer chat inputs:'];
        foreach ($payload as $key => $value) {
            $lines[] = "- {$key}: " . ($value ?? '(not provided)');
        }
        $lines[] = '';
        $lines[] = 'Generate the Launchpad output JSON now. Respond with ONLY the JSON object. No preamble, no markdown code fences, no trailing commentary.';

        return implode("\n", $lines);
    }

    private function loadArchetypeLibrary(): string
    {
        $path = resource_path(self::ARCHETYPE_LIBRARY_PATH);
        if (! is_file($path)) {
            throw new RuntimeException("Archetype library not found at {$path}");
        }

        return "Assistant archetype library (reference):\n\n" . file_get_contents($path);
    }

    private function parseJson(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        // Strip markdown code fences if the model emitted them despite instructions.
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
            $text = trim((string) $text);
        }
        try {
            $decoded = json_decode($text, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function validateSchema(array $data): ?string
    {
        $validRoles = ['Exec', 'Finance', 'Sales', 'Marketing', 'Operations'];
        $validTypes = ['Coach', 'Consultant', 'Advisor', 'Creator', 'Generic solo'];

        if (! in_array($data['business_type'] ?? null, $validTypes, true)) {
            return 'business_type is missing or invalid.';
        }
        if (! is_array($data['assistant_pick'] ?? null) || ! in_array($data['assistant_pick']['role'] ?? null, $validRoles, true)) {
            return 'assistant_pick.role is missing or invalid.';
        }
        if (empty($data['assistant_pick']['reason'] ?? null)) {
            return 'assistant_pick.reason is required.';
        }

        foreach (['subtitle', 'time_savings_line'] as $key) {
            if (empty($data['cover'][$key] ?? null)) {
                return "cover.{$key} is required.";
            }
        }
        foreach (['remit_paragraph', 'pain_paragraph', 'time_savings_paragraph'] as $key) {
            if (empty($data['your_first_assistant'][$key] ?? null)) {
                return "your_first_assistant.{$key} is required.";
            }
        }

        if (empty($data['starter_prompt'] ?? null) || ! is_string($data['starter_prompt'])) {
            return 'starter_prompt is required.';
        }

        if (! is_array($data['install_steps'] ?? null) || count($data['install_steps']) < 3 || count($data['install_steps']) > 5) {
            return 'install_steps must be a 3-5 element array.';
        }
        foreach ($data['install_steps'] as $step) {
            if (! is_string($step) || trim($step) === '') {
                return 'install_steps entries must be non-empty strings.';
            }
        }

        foreach (['subject', 'opening_line', 'upgrade_line'] as $key) {
            if (empty($data['email_body'][$key] ?? null)) {
                return "email_body.{$key} is required.";
            }
        }

        if (! is_array($data['other_four'] ?? null) || count($data['other_four']) !== 4) {
            return 'other_four must be an array of exactly 4 entries.';
        }
        foreach ($data['other_four'] as $i => $entry) {
            if (! in_array($entry['role'] ?? null, $validRoles, true)) {
                return "other_four[{$i}].role is invalid.";
            }
            if (empty($entry['one_line_remit'] ?? null) || empty($entry['typical_pain'] ?? null)) {
                return "other_four[{$i}] is missing remit or typical_pain.";
            }
        }

        return null;
    }

    private function noAiAppendix(): string
    {
        return "Getting started with Claude CoWork\n\n".
            "Why Claude CoWork. It plays nicely with the kind of assistants we build, and the free tier is enough to get started.\n\n".
            "Sign up. 1) Go to claude.ai/cowork, 2) create an account with your email, 3) install the desktop app.\n\n".
            "Install the prompt. Copy the starter prompt above and paste it into a new project's instructions inside CoWork.\n\n".
            "First use. Give it a real task from your week, not a test prompt. The assistant learns your voice fastest when the work is real.";
    }
}
