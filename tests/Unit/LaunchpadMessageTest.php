<?php

use App\Models\LaunchpadMessage;
use App\Models\LaunchpadTask;

it('belongs to a task', function () {
    $task = LaunchpadTask::factory()->create();
    $message = LaunchpadMessage::factory()->create(['task_id' => $task->id]);

    expect($message->task->id)->toBe($task->id);
});

it('returns claude format', function () {
    $message = LaunchpadMessage::factory()->create([
        'role' => 'user',
        'content' => 'Hello there',
    ]);

    expect($message->toClaudeFormat())->toBe([
        'role' => 'user',
        'content' => 'Hello there',
    ]);
});

it('returns claude format for assistant role', function () {
    $message = LaunchpadMessage::factory()->create([
        'role' => 'assistant',
        'content' => 'Hi! How can I help?',
    ]);

    expect($message->toClaudeFormat())->toBe([
        'role' => 'assistant',
        'content' => 'Hi! How can I help?',
    ]);
});

it('casts is_instruction_sheet to boolean', function () {
    $message = LaunchpadMessage::factory()->create(['is_instruction_sheet' => true]);

    expect($message->is_instruction_sheet)->toBeBool()->toBeTrue();
});

it('auto-sets created_at on creation', function () {
    $message = LaunchpadMessage::factory()->create();

    expect($message->created_at)->not->toBeNull();
});

it('cascades delete with task', function () {
    $task = LaunchpadTask::factory()->create();
    LaunchpadMessage::factory()->count(3)->create(['task_id' => $task->id]);

    expect(LaunchpadMessage::where('task_id', $task->id)->count())->toBe(3);

    $task->delete();

    expect(LaunchpadMessage::where('task_id', $task->id)->count())->toBe(0);
});
