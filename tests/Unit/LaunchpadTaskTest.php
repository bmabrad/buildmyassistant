<?php

use App\Models\Chat;
use App\Models\Assistant;

it('generates a uuid token on creation', function () {
    $task = Assistant::factory()->create(['token' => null]);

    expect($task->token)
        ->not->toBeNull()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
});

it('has a unique token', function () {
    $task1 = Assistant::factory()->create();

    expect(fn () => Assistant::factory()->create(['token' => $task1->token]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('has messages relationship', function () {
    $task = Assistant::factory()->create();
    $message = Chat::factory()->create(['task_id' => $task->id]);

    expect($task->chats)->toHaveCount(1)
        ->and($task->chats->first()->id)->toBe($message->id);
});

it('orders messages by created_at ascending', function () {
    $task = Assistant::factory()->create();

    $older = Chat::factory()->create([
        'task_id' => $task->id,
        'created_at' => now()->subMinutes(5),
    ]);
    $newer = Chat::factory()->create([
        'task_id' => $task->id,
        'created_at' => now(),
    ]);

    $messages = $task->chats()->get();

    expect($messages->first()->id)->toBe($older->id)
        ->and($messages->last()->id)->toBe($newer->id);
});

it('scopes pending tasks', function () {
    Assistant::factory()->create(['status' => 'pending']);
    Assistant::factory()->create(['status' => 'active']);
    Assistant::factory()->create(['status' => 'completed']);

    expect(Assistant::pending()->count())->toBe(1);
});

it('scopes active tasks', function () {
    Assistant::factory()->create(['status' => 'pending']);
    Assistant::factory()->create(['status' => 'active']);

    expect(Assistant::active()->count())->toBe(1);
});

it('scopes completed tasks', function () {
    Assistant::factory()->create(['status' => 'pending']);
    Assistant::factory()->create(['status' => 'completed']);

    expect(Assistant::completed()->count())->toBe(1);
});

it('marks a task as completed', function () {
    $task = Assistant::factory()->create(['status' => 'active']);

    $task->markCompleted();

    expect($task->fresh()->status)->toBe('completed');
});

it('casts phase to integer', function () {
    $task = Assistant::factory()->create(['phase' => 1]);

    expect($task->phase)->toBeInt();
});

it('casts phase_1_complete to boolean', function () {
    $task = Assistant::factory()->create(['phase_1_complete' => false]);

    expect($task->phase_1_complete)->toBeBool();
});
