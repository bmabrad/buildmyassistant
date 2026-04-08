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
    Storage::disk('local')->put('launchpad/system_prompt.md', 'Test prompt for {{BUYER_NAME}}');

    $this->mock = Mockery::mock(ClaudeApiService::class)->makePartial();
    $this->mock->shouldReceive('getLastStreamUsage')->andReturn([
        'input_tokens' => 100,
        'output_tokens' => 50,
    ]);
    app()->instance(ClaudeApiService::class, $this->mock);
});

it('detects deliverable marker and sets is_deliverable on message', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Here is your Playbook\n\n<!-- INSTRUCTION_SHEET -->";
        })();
    });

    $task = Assistant::factory()->active()->create(['phase' => 1]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Build me an assistant')
        ->call('sendMessage')
        ->call('streamResponse');

    $assistantMessage = $task->chats()->where('role', 'assistant')->latest('id')->first();

    expect($assistantMessage->is_deliverable)->toBeTrue()
        ->and($assistantMessage->content)->not->toContain('<!-- INSTRUCTION_SHEET -->');
});

it('strips marker from stored content', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Your Playbook\n\n<!-- INSTRUCTION_SHEET -->";
        })();
    });

    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Help me')
        ->call('sendMessage')
        ->call('streamResponse');

    $message = $task->chats()->where('is_deliverable', true)->first();

    expect($message->content)->toBe('Your Playbook');
});

it('parses playbook and instructions content into separate columns', function () {
    $deliverableContent = "## 1. Your Bottleneck\nYou spend 3 hours a day on email.\n\n## 2. Your Process Map\nTriage, draft, send.\n\n<!-- INSTRUCTIONS_START -->\n\n# Sarah — AI Assistant for Jane\n\n## Role\nYou are Sarah.";

    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () use ($deliverableContent) {
        return (function () use ($deliverableContent) {
            yield $deliverableContent . "\n\n<!-- INSTRUCTION_SHEET -->";
        })();
    });

    $task = Assistant::factory()->active()->create(['phase' => 1]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Build it')
        ->call('sendMessage')
        ->call('streamResponse');

    $message = $task->chats()->where('is_deliverable', true)->first();

    expect($message->playbook_content)->toContain('Your Bottleneck')
        ->and($message->playbook_content)->not->toContain('# Sarah')
        ->and($message->instructions_content)->toContain('# Sarah — AI Assistant for Jane')
        ->and($message->instructions_content)->toContain('## Role');
});

it('sets playbook_delivered on task when first deliverable is delivered', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Playbook content<!-- INSTRUCTION_SHEET -->";
        })();
    });

    $task = Assistant::factory()->active()->create([
        'phase' => 1,
        'playbook_delivered' => false,
    ]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Build it')
        ->call('sendMessage')
        ->call('streamResponse');

    expect($task->fresh()->playbook_delivered)->toBeTrue();
});

it('sets task to completed and transitions to Post-Playbook on Playbook delivery', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Your Playbook<!-- INSTRUCTION_SHEET -->";
        })();
    });

    $task = Assistant::factory()->active()->create([
        'phase' => 1,
        'playbook_delivered' => false,
    ]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Build it')
        ->call('sendMessage')
        ->call('streamResponse');

    $fresh = $task->fresh();
    expect($fresh->status)->toBe('completed')
        ->and($fresh->playbook_delivered)->toBeTrue()
        ->and($fresh->in_post_playbook)->toBeTrue()
        ->and($fresh->session_completed_at)->not->toBeNull();
});

it('renders deliverable messages with copy and two download buttons', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->deliverable()->create([
        'task_id' => $task->id,
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertSee('Copy instructions')
        ->assertSee('Download Playbook')
        ->assertSee('Download Instructions');
});

it('does not render copy/download buttons on regular messages', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Just a regular message',
        'is_deliverable' => false,
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertDontSee('Copy instructions');
});
