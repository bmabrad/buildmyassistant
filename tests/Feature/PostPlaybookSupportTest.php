<?php

use App\Livewire\LaunchpadChat;
use App\Models\Chat;
use App\Models\Assistant;
use App\Services\ClaudeApiService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();

    $mock = Mockery::mock(ClaudeApiService::class)->makePartial();
    $mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield 'Here is some help.';
        })();
    });
    $mock->shouldReceive('getLastStreamUsage')->andReturn([
        'input_tokens' => 200,
        'output_tokens' => 100,
    ]);
    app()->instance(ClaudeApiService::class, $mock);
});

// ─────────────────────────────────────────────
// Model: Support Window
// ─────────────────────────────────────────────

it('reports support window open within 7 days of Playbook delivery', function () {
    $task = Assistant::factory()->postPlaybook()->create([
        'session_completed_at' => now()->subDays(3),
    ]);

    expect($task->isSupportWindowOpen())->toBeTrue()
        ->and($task->isChatLocked())->toBeFalse();
});

it('reports support window closed after 7 days', function () {
    $task = Assistant::factory()->expiredSupport()->create();

    expect($task->isSupportWindowOpen())->toBeFalse()
        ->and($task->isChatLocked())->toBeTrue()
        ->and($task->lockReason())->toBe('expired');
});

it('is not locked before Playbook delivery', function () {
    $task = Assistant::factory()->active()->create();

    expect($task->isChatLocked())->toBeFalse()
        ->and($task->isSupportWindowOpen())->toBeFalse()
        ->and($task->lockReason())->toBeNull();
});

// ─────────────────────────────────────────────
// Model: Days Remaining
// ─────────────────────────────────────────────

it('calculates days remaining correctly', function () {
    $task = Assistant::factory()->postPlaybook()->create([
        'session_completed_at' => now()->subDays(3),
    ]);

    $remaining = $task->supportDaysRemaining();

    expect($remaining)->toBe(4);
});

it('returns -1 sentinel when less than 24 hours remain', function () {
    $task = Assistant::factory()->postPlaybook()->create([
        'session_completed_at' => now()->subDays(6)->subHours(10),
    ]);

    expect($task->supportDaysRemaining())->toBe(-1);
});

it('returns 0 when window has expired', function () {
    $task = Assistant::factory()->expiredSupport()->create();

    expect($task->supportDaysRemaining())->toBe(0);
});

it('returns null before Playbook delivery', function () {
    $task = Assistant::factory()->active()->create();

    expect($task->supportDaysRemaining())->toBeNull();
});

// ─────────────────────────────────────────────
// Model: Token Limits
// ─────────────────────────────────────────────

it('is not token limited when no limit is configured', function () {
    config(['services.launchpad.token_limit' => 0]);

    $task = Assistant::factory()->postPlaybook()->create([
        'total_input_tokens' => 999999,
        'total_output_tokens' => 999999,
    ]);

    expect($task->isTokenLimitReached())->toBeFalse();
});

it('detects when token limit is reached', function () {
    config(['services.launchpad.token_limit' => 50000]);

    $task = Assistant::factory()->postPlaybook()->create([
        'total_input_tokens' => 30000,
        'total_output_tokens' => 20000,
    ]);

    expect($task->isTokenLimitReached())->toBeTrue()
        ->and($task->isChatLocked())->toBeTrue()
        ->and($task->lockReason())->toBe('tokens');
});

it('records token usage correctly', function () {
    $task = Assistant::factory()->postPlaybook()->create([
        'total_input_tokens' => 100,
        'total_output_tokens' => 50,
    ]);

    $task->recordTokenUsage(200, 100);

    $task->refresh();
    expect($task->total_input_tokens)->toBe(300)
        ->and($task->total_output_tokens)->toBe(150);
});

// ─────────────────────────────────────────────
// Livewire: Chat Lockout
// ─────────────────────────────────────────────

it('rejects messages when support window has expired', function () {
    $task = Assistant::factory()->expiredSupport()->create();

    // Pre-seed a message
    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Your Playbook is ready.',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Can you help me?')
        ->call('sendMessage')
        ->assertSet('error', 'Your 7-day support window has closed.');

    // Only the pre-seeded message should exist
    expect($task->chats()->count())->toBe(1);
});

it('allows messages within the support window', function () {
    $task = Assistant::factory()->postPlaybook()->create([
        'session_completed_at' => now()->subDays(3),
    ]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Your Playbook is ready.',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Can you help me set this up?')
        ->call('sendMessage')
        ->call('streamResponse');

    expect($task->chats()->count())->toBe(3); // greeting + user + assistant
});

it('rejects messages when token limit is reached', function () {
    config(['services.launchpad.token_limit' => 1000]);

    $task = Assistant::factory()->postPlaybook()->create([
        'session_completed_at' => now()->subDays(1),
        'total_input_tokens' => 600,
        'total_output_tokens' => 500,
    ]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello.',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'More help please')
        ->call('sendMessage')
        ->assertSet('error', "You've used your available support messages.");
});

// ─────────────────────────────────────────────
// Livewire: Playbook Delivery Transition
// ─────────────────────────────────────────────

it('sets session_completed_at on Playbook delivery', function () {
    $mock = Mockery::mock(ClaudeApiService::class)->makePartial();
    $mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield '<!-- INSTRUCTION_SHEET -->';
            yield 'Here is your Playbook...';
        })();
    });
    $mock->shouldReceive('getLastStreamUsage')->andReturn([
        'input_tokens' => 500,
        'output_tokens' => 300,
    ]);
    app()->instance(ClaudeApiService::class, $mock);

    $task = Assistant::factory()->active()->create(['phase' => 1]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'I am ready')
        ->call('sendMessage')
        ->call('streamResponse');

    $task->refresh();

    expect($task->playbook_delivered)->toBeTrue()
        ->and($task->in_post_playbook)->toBeTrue()
        ->and($task->session_completed_at)->not->toBeNull()
        ->and($task->status)->toBe('completed');
});

// ─────────────────────────────────────────────
// View: Countdown and Lockout Display
// ─────────────────────────────────────────────

it('shows countdown during active Post-Playbook window', function () {
    $task = Assistant::factory()->postPlaybook()->create([
        'session_completed_at' => now()->subDays(3),
    ]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Your Playbook.',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertSee('days of support remaining');
});

it('shows lockout message when support window has expired', function () {
    $task = Assistant::factory()->expiredSupport()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Your Playbook.',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertSee('Your 7-day support window has closed')
        ->assertSee('check out Fast Track');
});

it('does not show countdown during Pre-Playbook mode', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertDontSee('days of support remaining');
});

// ─────────────────────────────────────────────
// Token Tracking in Chat Flow
// ─────────────────────────────────────────────

it('tracks token usage after each response', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Help me please')
        ->call('sendMessage')
        ->call('streamResponse');

    $task->refresh();

    expect($task->total_input_tokens)->toBe(200)
        ->and($task->total_output_tokens)->toBe(100);
});
