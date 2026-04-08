<?php

use App\Models\Assistant;
use App\Models\Chat;
use App\Services\PlaybookPdfService;

it('generates a Playbook PDF download', function () {
    $task = Assistant::factory()->create([
        'name' => 'Sarah Jones',
        'status' => 'active',
    ]);

    $task->chats()->create([
        'role' => 'assistant',
        'content' => samplePlaybookContent(),
        'phase' => 1,
        'is_deliverable' => true,
        'playbook_content' => samplePlaybookContent(),
        'instructions_content' => sampleInstructionsContent(),
    ]);

    $response = $this->get("/launchpad/{$task->token}/playbook.pdf");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('returns 404 when no deliverable exists for PDF', function () {
    $task = Assistant::factory()->create(['status' => 'active']);

    $response = $this->get("/launchpad/{$task->token}/playbook.pdf");

    $response->assertNotFound();
});

it('downloads a specific deliverable by message id', function () {
    $task = Assistant::factory()->create([
        'name' => 'Test User',
        'status' => 'active',
    ]);

    $first = $task->chats()->create([
        'role' => 'assistant',
        'content' => samplePlaybookContent(),
        'phase' => 1,
        'is_deliverable' => true,
        'playbook_content' => samplePlaybookContent(),
    ]);

    $second = $task->chats()->create([
        'role' => 'assistant',
        'content' => samplePlaybookContent('Updated'),
        'phase' => 2,
        'is_deliverable' => true,
        'playbook_content' => samplePlaybookContent('Updated'),
    ]);

    $response = $this->get("/launchpad/{$task->token}/playbook.pdf?message={$first->id}");

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
});

it('parses Playbook sections correctly', function () {
    $service = new PlaybookPdfService();

    $sections = $service->parseSections(samplePlaybookContent());

    expect($sections)->toHaveCount(5);
    expect($sections[0]['title'])->toContain('Your Bottleneck');
    expect($sections[1]['title'])->toContain('Your Process Map');
    expect($sections[2]['title'])->toContain('How Your Assistant Works');
    expect($sections[3]['title'])->toContain('Getting Started');
    expect($sections[4]['title'])->toContain('What Happens Next');
});

it('converts markdown to html for pdf rendering', function () {
    $service = new PlaybookPdfService();

    $html = $service->markdownToHtml("**Bold text** and *italic text*");

    expect($html)->toContain('<strong>Bold text</strong>');
    expect($html)->toContain('<em>italic text</em>');
});

it('renders code blocks as styled boxes', function () {
    $service = new PlaybookPdfService();

    $markdown = "Some text\n\n```\nYou are an assistant named Sarah.\n```\n\nMore text";
    $html = $service->markdownToHtml($markdown);

    expect($html)->toContain('class="code-block"');
});

it('includes correct filename with buyer name', function () {
    $service = new PlaybookPdfService();

    $task = Assistant::factory()->create(['name' => 'Jane Smith']);

    expect($service->filename($task))->toBe('Jane Smith - AI Assistant Playbook.pdf');
});

it('shows both download buttons for each deliverable in chat', function () {
    $task = Assistant::factory()->create([
        'name' => 'Test Buyer',
        'status' => 'active',
    ]);

    $msg = $task->chats()->create([
        'role' => 'assistant',
        'content' => samplePlaybookContent(),
        'phase' => 1,
        'is_deliverable' => true,
        'playbook_content' => samplePlaybookContent(),
        'instructions_content' => sampleInstructionsContent(),
    ]);

    $response = $this->get("/launchpad/{$task->token}");

    $response->assertOk();
    $response->assertSee('Download Playbook');
    $response->assertSee('Download AssistantInstructions.md');
    $response->assertSee("/launchpad/{$task->token}/playbook.pdf?message={$msg->id}");
    $response->assertSee("/launchpad/{$task->token}/instructions.md?message={$msg->id}");
});

it('downloads instructions as markdown file', function () {
    $task = Assistant::factory()->create([
        'name' => 'Test Buyer',
        'status' => 'active',
    ]);

    $task->chats()->create([
        'role' => 'assistant',
        'content' => samplePlaybookContent() . "\n\n" . sampleInstructionsContent(),
        'phase' => 1,
        'is_deliverable' => true,
        'playbook_content' => samplePlaybookContent(),
        'instructions_content' => sampleInstructionsContent(),
    ]);

    $response = $this->get("/launchpad/{$task->token}/instructions.md");

    $response->assertOk();
    $response->assertHeader('content-type', 'text/markdown; charset=utf-8');
    expect($response->getContent())->toContain('# Sarah — AI Assistant for Jane');
});

// Helper functions
function samplePlaybookContent(string $prefix = ''): string
{
    return <<<PLAYBOOK
{$prefix}

**1. Your Bottleneck — You spend 3 hours a day triaging emails**

It comes up every morning and takes time away from coaching.

**2. Your Process Map**

Triage incoming emails, categorise by urgency, draft responses for routine items, flag VIPs.

**3. How Your Assistant Works**

Sarah handles email triage and response drafting. She learns your patterns by reviewing sent emails. Routine items are handled automatically. Anything flagged urgent goes to you first.

**4. Getting Started**

Open Claude CoWork. Create a new project. Add the assistant instructions file. Run the first test task below. Your assistant instructions are included as an appendix to this Playbook and are also available as a separate markdown file you can download.

**5. What Happens Next**

When Sarah first runs, she will go through a short onboarding process: reviewing your sent emails, learning your tone, and asking a few setup questions. The first week is about observation and calibration.
PLAYBOOK;
}

function sampleInstructionsContent(): string
{
    return <<<INSTRUCTIONS
# Sarah — AI Assistant for Jane

## Role
You are Sarah, an AI assistant for Jane's coaching business. You handle email triage and response drafting.

## Business Context
Jane runs a small coaching practice serving 20 active clients.

## The Process You Handle
- Triage incoming emails by urgency
- Draft responses for routine items
- Flag VIP clients immediately

## How You Learn
Review sent emails from the last 90 days. Learn tone, frequency, and contacts.

## Onboarding Sequence
1. Confirm Gmail is connected
2. Read sent emails from the last 90 days
3. Ask Jane about VIP client list
4. Summarise what you have learned

## Rules
- Always respond within 24 hours
- Flag VIP clients immediately
- Never auto-send without approval

## Output Style
Summaries as bullet lists. Drafted responses in the same tone as Jane's sent emails.

## Defaults
When encountering something new, ask Jane what to do and remember the answer.
INSTRUCTIONS;
}
