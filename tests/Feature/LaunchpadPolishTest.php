<?php

use App\Actions\DeliverLaunchpadOutput;
use App\Actions\GenerateLaunchpadOutput;
use App\Jobs\GenerateLaunchpadOutputJob;
use App\Livewire\LaunchpadChat;
use App\Mail\AdminLaunchpadAlertMail;
use App\Mail\LaunchpadDeliveryMail;
use App\Models\Delivery;
use App\Models\Generation;
use App\Models\Lead;
use App\Models\PromptVersion;
use App\Services\LaunchpadPdfService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config()->set('launchpad.chat_rate_limit', 2);
    config()->set('launchpad.admin_alert_email', 'alerts@example.com');
    RateLimiter::clear('launchpad-chat:127.0.0.1');
});

function runChatToEmailSubmit(string $email): void
{
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
        ->set('buyerEmail', $email)->call('submitStep');
}

it('rate limits chat submissions per IP after the configured attempts', function () {
    Bus::fake([GenerateLaunchpadOutputJob::class]);

    runChatToEmailSubmit('one@example.com');
    runChatToEmailSubmit('two@example.com');

    $third = Livewire::test(LaunchpadChat::class)
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
        ->set('buyerEmail', 'three@example.com')->call('submitStep');

    $third->assertHasErrors(['buyerEmail']);
    expect(Lead::where('buyer_email', 'three@example.com')->where('status', 'completed')->count())->toBe(0);
});

it('alerts admin when a generation falls back after two failures', function () {
    config()->set('services.anthropic.api_key', 'test-key');
    PromptVersion::factory()->active()->create(['product' => 'launchpad']);

    $bad = [
        'id' => 'm', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-sonnet-4-6',
        'content' => [['type' => 'text', 'text' => 'not json']],
        'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
    ];
    Http::fake(['api.anthropic.com/*' => Http::sequence()->push($bad)->push($bad)]);

    $lead = Lead::factory()->completed()->create();
    app(GenerateLaunchpadOutput::class)($lead);

    Mail::assertSent(AdminLaunchpadAlertMail::class, function (AdminLaunchpadAlertMail $mail) use ($lead) {
        return $mail->hasTo('alerts@example.com')
            && $mail->lead->id === $lead->id
            && $mail->reason === 'generation fell back after two failures';
    });
});

it('alerts admin when a delivery fails terminally', function () {
    Storage::fake('local');

    $lead = Lead::factory()->generated()->create();
    $generation = Generation::factory()->success()->create([
        'lead_id' => $lead->id,
        'output' => ['email_body' => ['subject' => 'Your starter prompt']],
    ]);

    $this->app->bind(LaunchpadPdfService::class, function () {
        return new class extends LaunchpadPdfService {
            public function render(\App\Models\Lead $lead, \App\Models\Generation $generation): string
            {
                throw new \RuntimeException('PDF broken');
            }
        };
    });

    app(DeliverLaunchpadOutput::class)($lead, $generation);

    Mail::assertSent(AdminLaunchpadAlertMail::class, function (AdminLaunchpadAlertMail $mail) use ($lead) {
        return $mail->reason === 'delivery failed' && $mail->lead->id === $lead->id;
    });
});

it('retries a failed delivery via artisan command and produces a fresh successful Delivery', function () {
    Storage::fake('local');

    $lead = Lead::factory()->generated()->create([
        'buyer_name' => 'Sarah',
        'buyer_email' => 'sarah@example.com',
        'tools_used' => 'Claude CoWork',
    ]);
    Generation::factory()->success()->create([
        'lead_id' => $lead->id,
        'output' => validFullOutput(),
    ]);
    $failed = Delivery::factory()->failed()->create([
        'lead_id' => $lead->id,
        'to_email' => 'sarah@example.com',
    ]);

    $this->artisan('launchpad:retry-deliveries')->assertExitCode(0);

    $failed->refresh();
    expect($failed->status)->toBe('failed');

    $newest = Delivery::where('lead_id', $lead->id)->latest('id')->first();
    expect($newest->id)->not->toBe($failed->id);
    expect($newest->status)->toBe('sent');
    Mail::assertSent(LaunchpadDeliveryMail::class);
});

it('skips retries for leads with no usable generation', function () {
    $lead = Lead::factory()->completed()->create();
    Delivery::factory()->failed()->create(['lead_id' => $lead->id]);

    $this->artisan('launchpad:retry-deliveries')
        ->expectsOutputToContain('has no usable generation')
        ->assertExitCode(0);

    expect(Delivery::count())->toBe(1);
});

function validFullOutput(): array
{
    return [
        'business_type' => 'Coach',
        'assistant_pick' => ['role' => 'Exec', 'reason' => 'Email dominates.'],
        'cover' => ['subtitle' => 'Your starter prompt.', 'time_savings_line' => 'Three to five hours a week.'],
        'your_first_assistant' => [
            'remit_paragraph' => 'Handles inbox.',
            'pain_paragraph' => 'Your week is eaten by email.',
            'time_savings_paragraph' => 'Three to five hours a week.',
        ],
        'starter_prompt' => 'You are the Exec Assistant.',
        'install_steps' => ['Step 1.', 'Step 2.', 'Step 3.'],
        'email_body' => [
            'subject' => 'Your starter prompt',
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
