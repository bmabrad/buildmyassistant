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

it('downloads instruction sheet as text file', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => '# My Assistant Instructions',
        'is_instruction_sheet' => true,
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.txt");

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="your-assistant-instructions.txt"');

    expect($response->getContent())->toBe('# My Assistant Instructions');
});

it('returns the most recent instruction sheet', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Phase 1 sheet',
        'is_instruction_sheet' => true,
        'created_at' => now()->subMinutes(5),
    ]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Phase 2 sheet (updated)',
        'is_instruction_sheet' => true,
        'created_at' => now(),
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.txt");

    expect($response->getContent())->toBe('Phase 2 sheet (updated)');
});

it('returns 404 when no instruction sheet exists', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Just a regular message',
        'is_instruction_sheet' => false,
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.txt");

    $response->assertStatus(404);
});

it('returns 404 for invalid token on download', function () {
    $response = $this->get('/launchpad/invalid-token/instructions.txt');

    $response->assertStatus(404);
});

it('downloads full chat as text file', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Welcome!',
        'created_at' => now()->subMinutes(2),
    ]);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'user',
        'content' => 'Thanks!',
        'created_at' => now()->subMinute(),
    ]);

    $response = $this->get("/launchpad/{$task->token}/chat.txt");

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="launchpad-chat.txt"');

    $content = $response->getContent();
    expect($content)
        ->toContain('[Guide]')
        ->toContain('Welcome!')
        ->toContain('[You]')
        ->toContain('Thanks!');
});
