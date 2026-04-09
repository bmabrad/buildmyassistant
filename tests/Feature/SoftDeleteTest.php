<?php

use App\Models\Chat;
use App\Models\Assistant;
use App\Models\User;

it('soft deletes a user and cascades to their tasks and messages', function () {
    $user = User::factory()->create();
    $task = Assistant::factory()->create(['user_id' => $user->id]);
    Chat::factory()->count(3)->create(['task_id' => $task->id]);

    $user->delete();

    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and(Assistant::find($task->id))->toBeNull()
        ->and(Assistant::withTrashed()->find($task->id))->not->toBeNull()
        ->and(Chat::where('task_id', $task->id)->count())->toBe(0)
        ->and(Chat::withTrashed()->where('task_id', $task->id)->count())->toBe(3);
});

it('restores a user and cascades to their tasks and messages', function () {
    $user = User::factory()->create();
    $task = Assistant::factory()->create(['user_id' => $user->id]);
    Chat::factory()->count(3)->create(['task_id' => $task->id]);

    $user->delete();
    $user->restore();

    expect(User::find($user->id))->not->toBeNull()
        ->and(Assistant::find($task->id))->not->toBeNull()
        ->and(Chat::where('task_id', $task->id)->count())->toBe(3);
});

it('force deletes a user and permanently removes tasks and messages', function () {
    $user = User::factory()->create();
    $task = Assistant::factory()->create(['user_id' => $user->id]);
    Chat::factory()->count(3)->create(['task_id' => $task->id]);

    $user->forceDelete();

    expect(User::withTrashed()->find($user->id))->toBeNull()
        ->and(Assistant::withTrashed()->find($task->id))->toBeNull()
        ->and(Chat::withTrashed()->where('task_id', $task->id)->count())->toBe(0);
});

it('soft deletes a task and cascades to its messages', function () {
    $task = Assistant::factory()->create();
    Chat::factory()->count(5)->create(['task_id' => $task->id]);

    $task->delete();

    expect(Assistant::find($task->id))->toBeNull()
        ->and(Assistant::withTrashed()->find($task->id))->not->toBeNull()
        ->and(Chat::where('task_id', $task->id)->count())->toBe(0)
        ->and(Chat::withTrashed()->where('task_id', $task->id)->count())->toBe(5);
});

it('restores a task and cascades to its messages', function () {
    $task = Assistant::factory()->create();
    Chat::factory()->count(5)->create(['task_id' => $task->id]);

    $task->delete();
    $task->restore();

    expect(Assistant::find($task->id))->not->toBeNull()
        ->and(Chat::where('task_id', $task->id)->count())->toBe(5);
});

it('does not affect other users tasks when deleting a user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $task1 = Assistant::factory()->create(['user_id' => $user1->id]);
    $task2 = Assistant::factory()->create(['user_id' => $user2->id]);

    $user1->delete();

    expect(Assistant::find($task1->id))->toBeNull()
        ->and(Assistant::find($task2->id))->not->toBeNull();
});
