<?php

use App\Livewire\LaunchpadChat;
use App\Models\LaunchpadMessage;
use App\Models\LaunchpadTask;
use App\Services\ClaudeApiService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    Storage::disk('local')->put('launchpad/system_prompt.md', 'Test prompt for {{BUYER_NAME}}');

    $this->mock = Mockery::mock(ClaudeApiService::class)->makePartial();
    app()->instance(ClaudeApiService::class, $this->mock);
});

it('detects instruction sheet marker and sets is_instruction_sheet on message', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Here is your instruction sheet\n\n<!-- INSTRUCTION_SHEET -->";
        })();
    });

    $task = LaunchpadTask::factory()->active()->create(['phase' => 1]);

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Build me an assistant')
        ->call('sendMessage');

    $assistantMessage = $task->messages()->where('role', 'assistant')->latest('id')->first();

    expect($assistantMessage->is_instruction_sheet)->toBeTrue()
        ->and($assistantMessage->content)->not->toContain('<!-- INSTRUCTION_SHEET -->');
});

it('strips marker from stored content', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Your instructions\n\n<!-- INSTRUCTION_SHEET -->";
        })();
    });

    $task = LaunchpadTask::factory()->active()->create();

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Help me')
        ->call('sendMessage');

    $message = $task->messages()->where('is_instruction_sheet', true)->first();

    expect($message->content)->toBe('Your instructions');
});

it('sets phase_1_complete on task when first instruction sheet is delivered', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Instruction sheet content<!-- INSTRUCTION_SHEET -->";
        })();
    });

    $task = LaunchpadTask::factory()->active()->create([
        'phase' => 1,
        'phase_1_complete' => false,
    ]);

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Build it')
        ->call('sendMessage');

    expect($task->fresh()->phase_1_complete)->toBeTrue();
});

it('sets task to completed when second instruction sheet is delivered', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield "Updated sheet<!-- INSTRUCTION_SHEET -->";
        })();
    });

    $task = LaunchpadTask::factory()->active()->create([
        'phase' => 1,
        'phase_1_complete' => true,
    ]);

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Go deeper')
        ->call('sendMessage');

    $fresh = $task->fresh();
    expect($fresh->status)->toBe('completed')
        ->and($fresh->phase)->toBe(2);
});

it('transitions to phase 2 when user sends message after phase_1_complete', function () {
    $this->mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield 'Great, let us dig deeper.';
        })();
    });

    $task = LaunchpadTask::factory()->active()->create([
        'phase' => 1,
        'phase_1_complete' => true,
    ]);

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->set('input', 'Yes, let us go deeper')
        ->call('sendMessage');

    expect($task->fresh()->phase)->toBe(2);
});

it('renders instruction sheet messages with copy and download buttons', function () {
    $task = LaunchpadTask::factory()->active()->create();

    LaunchpadMessage::factory()->create([
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
    $task = LaunchpadTask::factory()->active()->create();

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Just a regular message',
        'is_instruction_sheet' => false,
    ]);

    Livewire::test(LaunchpadChat::class, ['task' => $task])
        ->assertDontSee('Copy instructions');
});
