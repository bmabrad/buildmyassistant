<?php

use App\Models\LaunchpadMessage;
use App\Models\LaunchpadTask;

it('generates a uuid token on creation', function () {
    $task = LaunchpadTask::factory()->create(['token' => null]);

    expect($task->token)
        ->not->toBeNull()
        ->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i');
});

it('has a unique token', function () {
    $task1 = LaunchpadTask::factory()->create();

    expect(fn () => LaunchpadTask::factory()->create(['token' => $task1->token]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

it('has messages relationship', function () {
    $task = LaunchpadTask::factory()->create();
    $message = LaunchpadMessage::factory()->create(['task_id' => $task->id]);

    expect($task->messages)->toHaveCount(1)
        ->and($task->messages->first()->id)->toBe($message->id);
});

it('orders messages by created_at ascending', function () {
    $task = LaunchpadTask::factory()->create();

    $older = LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'created_at' => now()->subMinutes(5),
    ]);
    $newer = LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'created_at' => now(),
    ]);

    $messages = $task->messages()->get();

    expect($messages->first()->id)->toBe($older->id)
        ->and($messages->last()->id)->toBe($newer->id);
});

it('scopes pending tasks', function () {
    LaunchpadTask::factory()->create(['status' => 'pending']);
    LaunchpadTask::factory()->create(['status' => 'active']);
    LaunchpadTask::factory()->create(['status' => 'completed']);

    expect(LaunchpadTask::pending()->count())->toBe(1);
});

it('scopes active tasks', function () {
    LaunchpadTask::factory()->create(['status' => 'pending']);
    LaunchpadTask::factory()->create(['status' => 'active']);

    expect(LaunchpadTask::active()->count())->toBe(1);
});

it('scopes completed tasks', function () {
    LaunchpadTask::factory()->create(['status' => 'pending']);
    LaunchpadTask::factory()->create(['status' => 'completed']);

    expect(LaunchpadTask::completed()->count())->toBe(1);
});

it('marks a task as completed', function () {
    $task = LaunchpadTask::factory()->create(['status' => 'active']);

    $task->markCompleted();

    expect($task->fresh()->status)->toBe('completed');
});

it('casts phase to integer', function () {
    $task = LaunchpadTask::factory()->create(['phase' => 1]);

    expect($task->phase)->toBeInt();
});

it('casts phase_1_complete to boolean', function () {
    $task = LaunchpadTask::factory()->create(['phase_1_complete' => false]);

    expect($task->phase_1_complete)->toBeBool();
});
