<?php

use App\Models\LaunchpadMessage;
use App\Models\LaunchpadTask;
use App\Services\ClaudeApiService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::disk('local')->put('launchpad/system_prompt.md', 'Hello {{BUYER_NAME}}, your email is {{BUYER_EMAIL}}.');
});

it('replaces buyer name and email in system prompt', function () {
    $task = LaunchpadTask::factory()->create([
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
    ]);

    $service = new ClaudeApiService();
    $prompt = $service->getSystemPrompt($task);

    expect($prompt)
        ->toContain('Alice Johnson')
        ->toContain('alice@example.com')
        ->not->toContain('{{BUYER_NAME}}')
        ->not->toContain('{{BUYER_EMAIL}}');
});

it('builds messages array from stored messages', function () {
    $task = LaunchpadTask::factory()->create();

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
        'created_at' => now()->subMinutes(2),
    ]);
    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'user',
        'content' => 'Hi there',
        'created_at' => now()->subMinute(),
    ]);

    $service = new ClaudeApiService();
    $messages = $service->buildMessages($task);

    expect($messages)->toHaveCount(2)
        ->and($messages[0])->toBe(['role' => 'assistant', 'content' => 'Hello!'])
        ->and($messages[1])->toBe(['role' => 'user', 'content' => 'Hi there']);
});

it('appends user message to messages array when provided', function () {
    $task = LaunchpadTask::factory()->create();

    LaunchpadMessage::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Hello!',
    ]);

    $service = new ClaudeApiService();
    $messages = $service->buildMessages($task, 'My new message');

    expect($messages)->toHaveCount(2)
        ->and($messages[1])->toBe(['role' => 'user', 'content' => 'My new message']);
});

it('returns empty array when no messages and no user message (auto-greeting)', function () {
    $task = LaunchpadTask::factory()->create();

    $service = new ClaudeApiService();
    $messages = $service->buildMessages($task);

    expect($messages)->toBeEmpty();
});
