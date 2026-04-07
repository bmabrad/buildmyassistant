<?php

use App\Livewire\LaunchpadChat;
use App\Models\Chat;
use App\Models\Assistant;
use App\Services\ClaudeApiService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Storage::disk('local')->put('launchpad/system_prompt.md', 'You are a guide. Buyer: {{BUYER_NAME}} ({{BUYER_EMAIL}})');

    $mock = Mockery::mock(ClaudeApiService::class)->makePartial();
    $mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield 'Hello! ';
            yield 'How can I help you today?';
        })();
    });
    app()->instance(ClaudeApiService::class, $mock);

    RateLimiter::clear('launchpad-chat:*');
});

it('allows messages within the rate limit', function () {
    $task = Assistant::factory()->active()->create();
    Chat::factory()->fromAssistant()->create(['task_id' => $task->id]);

    $component = Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'First message')
        ->call('sendMessage');

    $component->assertSet('error', '');
    expect($task->chats()->where('role', 'user')->count())->toBe(1);
});

it('blocks messages after 10 per minute per task', function () {
    $task = Assistant::factory()->active()->create();
    Chat::factory()->fromAssistant()->create(['task_id' => $task->id]);

    // Hit the rate limiter 10 times
    $rateLimitKey = 'launchpad-chat:' . $task->id;
    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit($rateLimitKey, 60);
    }

    $component = Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'This should be blocked')
        ->call('sendMessage');

    $component->assertSet('error', fn ($error) => str_contains($error, 'too quickly'));
    // The user message should NOT have been stored
    expect($task->chats()->where('role', 'user')->where('content', 'This should be blocked')->count())->toBe(0);
});

it('rate limits per task not globally', function () {
    $task1 = Assistant::factory()->active()->create();
    $task2 = Assistant::factory()->active()->create();
    Chat::factory()->fromAssistant()->create(['task_id' => $task1->id]);
    Chat::factory()->fromAssistant()->create(['task_id' => $task2->id]);

    // Exhaust rate limit on task1
    $rateLimitKey = 'launchpad-chat:' . $task1->id;
    for ($i = 0; $i < 10; $i++) {
        RateLimiter::hit($rateLimitKey, 60);
    }

    // Task2 should still be able to send
    Livewire::test(LaunchpadChat::class, ['task' => $task2])
        ->set('input', 'Hello from task 2')
        ->call('sendMessage')
        ->assertSet('error', '');

    expect($task2->chats()->where('role', 'user')->count())->toBe(1);
});

it('rejects messages over 5000 characters', function () {
    $task = Assistant::factory()->active()->create();
    Chat::factory()->fromAssistant()->create(['task_id' => $task->id]);

    $longMessage = str_repeat('a', 5001);

    $component = Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', $longMessage)
        ->call('sendMessage');

    $component->assertSet('error', fn ($error) => str_contains($error, 'too long'));
    expect($task->chats()->where('role', 'user')->count())->toBe(0);
});

it('allows messages at exactly 5000 characters', function () {
    $task = Assistant::factory()->active()->create();
    Chat::factory()->fromAssistant()->create(['task_id' => $task->id]);

    $exactMessage = str_repeat('a', 5000);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', $exactMessage)
        ->call('sendMessage')
        ->assertSet('error', '');

    expect($task->chats()->where('role', 'user')->count())->toBe(1);
});

it('clears error on next valid send', function () {
    $task = Assistant::factory()->active()->create();
    Chat::factory()->fromAssistant()->create(['task_id' => $task->id]);

    // Send a too-long message to trigger error
    $component = Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', str_repeat('a', 5001))
        ->call('sendMessage');

    $component->assertSet('error', fn ($error) => str_contains($error, 'too long'));

    // Send a valid message — error should clear
    $component->set('input', 'Valid message')
        ->call('sendMessage')
        ->assertSet('error', '');
});

it('silently ignores empty messages', function () {
    $task = Assistant::factory()->active()->create();
    Chat::factory()->fromAssistant()->create(['task_id' => $task->id]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', '   ')
        ->call('sendMessage');

    expect($task->chats()->where('role', 'user')->count())->toBe(0);
});
