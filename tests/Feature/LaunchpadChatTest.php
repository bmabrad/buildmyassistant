<?php

use App\Livewire\LaunchpadChat;
use App\Models\Chat;
use App\Models\Assistant;
use App\Services\ClaudeApiService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    \Illuminate\Support\Facades\Cache::flush();
    Storage::fake('local');
    Storage::disk('local')->put('launchpad/system_prompt.md', 'You are a guide. Buyer: {{BUYER_NAME}} ({{BUYER_EMAIL}})');

    // Mock ClaudeApiService to avoid real API calls
    $mock = Mockery::mock(ClaudeApiService::class)->makePartial();
    $mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield 'Hello! ';
            yield 'How can I help you today?';
        })();
    });
    $mock->shouldReceive('streamWithDirective')->andReturnUsing(function () {
        return (function () {
            yield 'Hello! ';
            yield 'How can I help you today?';
        })();
    });
    $mock->shouldReceive('streamPlaybook')->andReturnUsing(function () {
        return (function () {
            yield '**1. Your Bottleneck**' . "\n\nTest content\n\n";
            yield '<!-- INSTRUCTIONS_START -->' . "\n\n";
            yield '# Sarah — AI Assistant' . "\n\n## Role\nYou are Sarah.";
        })();
    });
    $mock->shouldReceive('getLastStreamUsage')->andReturn([
        'input_tokens' => 100,
        'output_tokens' => 50,
    ]);
    app()->instance(ClaudeApiService::class, $mock);
});

it('loads the chat page for a valid token', function () {
    $task = Assistant::factory()->active()->create();

    // Pre-seed a message so mount doesn't trigger streaming
    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    $response = $this->get("/launchpad/{$task->token}");

    $response->assertStatus(200);
    $response->assertSee('AI Assistant Launchpad');
});

it('auto-generates greeting on first visit', function () {
    $task = Assistant::factory()->active()->create();

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertSet('needsGreeting', true)
        ->call('generateGreeting')
        ->assertSet('isStreaming', false);

    // The greeting should have been stored
    expect($task->chats()->count())->toBe(1);

    $greeting = $task->chats()->first();
    expect($greeting->role)->toBe('assistant')
        ->and($greeting->content)->toBe('Hello! How can I help you today?')
        ->and($greeting->phase)->toBe(1);
});

it('does not auto-generate greeting on return visit', function () {
    $task = Assistant::factory()->active()->create();

    // Pre-existing messages
    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Previous greeting',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task]);

    // Should still only have the one pre-existing message
    expect($task->chats()->count())->toBe(1);
});

it('stores user message and assistant response when sending', function () {
    $task = Assistant::factory()->active()->create();

    // Pre-seed a greeting so mount doesn't trigger streaming
    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'I need help with email follow-ups')
        ->call('sendMessage')
        ->call('streamResponse');

    $messages = $task->chats()->orderBy('created_at', 'asc')->get();

    expect($messages)->toHaveCount(3)
        ->and($messages[1]->role)->toBe('user')
        ->and($messages[1]->content)->toBe('I need help with email follow-ups')
        ->and($messages[2]->role)->toBe('assistant')
        ->and($messages[2]->content)->toBe('Hello! How can I help you today?');
});

it('sets correct phase on messages', function () {
    $task = Assistant::factory()->active()->create(['phase' => 1]);

    // Pre-seed a greeting
    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Test message')
        ->call('sendMessage')
        ->call('streamResponse');

    $userMessage = $task->chats()->where('role', 'user')->first();
    expect($userMessage->phase)->toBe(1);
});

it('does not send empty messages', function () {
    $task = Assistant::factory()->active()->create();

    // Pre-seed a greeting
    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', '   ')
        ->call('sendMessage');

    // Only the pre-seeded greeting should exist
    expect($task->chats()->count())->toBe(1);
});

it('displays existing messages on return visit', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Welcome back!',
    ]);
    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'user',
        'content' => 'Thanks!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertSee('Welcome back!')
        ->assertSee('Thanks!');
});

it('clears input after sending', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'My message')
        ->call('sendMessage')
        ->assertSet('input', '');
});
