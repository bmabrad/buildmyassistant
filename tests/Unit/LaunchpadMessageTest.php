<?php

use App\Models\Chat;
use App\Models\Assistant;

it('belongs to a task', function () {
    $task = Assistant::factory()->create();
    $message = Chat::factory()->create(['task_id' => $task->id]);

    expect($message->assistant->id)->toBe($task->id);
});

it('returns claude format', function () {
    $message = Chat::factory()->create([
        'role' => 'user',
        'content' => 'Hello there',
    ]);

    expect($message->toClaudeFormat())->toBe([
        'role' => 'user',
        'content' => 'Hello there',
    ]);
});

it('returns claude format for assistant role', function () {
    $message = Chat::factory()->create([
        'role' => 'assistant',
        'content' => 'Hi! How can I help?',
    ]);

    expect($message->toClaudeFormat())->toBe([
        'role' => 'assistant',
        'content' => 'Hi! How can I help?',
    ]);
});

it('casts is_instruction_sheet to boolean', function () {
    $message = Chat::factory()->create(['is_instruction_sheet' => true]);

    expect($message->is_instruction_sheet)->toBeBool()->toBeTrue();
});

it('auto-sets created_at on creation', function () {
    $message = Chat::factory()->create();

    expect($message->created_at)->not->toBeNull();
});

it('cascades delete with task', function () {
    $task = Assistant::factory()->create();
    Chat::factory()->count(3)->create(['task_id' => $task->id]);

    expect(Chat::where('task_id', $task->id)->count())->toBe(3);

    $task->delete();

    expect(Chat::where('task_id', $task->id)->count())->toBe(0);
});
