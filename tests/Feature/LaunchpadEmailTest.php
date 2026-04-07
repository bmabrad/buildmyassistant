<?php

use App\Mail\LaunchpadCompletionMail;
use App\Models\Chat;
use App\Models\Assistant;
use App\Services\ClaudeApiService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

$instructionSheetContent = <<<'MD'
## Assistant name

Sarah

## What the assistant handles

Writing follow-up emails after discovery calls, including personalised summaries and next steps.

## System prompt

You are Sarah, a follow-up email assistant...

## Setup steps

1. Open Claude CoWork
2. Create a new project

## First test task

Write a follow-up email for a call with Jane about her marketing strategy.
MD;

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
    Mail::fake();
    Storage::fake('local');
    Storage::disk('local')->put('launchpad/system_prompt.md', 'You are a guide. Buyer: {{BUYER_NAME}} ({{BUYER_EMAIL}})');
});

it('queues completion email when task status changes to completed', function () use ($instructionSheetContent) {
    $task = Assistant::factory()->active()->create([
        'phase' => 1,
        'phase_1_complete' => true,
        'name' => 'Brad',
        'email' => 'brad@example.com',
    ]);

    // Seed a prior message so mount doesn't trigger streaming
    Chat::factory()->fromAssistant()->create(['task_id' => $task->id]);

    // Mock Claude to return a Phase 2 instruction sheet
    $mock = Mockery::mock(ClaudeApiService::class)->makePartial();
    $mock->shouldReceive('streamChat')->andReturnUsing(function () use ($instructionSheetContent) {
        return (function () use ($instructionSheetContent) {
            yield $instructionSheetContent;
            yield "\n<!-- INSTRUCTION_SHEET -->";
        })();
    });
    app()->instance(ClaudeApiService::class, $mock);

    Livewire::test(\App\Livewire\LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Yes, let us go deeper')
        ->call('sendMessage')
        ->call('streamResponse');

    $task->refresh();
    expect($task->status)->toBe('completed');

    Mail::assertQueued(LaunchpadCompletionMail::class, function ($mail) use ($task) {
        return $mail->hasTo('brad@example.com')
            && $mail->task->id === $task->id;
    });
});

it('does not send email when phase 1 instruction sheet is delivered', function () use ($instructionSheetContent) {
    $task = Assistant::factory()->active()->create([
        'phase' => 1,
        'phase_1_complete' => false,
    ]);

    Chat::factory()->fromAssistant()->create(['task_id' => $task->id]);

    $mock = Mockery::mock(ClaudeApiService::class)->makePartial();
    $mock->shouldReceive('streamChat')->andReturnUsing(function () use ($instructionSheetContent) {
        return (function () use ($instructionSheetContent) {
            yield $instructionSheetContent;
            yield "\n<!-- INSTRUCTION_SHEET -->";
        })();
    });
    app()->instance(ClaudeApiService::class, $mock);

    Livewire::test(\App\Livewire\LaunchpadChat::class, ['task' => $task])
        ->set('input', 'I want to automate follow-up emails')
        ->call('sendMessage')
        ->call('streamResponse');

    $task->refresh();
    expect($task->phase_1_complete)->toBeTrue();
    expect($task->status)->not->toBe('completed');

    Mail::assertNotQueued(LaunchpadCompletionMail::class);
});

it('email contains buyer name and chat link', function () use ($instructionSheetContent) {
    $task = Assistant::factory()->completed()->create([
        'name' => 'Brad',
        'email' => 'brad@example.com',
    ]);

    Chat::factory()->instructionSheet()->create([
        'task_id' => $task->id,
        'content' => $instructionSheetContent,
    ]);

    $mail = new LaunchpadCompletionMail($task);

    expect($mail->buyerName)->toBe('Brad');
    expect($mail->chatUrl)->toContain("/launchpad/{$task->token}");
    expect($mail->assistantName)->toBe('Sarah');
    expect($mail->assistantHandles)->toContain('follow-up emails');
});

it('email has correct subject line', function () {
    $task = Assistant::factory()->completed()->create();

    $mail = new LaunchpadCompletionMail($task);

    expect($mail->envelope()->subject)->toBe('Your AI assistant instructions are ready');
});

it('extracts assistant name from different markdown formats', function () {
    // Heading format
    expect(LaunchpadCompletionMail::extractAssistantName("## Assistant name\n\nSarah"))->toBe('Sarah');

    // Bold format
    expect(LaunchpadCompletionMail::extractAssistantName('**Assistant name:** James'))->toBe('James');

    // Fallback when no match
    expect(LaunchpadCompletionMail::extractAssistantName('No name here'))->toBe('your AI assistant');
});

it('extracts what the assistant handles from different formats', function () {
    // Heading format
    $content = "## What the assistant handles\n\nWriting follow-up emails after calls.\n\n## System prompt";
    expect(LaunchpadCompletionMail::extractAssistantHandles($content))->toBe('Writing follow-up emails after calls.');

    // Bold format
    $content = '**What the assistant handles:** Creating weekly LinkedIn posts';
    expect(LaunchpadCompletionMail::extractAssistantHandles($content))->toBe('Creating weekly LinkedIn posts');

    // Fallback
    expect(LaunchpadCompletionMail::extractAssistantHandles('Nothing relevant'))->toBe('the process you described');
});

it('renders the email template without errors', function () use ($instructionSheetContent) {
    $task = Assistant::factory()->completed()->create([
        'name' => 'Brad',
    ]);

    Chat::factory()->instructionSheet()->create([
        'task_id' => $task->id,
        'content' => $instructionSheetContent,
    ]);

    $mail = new LaunchpadCompletionMail($task);
    $rendered = $mail->render();

    expect($rendered)->toContain('Hi Brad');
    expect($rendered)->toContain('Sarah');
    expect($rendered)->toContain('View your instructions');
    expect($rendered)->toContain("/launchpad/{$task->token}");
    expect($rendered)->toContain('Build My Assistant');
});

it('completion email does not contain invoice link', function () use ($instructionSheetContent) {
    $task = Assistant::factory()->completed()->create([
        'name' => 'Brad',
        'stripe_invoice_url' => 'https://invoice.stripe.com/i/acct_123/test_inv_456',
    ]);

    Chat::factory()->instructionSheet()->create([
        'task_id' => $task->id,
        'content' => $instructionSheetContent,
    ]);

    $mail = new LaunchpadCompletionMail($task);
    $rendered = $mail->render();

    expect($rendered)->not->toContain('View your invoice');
});

it('post-purchase email includes invoice link when present', function () {
    $task = Assistant::factory()->create([
        'name' => 'Brad',
        'email' => 'brad@example.com',
        'stripe_invoice_url' => 'https://invoice.stripe.com/i/acct_123/test_inv_456',
    ]);

    $mail = new \App\Mail\PostPurchaseMail($task);
    $rendered = $mail->render();

    expect($mail->buyerName)->toBe('Brad');
    expect($mail->chatUrl)->toContain("/launchpad/{$task->token}");
    expect($mail->invoiceUrl)->toBe('https://invoice.stripe.com/i/acct_123/test_inv_456');
    expect($rendered)->toContain('Thanks for your purchase, Brad');
    expect($rendered)->toContain('Start your session');
    expect($rendered)->toContain('View your invoice');
    expect($rendered)->toContain('https://invoice.stripe.com/i/acct_123/test_inv_456');
});

it('post-purchase email omits invoice link when not present', function () {
    $task = Assistant::factory()->create([
        'name' => 'Brad',
        'stripe_invoice_url' => null,
    ]);

    $mail = new \App\Mail\PostPurchaseMail($task);
    $rendered = $mail->render();

    expect($rendered)->toContain('Thanks for your purchase, Brad');
    expect($rendered)->not->toContain('View your invoice');
    expect($mail->invoiceUrl)->toBeNull();
});

it('post-purchase email has correct subject line', function () {
    $task = Assistant::factory()->create();

    $mail = new \App\Mail\PostPurchaseMail($task);

    expect($mail->envelope()->subject)->toBe('Your AI Assistant Launchpad is ready to go');
});
