<?php

use App\Models\Assistant;
use App\Models\Chat;
use App\Services\InstructionSheetPdfService;

it('generates a PDF download for instruction sheet', function () {
    $task = Assistant::factory()->create([
        'name' => 'Sarah Jones',
        'status' => 'active',
    ]);

    $task->chats()->create([
        'role' => 'assistant',
        'content' => sampleInstructionSheet(),
        'phase' => 1,
        'is_instruction_sheet' => true,
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.pdf");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('returns 404 when no instruction sheet exists', function () {
    $task = Assistant::factory()->create(['status' => 'active']);

    $response = $this->get("/launchpad/{$task->token}/instructions.pdf");

    $response->assertNotFound();
});

it('downloads a specific instruction sheet by message id', function () {
    $task = Assistant::factory()->create([
        'name' => 'Test User',
        'status' => 'active',
    ]);

    $first = $task->chats()->create([
        'role' => 'assistant',
        'content' => sampleInstructionSheet(),
        'phase' => 1,
        'is_instruction_sheet' => true,
    ]);

    $second = $task->chats()->create([
        'role' => 'assistant',
        'content' => sampleInstructionSheet('Updated'),
        'phase' => 2,
        'is_instruction_sheet' => true,
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.pdf?message={$first->id}");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('parses instruction sheet sections correctly', function () {
    $service = new InstructionSheetPdfService();

    $sections = $service->parseSections(sampleInstructionSheet());

    expect($sections)->toHaveCount(8);
    expect($sections[0]['title'])->toContain('Assistant name');
    expect($sections[3]['title'])->toContain('Training steps');
    expect($sections[5]['title'])->toContain('System prompt');
});

it('converts markdown to html for pdf rendering', function () {
    $service = new InstructionSheetPdfService();

    $html = $service->markdownToHtml("**Bold text** and *italic text*");

    expect($html)->toContain('<strong>Bold text</strong>');
    expect($html)->toContain('<em>italic text</em>');
});

it('renders code blocks as styled boxes', function () {
    $service = new InstructionSheetPdfService();

    $markdown = "Some text\n\n```\nYou are an assistant named Sarah.\n```\n\nMore text";
    $html = $service->markdownToHtml($markdown);

    expect($html)->toContain('class="code-block"');
});

it('includes correct filename with buyer name', function () {
    $service = new InstructionSheetPdfService();

    $task = Assistant::factory()->create(['name' => 'Jane Smith']);

    expect($service->filename($task))->toBe('Jane Smith - AI Assistant Instruction Sheet.pdf');
});

it('shows pdf download button for each instruction sheet in chat', function () {
    $task = Assistant::factory()->create([
        'name' => 'Test Buyer',
        'status' => 'active',
    ]);

    $msg = $task->chats()->create([
        'role' => 'assistant',
        'content' => sampleInstructionSheet(),
        'phase' => 1,
        'is_instruction_sheet' => true,
    ]);

    $response = $this->get("/launchpad/{$task->token}");

    $response->assertOk();
    $response->assertSee('Download your instruction sheet');
    $response->assertSee("/launchpad/{$task->token}/instructions.pdf?message={$msg->id}");
});

// Helper function
function sampleInstructionSheet(string $prefix = ''): string
{
    return <<<SHEET
{$prefix}Here is your complete instruction sheet:

1. **Assistant name** — Sarah

2. **What the assistant handles** — Email triage and response drafting for your coaching business.

3. **How it learns** — Sarah will review your sent email history from the last 3 months to learn your communication patterns.

4. **Training steps** — 1. Review the last 3 months of sent emails. 2. Scan archived messages. 3. When encountering something new, ask what to do and remember.

5. **Your rules** — Always respond within 24 hours. Flag VIP clients immediately. Never auto-send without approval.

6. **System prompt** — ```
You are Sarah, an AI assistant for a coaching business. You handle email triage and response drafting.
```

7. **Setup steps** — 1. Open Claude CoWork. 2. Create a new project. 3. Paste the system prompt. 4. Start with the test task.

8. **First test task** — Forward your 5 most recent client emails and ask Sarah to draft responses.
SHEET;
}
