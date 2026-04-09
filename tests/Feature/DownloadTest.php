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

it('downloads assistant instructions as markdown file', function () {
    $task = Assistant::factory()->active()->create(['name' => 'Sarah Jones']);

    Chat::factory()->deliverable()->create([
        'task_id' => $task->id,
        'instructions_content' => '# Sarah — AI Assistant for Test Client',
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.md");

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'text/markdown; charset=utf-8');

    expect($response->getContent())->toBe('# Sarah — AI Assistant for Test Client');
});

it('returns the most recent deliverable for instructions download', function () {
    $task = Assistant::factory()->active()->create(['name' => 'Test User']);

    Chat::factory()->deliverable()->create([
        'task_id' => $task->id,
        'instructions_content' => 'Phase 1 instructions',
        'created_at' => now()->subMinutes(5),
    ]);

    Chat::factory()->deliverable()->create([
        'task_id' => $task->id,
        'instructions_content' => 'Phase 2 instructions (updated)',
        'created_at' => now(),
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.md");

    expect($response->getContent())->toBe('Phase 2 instructions (updated)');
});

it('falls back to full content when instructions_content is null', function () {
    $task = Assistant::factory()->active()->create(['name' => 'Test User']);

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Full deliverable content as fallback',
        'is_deliverable' => true,
        'instructions_content' => null,
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.md");

    expect($response->getContent())->toBe('Full deliverable content as fallback');
});

it('returns 404 when no deliverable exists for instructions', function () {
    $task = Assistant::factory()->active()->create();

    Chat::factory()->create([
        'task_id' => $task->id,
        'role' => 'assistant',
        'content' => 'Just a regular message',
        'is_deliverable' => false,
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.md");

    $response->assertStatus(404);
});

it('returns 404 for invalid token on download', function () {
    $response = $this->get('/launchpad/invalid-token/instructions.md');

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
