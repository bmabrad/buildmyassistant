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

it('detects instruction sheet marker and sets is_instruction_sheet on message', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Here is your instruction sheet\n\n<!-- INSTRUCTION_SHEET -->";
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

    expect($assistantMessage->is_instruction_sheet)->toBeTrue()
        ->and($assistantMessage->content)->not->toContain('<!-- INSTRUCTION_SHEET -->');
});

it('strips marker from stored content', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Your instructions\n\n<!-- INSTRUCTION_SHEET -->";
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

    $message = $task->chats()->where('is_instruction_sheet', true)->first();

    expect($message->content)->toBe('Your instructions');
});

it('sets playbook_delivered on task when first instruction sheet is delivered', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Instruction sheet content<!-- INSTRUCTION_SHEET -->";
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

it('renders instruction sheet messages with copy and download buttons', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Here is your instruction sheet content',
        'is_instruction_sheet' => true,
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertSee('Copy instructions')
        ->assertSee('Download');
});

it('does not render copy/download buttons on regular messages', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Just a regular message',
        'is_instruction_sheet' => false,
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertDontSee('Copy instructions');
});
