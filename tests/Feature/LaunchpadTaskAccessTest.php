<?php

use App\Models\Chat;
use App\Models\Assistant;
use App\Services\ClaudeApiService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::disk('local')->put('launchpad/system_prompt.md', 'Test prompt for {{BUYER_NAME}}');

    $mock = Mockery::mock(ClaudeApiService::class)->makePartial();
    $mock->shouldReceive('streamChat')->andReturnUsing(function () {
        return (function () {
            yield 'Hello!';
        })();
    });
    app()->instance(ClaudeApiService::class, $mock);
});

it('loads the chat page with a valid token', function () {
    $task = Assistant::factory()->create(['status' => 'active']);

    // Pre-seed a message to avoid mount-time streaming
    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    $response = $this->get("/launchpad/{$task->token}");

    $response->assertStatus(200);
});

it('returns 404 for an invalid token', function () {
    $response = $this->get('/launchpad/invalid-token-that-does-not-exist');

    $response->assertStatus(404);
});

it('transitions task from pending to active on first access', function () {
    $task = Assistant::factory()->create(['status' => 'pending']);

    $this->get("/launchpad/{$task->token}");

    expect($task->fresh()->status)->toBe('active');
});

it('does not change status if already active', function () {
    $task = Assistant::factory()->create(['status' => 'active']);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    $this->get("/launchpad/{$task->token}");

    expect($task->fresh()->status)->toBe('active');
});

it('does not change status if already completed', function () {
    $task = Assistant::factory()->create(['status' => 'completed']);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    $this->get("/launchpad/{$task->token}");

    expect($task->fresh()->status)->toBe('completed');
});
