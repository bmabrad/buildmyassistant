<?php

use App\Models\LaunchpadTask;

it('loads the chat page with a valid token', function () {
    $task = LaunchpadTask::factory()->create(['status' => 'active']);

    $response = $this->get("/launchpad/{$task->token}");

    $response->assertStatus(200);
    $response->assertSee($task->name);
});

it('returns 404 for an invalid token', function () {
    $response = $this->get('/launchpad/invalid-token-that-does-not-exist');

    $response->assertStatus(404);
});

it('transitions task from pending to active on first access', function () {
    $task = LaunchpadTask::factory()->create(['status' => 'pending']);

    $this->get("/launchpad/{$task->token}");

    expect($task->fresh()->status)->toBe('active');
});

it('does not change status if already active', function () {
    $task = LaunchpadTask::factory()->create(['status' => 'active']);

    $this->get("/launchpad/{$task->token}");

    expect($task->fresh()->status)->toBe('active');
});

it('does not change status if already completed', function () {
    $task = LaunchpadTask::factory()->create(['status' => 'completed']);

    $this->get("/launchpad/{$task->token}");

    expect($task->fresh()->status)->toBe('completed');
});
