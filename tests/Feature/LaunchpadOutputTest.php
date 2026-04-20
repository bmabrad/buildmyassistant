<?php

use App\Actions\DeliverLaunchpadOutput;
use App\Jobs\GenerateLaunchpadOutputJob;
use App\Mail\LaunchpadDeliveryMail;
use App\Models\Delivery;
use App\Models\Generation;
use App\Models\Lead;
use App\Models\PromptVersion;
use App\Services\LaunchpadPdfService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Mail::fake();
    Storage::fake('local');
});

function validOutputForDelivery(): array
{
    return [
        'business_type' => 'Coach',
        'assistant_pick' => ['role' => 'Exec', 'reason' => 'Email dominates.'],
        'cover' => ['subtitle' => 'A starter prompt for your Exec Assistant.', 'time_savings_line' => 'Most coaches recover three to five hours a week.'],
        'your_first_assistant' => [
            'remit_paragraph' => 'Handles inbox and admin.',
            'pain_paragraph' => 'You said email eats your week.',
            'time_savings_paragraph' => 'Three to five hours a week.',
        ],
        'starter_prompt' => 'You are the Exec Assistant.',
        'install_steps' => ['Open the tool.', 'Paste the prompt.', 'Give it a task.'],
        'email_body' => [
            'subject' => 'Your starter prompt, Sarah',
            'opening_line' => 'Based on what you told us...',
            'upgrade_line' => 'The custom version is seven dollars.',
        ],
        'other_four' => [
            ['role' => 'Finance', 'one_line_remit' => 'Numbers.', 'typical_pain' => 'Late invoices.'],
            ['role' => 'Sales', 'one_line_remit' => 'Pipeline.', 'typical_pain' => 'Stalled deals.'],
            ['role' => 'Marketing', 'one_line_remit' => 'Content.', 'typical_pain' => 'No newsletter.'],
            ['role' => 'Operations', 'one_line_remit' => 'Delivery.', 'typical_pain' => 'Onboarding repeats.'],
        ],
        'no_ai_appendix' => null,
    ];
}

it('renders a PDF and writes it to storage', function () {
    $lead = Lead::factory()->generated()->create(['buyer_name' => 'Sarah', 'tools_used' => 'Claude CoWork']);
    $generation = Generation::factory()->success()->create([
        'lead_id' => $lead->id,
        'output' => validOutputForDelivery(),
    ]);

    $path = app(LaunchpadPdfService::class)->render($lead, $generation);

    expect(file_exists($path))->toBeTrue();
    $bytes = file_get_contents($path);
    expect(strlen($bytes))->toBeGreaterThan(1000);
    expect(substr($bytes, 0, 4))->toBe('%PDF');
});

it('sends the email with the PDF attached and logs a Delivery', function () {
    $lead = Lead::factory()->generated()->create([
        'buyer_name' => 'Sarah',
        'buyer_email' => 'sarah@example.com',
        'tools_used' => 'Claude CoWork',
    ]);
    $generation = Generation::factory()->success()->create([
        'lead_id' => $lead->id,
        'output' => validOutputForDelivery(),
    ]);

    app(DeliverLaunchpadOutput::class)($lead, $generation);

    Mail::assertSent(LaunchpadDeliveryMail::class, function (LaunchpadDeliveryMail $mail) use ($lead) {
        return $mail->hasTo($lead->buyer_email) &&
            $mail->envelope()->subject === 'Your starter prompt, Sarah' &&
            $mail->lead->id === $lead->id;
    });

    $delivery = Delivery::first();
    expect($delivery->status)->toBe('sent');
    expect($delivery->to_email)->toBe('sarah@example.com');
    expect($delivery->attachments)->toHaveCount(1);
    expect($delivery->sent_at)->not->toBeNull();

    $lead->refresh();
    expect($lead->status)->toBe('delivered');
    expect($lead->delivered_at)->not->toBeNull();
});

it('records a failed Delivery when mail send throws', function () {
    $lead = Lead::factory()->generated()->create();
    $generation = Generation::factory()->success()->create([
        'lead_id' => $lead->id,
        'output' => validOutputForDelivery(),
    ]);

    // Swap the PDF service for one that throws, forcing the action into the catch path.
    $this->app->bind(LaunchpadPdfService::class, function () {
        return new class extends LaunchpadPdfService {
            public function render(\App\Models\Lead $lead, \App\Models\Generation $generation): string
            {
                throw new \RuntimeException('PDF broken');
            }
        };
    });

    app(DeliverLaunchpadOutput::class)($lead, $generation);

    $delivery = Delivery::first();
    expect($delivery->status)->toBe('failed');
    expect($delivery->error)->toContain('PDF broken');

    $lead->refresh();
    expect($lead->status)->not->toBe('delivered');
});

it('runs generation and delivery end to end when the job handles', function () {
    config()->set('services.anthropic.api_key', 'test-key');
    PromptVersion::factory()->active()->create(['product' => 'launchpad', 'system_prompt' => 'test prompt']);

    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'id' => 'msg', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-4-6',
            'content' => [['type' => 'text', 'text' => json_encode(validOutputForDelivery())]],
            'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 3000, 'output_tokens' => 1200],
        ]),
    ]);

    $lead = Lead::factory()->completed()->create([
        'buyer_name' => 'Sarah',
        'buyer_email' => 'sarah@example.com',
        'tools_used' => 'Claude CoWork',
    ]);

    (new GenerateLaunchpadOutputJob($lead))->handle(
        app(\App\Actions\GenerateLaunchpadOutput::class),
        app(DeliverLaunchpadOutput::class),
    );

    expect(Generation::where('status', 'success')->count())->toBe(1);
    $delivery = Delivery::first();
    expect($delivery)->not->toBeNull();
    expect($delivery->status)->toBe('sent');

    Mail::assertSent(LaunchpadDeliveryMail::class);

    $lead->refresh();
    expect($lead->status)->toBe('delivered');
});
