<?php

use App\Actions\GenerateLaunchpadOutput;
use App\Jobs\GenerateLaunchpadOutputJob;
use App\Livewire\LaunchpadChat;
use App\Models\Generation;
use App\Models\Lead;
use App\Models\PromptVersion;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.anthropic.api_key', 'test-key');
    config()->set('services.anthropic.model', 'claude-sonnet-4-6');
    PromptVersion::factory()->active()->create([
        'product' => 'launchpad',
        'name' => 'test-v1',
        'system_prompt' => 'System prompt under test.',
    ]);
});

function validGenerationPayload(): array
{
    return [
        'business_type' => 'Coach',
        'assistant_pick' => ['role' => 'Exec', 'reason' => 'Client emails dominate the pain.'],
        'cover' => ['subtitle' => 'A starter prompt for your Exec Assistant.', 'time_savings_line' => 'Most coaches recover three to five hours a week.'],
        'your_first_assistant' => [
            'remit_paragraph' => 'Handles inbox and admin.',
            'pain_paragraph' => 'You said email eats your week.',
            'time_savings_paragraph' => 'Three to five hours a week.',
        ],
        'starter_prompt' => 'You are the Exec Assistant. Start by asking for three things...',
        'install_steps' => ['Open the tool.', 'Paste the prompt.', 'Give it a real task.'],
        'email_body' => [
            'subject' => 'Your starter prompt',
            'opening_line' => 'Based on what you told us...',
            'upgrade_line' => 'The custom version is seven dollars.',
        ],
        'other_four' => [
            ['role' => 'Finance', 'one_line_remit' => 'Handles numbers.', 'typical_pain' => 'Late invoices.'],
            ['role' => 'Sales', 'one_line_remit' => 'Handles pipeline.', 'typical_pain' => 'Stalled deals.'],
            ['role' => 'Marketing', 'one_line_remit' => 'Handles content.', 'typical_pain' => 'No newsletter.'],
            ['role' => 'Operations', 'one_line_remit' => 'Handles delivery.', 'typical_pain' => 'Onboarding repeats.'],
        ],
        'no_ai_appendix' => null,
    ];
}

function anthropicSuccessResponse(array $payload): array
{
    return [
        'id' => 'msg_test',
        'type' => 'message',
        'role' => 'assistant',
        'model' => 'claude-sonnet-4-6',
        'content' => [['type' => 'text', 'text' => json_encode($payload)]],
        'stop_reason' => 'end_turn',
        'usage' => ['input_tokens' => 3200, 'output_tokens' => 1400],
    ];
}

it('generates a Launchpad output on first call and updates the lead', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response(anthropicSuccessResponse(validGenerationPayload())),
    ]);

    $lead = Lead::factory()->completed()->create(['tools_used' => 'Claude CoWork']);
    $generation = app(GenerateLaunchpadOutput::class)($lead);

    expect($generation->status)->toBe('success');
    expect($generation->output['assistant_pick']['role'])->toBe('Exec');
    expect($generation->input_tokens)->toBe(3200);
    expect($generation->output_tokens)->toBe(1400);

    $lead->refresh();
    expect($lead->status)->toBe('generated');
    expect($lead->business_type)->toBe('Coach');
    expect($lead->assistant_pick)->toBe('Exec');
    expect($lead->generated_at)->not->toBeNull();
});

it('retries once on invalid JSON and succeeds on retry', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::sequence()
            ->push([
                'id' => 'msg_1', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-4-6',
                'content' => [['type' => 'text', 'text' => 'not json at all']],
                'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 100, 'output_tokens' => 10],
            ])
            ->push(anthropicSuccessResponse(validGenerationPayload())),
    ]);

    $lead = Lead::factory()->completed()->create();
    $generation = app(GenerateLaunchpadOutput::class)($lead);

    expect($generation->status)->toBe('success');
    expect($generation->retry_of_id)->not->toBeNull();
    expect(Generation::count())->toBe(2);

    $first = Generation::find($generation->retry_of_id);
    expect($first->status)->toBe('invalid_json');
});

it('falls back after two failures and marks the lead generated', function () {
    $bad = [
        'id' => 'msg_bad', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-4-6',
        'content' => [['type' => 'text', 'text' => 'still not json']],
        'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 100, 'output_tokens' => 10],
    ];
    Http::fake(['api.anthropic.com/*' => Http::sequence()->push($bad)->push($bad)]);

    $lead = Lead::factory()->completed()->create(['tools_used' => 'None yet', 'buyer_name' => 'Ben']);
    $generation = app(GenerateLaunchpadOutput::class)($lead);

    expect($generation->status)->toBe('fallback');
    expect($generation->output['assistant_pick']['role'])->toBe('Exec');
    expect($generation->output['no_ai_appendix'])->toContain('Claude CoWork');
    expect(Generation::count())->toBe(3);

    $lead->refresh();
    expect($lead->status)->toBe('generated');
});

it('strips markdown code fences if the model emits them', function () {
    $fenced = "```json\n" . json_encode(validGenerationPayload()) . "\n```";
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'id' => 'msg', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-4-6',
            'content' => [['type' => 'text', 'text' => $fenced]],
            'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 100, 'output_tokens' => 100],
        ]),
    ]);

    $lead = Lead::factory()->completed()->create();
    $generation = app(GenerateLaunchpadOutput::class)($lead);

    expect($generation->status)->toBe('success');
});

it('fails schema validation when other_four is wrong length', function () {
    $bad = validGenerationPayload();
    $bad['other_four'] = [$bad['other_four'][0]];
    $badNext = validGenerationPayload();
    $badNext['other_four'] = [$badNext['other_four'][0]];

    Http::fake([
        'api.anthropic.com/*' => Http::sequence()
            ->push(anthropicSuccessResponse($bad))
            ->push(anthropicSuccessResponse($badNext)),
    ]);

    $lead = Lead::factory()->completed()->create();
    $generation = app(GenerateLaunchpadOutput::class)($lead);

    expect($generation->status)->toBe('fallback');
    $firstFail = Generation::whereNotNull('error')->orderBy('id')->first();
    expect($firstFail->status)->toBe('schema_failed');
    expect($firstFail->error)->toContain('other_four');
});

it('dispatches GenerateLaunchpadOutputJob when the chat completes with a valid email', function () {
    Bus::fake([GenerateLaunchpadOutputJob::class]);

    Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Sarah')->call('submitStep')
        ->set('businessDescription', 'Leadership coaching for mid-career executives')->call('submitStep')
        ->call('chooseTool', 'Claude CoWork')
        ->set('businessRole', 'Executive coach')->call('submitStep')
        ->set('whoTheyServe', 'Directors and VPs navigating step ups')->call('submitStep')
        ->set('timeDrainChecks', ['Client emails and messages'])->call('submitStep')
        ->set('tediousWork', 'Writing session notes and chasing invoices')->call('submitStep')
        ->set('oneHandoffToday', 'Drafting the session follow-up emails')->call('submitStep')
        ->call('chooseAiUsage', 'Regular')
        ->set('buyerEmail', 'sarah@example.com')->call('submitStep');

    Bus::assertDispatched(GenerateLaunchpadOutputJob::class, function (GenerateLaunchpadOutputJob $job) {
        return $job->lead->buyer_email === 'sarah@example.com';
    });
});

it('does not dispatch generation when a duplicate email is submitted', function () {
    Bus::fake([GenerateLaunchpadOutputJob::class]);

    Lead::factory()->create([
        'buyer_email' => 'sarah@example.com',
        'status' => 'completed',
        'completed_at' => now()->subHour(),
    ]);

    Livewire::test(LaunchpadChat::class)
        ->call('start')
        ->set('buyerName', 'Sarah')->call('submitStep')
        ->set('businessDescription', 'Leadership coaching for mid-career executives')->call('submitStep')
        ->call('chooseTool', 'Claude CoWork')
        ->set('businessRole', 'Executive coach')->call('submitStep')
        ->set('whoTheyServe', 'Directors and VPs navigating step ups')->call('submitStep')
        ->set('timeDrainChecks', ['Client emails and messages'])->call('submitStep')
        ->set('tediousWork', 'Writing session notes and chasing invoices')->call('submitStep')
        ->set('oneHandoffToday', 'Drafting the session follow-up emails')->call('submitStep')
        ->call('chooseAiUsage', 'Regular')
        ->set('buyerEmail', 'sarah@example.com')->call('submitStep');

    Bus::assertNotDispatched(GenerateLaunchpadOutputJob::class);
});
