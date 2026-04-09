<?php

use App\Services\PlaybookValidator;

// ── Helper to build a valid playbook ──

function validPlaybook(string $assistantName = 'Ben', string $buyerName = 'Fred'): string
{
    return <<<MD
**1. Your Bottleneck**

You are spending around 4 hours a week triaging emails in Gmail. Every morning you open your inbox, scan each message, and decide what needs action.

---
20 min - per triage session
Daily - every working day
~4 hrs - per week on email triage

**2. Your Process Map**

1. **Check inbox** - Open Gmail and scan new messages. *(Manual - daily)*
2. **Categorise emails** - Decide if each email needs action, is FYI, or can be archived. *(Learnable - pattern-based)*
3. **Label and sort** - Apply labels and move to folders. *(Automatic - rule-based)*
4. **Draft replies** - Write quick replies to routine messages. *(Learnable - tone and content)*
5. **Flag urgent** - Star anything that needs immediate attention. *(Manual - judgement call)*

**3. How {$assistantName} Works**

{$assistantName} reads your incoming emails and categorises them based on urgency and type. It learns your patterns by observing how you have handled similar emails before.

- Always present drafts for review before sending
- Never include confidential client information
- Always ask about new situations and remember the answer

**4. Getting Started**

1. Download the instruction file below. This is {$assistantName}'s brain.
2. Open Claude CoWork. Create a new project called "{$assistantName}".
3. Upload the instruction file to the project. {$assistantName} will read it.
4. Type "Let's get started". {$assistantName} will begin the onboarding sequence.

**First test task:** Forward your most recent 5 emails to {$assistantName} and ask it to categorise them.

**5. What Happens Next**

{$assistantName} will run through an onboarding sequence on first use, asking you setup questions to understand your preferences.

### Your first two weeks
- **First use:** Onboarding runs and the assistant asks setup questions.
- **Week one:** Review each output and give feedback so the assistant learns.
- **Week two:** Outputs should feel like your own work.
- **Ongoing:** The assistant asks about new situations and remembers your answers.

> **Remember:** You can return to your Launchpad chat anytime to refine your assistant.

<!-- INSTRUCTIONS_START -->

# {$assistantName} - AI Assistant for {$buyerName}

## Role
You are {$assistantName}, an AI assistant for {$buyerName}. You handle email triage in Gmail.

## Business Context
{$buyerName} is a business coach who receives 30-50 emails daily. Most are client communications, newsletters, and admin.

## The Process You Handle
1. Read new emails in the inbox
2. Categorise each email: action required, FYI, or archive
3. Apply appropriate labels
4. Draft replies for routine messages
5. Flag urgent items for immediate attention

## How You Learn
Review {$buyerName}'s sent emails from the last 30 days. Look for patterns in tone, common phrases, response times, and which emails get prioritised.

## Onboarding Sequence
1. Ask {$buyerName} to share their current Gmail labels
2. Review the last 20 sent emails for tone and style
3. Ask about priority contacts
4. Ask about email categories they use
5. Run a test categorisation on 5 recent emails

## Rules
- Always present draft replies for review before sending
- Never share client information between threads
- Ask about unfamiliar senders before categorising

## Output Style
Keep categorisation summaries brief. One line per email. Use the format: Subject - Category - Action needed (yes/no).

## Defaults
When unsure about a category, ask {$buyerName}. Suggest your best guess and let them confirm. Remember the answer for next time.
MD;
}

// ── Validation tests ──

it('validates a correct playbook as valid', function () {
    $validator = new PlaybookValidator();
    $result = $validator->validate(validPlaybook());

    expect($result['valid'])->toBeTrue()
        ->and($result['issues'])->toBeEmpty();
});

it('detects missing instructions marker', function () {
    $content = str_replace('<!-- INSTRUCTIONS_START -->', '', validPlaybook());
    $validator = new PlaybookValidator();
    $result = $validator->validate($content);

    expect($result['valid'])->toBeFalse()
        ->and($result['issues'])->toContain('missing_instructions_marker');
});

it('detects missing playbook sections', function () {
    $content = str_replace('**1. Your Bottleneck**', '**1. The Problem**', validPlaybook());
    $validator = new PlaybookValidator();
    $result = $validator->validate($content);

    expect($result['valid'])->toBeFalse()
        ->and($result['issues'])->toContain('missing_playbook_section:Your Bottleneck');
});

it('detects missing instruction sections', function () {
    $content = str_replace('## Onboarding Sequence', '## Setup Steps', validPlaybook());
    $validator = new PlaybookValidator();
    $result = $validator->validate($content);

    expect($result['valid'])->toBeFalse()
        ->and($result['issues'])->toContain('missing_instruction_section:Onboarding Sequence');
});

it('detects em dashes', function () {
    $content = str_replace(' - per triage session', ' — per triage session', validPlaybook());
    $validator = new PlaybookValidator();
    $result = $validator->validate($content);

    expect($result['valid'])->toBeFalse()
        ->and($result['issues'])->toContain('contains_em_dashes');
});

it('detects playbook that is too short', function () {
    $content = "Short playbook\n\n<!-- INSTRUCTIONS_START -->\n\n## Role\nYou are Ben.\n\n## Business Context\nCoach.\n\n## The Process You Handle\nEmail.\n\n## How You Learn\nObserve.\n\n## Onboarding Sequence\n1. Start.\n\n## Rules\nBe nice.\n\n## Output Style\nBrief.\n\n## Defaults\nAsk.";
    $validator = new PlaybookValidator();
    $result = $validator->validate($content);

    expect($result['issues'])->toContain('playbook_too_short');
});

// ── Auto-fix tests ──

it('auto-fixes em dashes', function () {
    $content = str_replace(' - per triage session', ' — per triage session', validPlaybook());
    $validator = new PlaybookValidator();
    $result = $validator->validate($content);
    $fixed = $validator->autoFix($content, $result['issues']);

    expect($fixed['fixed'])->toBeTrue()
        ->and($fixed['fixes_applied'])->toContain('replaced_em_dashes')
        ->and(str_contains($fixed['content'], '—'))->toBeFalse();
});

it('auto-fixes missing instructions marker when Role section exists', function () {
    $content = str_replace('<!-- INSTRUCTIONS_START -->', '', validPlaybook());
    $validator = new PlaybookValidator();
    $result = $validator->validate($content);
    $fixed = $validator->autoFix($content, $result['issues']);

    expect($fixed['fixed'])->toBeTrue()
        ->and($fixed['fixes_applied'])->toContain('inserted_instructions_marker')
        ->and(str_contains($fixed['content'], '<!-- INSTRUCTIONS_START -->'))->toBeTrue();
});

// ── Repair prompt tests ──

it('builds a repair prompt for missing sections', function () {
    $validator = new PlaybookValidator();
    $issues = ['missing_playbook_section:Your Bottleneck', 'missing_instruction_section:Rules'];
    $prompt = $validator->buildRepairPrompt($issues);

    expect($prompt)->toContain('REPAIR NEEDED')
        ->and($prompt)->toContain('Your Bottleneck')
        ->and($prompt)->toContain('Rules');
});

it('returns empty repair prompt when only em dashes', function () {
    $validator = new PlaybookValidator();
    $prompt = $validator->buildRepairPrompt(['contains_em_dashes']);

    expect($prompt)->toBe('');
});

it('detects missing stat block', function () {
    // Remove the stat block (--- separator and metrics)
    $content = validPlaybook();
    $content = preg_replace('/---\s*\n.*?per week on email triage\n/s', '', $content);
    $validator = new PlaybookValidator();
    $result = $validator->validate($content);

    expect($result['issues'])->toContain('missing_stat_block');
});

it('validates all five playbook sections are present', function () {
    $validator = new PlaybookValidator();
    $content = validPlaybook();

    // Remove each section one at a time and check
    $sections = [
        '**1. Your Bottleneck**' => 'Your Bottleneck',
        '**2. Your Process Map**' => 'Your Process Map',
        '**4. Getting Started**' => 'Getting Started',
        '**5. What Happens Next**' => 'What Happens Next',
    ];

    foreach ($sections as $heading => $name) {
        $broken = str_replace($heading, '**X. Wrong Heading**', $content);
        $result = $validator->validate($broken);
        expect($result['issues'])->toContain("missing_playbook_section:{$name}");
    }
});
