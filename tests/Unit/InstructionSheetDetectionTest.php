<?php

use App\Livewire\LaunchpadChat;

it('detects deliverable marker in content', function () {
    $content = "Here is your Playbook\n\n<!-- INSTRUCTION_SHEET -->";

    expect(LaunchpadChat::detectDeliverable($content))->toBeTrue();
});

it('does not detect marker when absent', function () {
    $content = "Just a regular message with no marker.";

    expect(LaunchpadChat::detectDeliverable($content))->toBeFalse();
});

it('detects marker in the middle of content', function () {
    $content = "Some content <!-- INSTRUCTION_SHEET --> more content";

    expect(LaunchpadChat::detectDeliverable($content))->toBeTrue();
});

it('strips marker from content', function () {
    $content = "Here is your Playbook\n\n<!-- INSTRUCTION_SHEET -->";

    $stripped = LaunchpadChat::stripDeliverableMarker($content);

    expect($stripped)
        ->toBe("Here is your Playbook")
        ->not->toContain('<!-- INSTRUCTION_SHEET -->');
});

it('strips marker from middle of content', function () {
    $content = "Before <!-- INSTRUCTION_SHEET --> After";

    $stripped = LaunchpadChat::stripDeliverableMarker($content);

    expect($stripped)->toBe("Before  After");
});

it('returns content unchanged when no marker present', function () {
    $content = "No marker here.";

    $stripped = LaunchpadChat::stripDeliverableMarker($content);

    expect($stripped)->toBe("No marker here.");
});

it('detects deliverable via fallback patterns when marker is missing', function () {
    $content = "## Your Bottleneck\nEmail triage\n\n## Your Process Map\n1. Check inbox\n\n## How Luna Works\nLuna sorts emails\n\n## Getting Started\n1. Open Claude";

    expect(LaunchpadChat::detectDeliverable($content))->toBeTrue();
});

it('does not false-positive on regular messages with one pattern', function () {
    $content = "## Getting Started\nHere is how to begin with your assistant.";

    expect(LaunchpadChat::detectDeliverable($content))->toBeFalse();
});

it('parses deliverable content using INSTRUCTIONS_START marker', function () {
    $content = "## 1. Your Bottleneck\nContent here\n\n<!-- INSTRUCTIONS_START -->\n\n# Sarah — AI Assistant\n\n## Role\nYou are Sarah.";

    $result = LaunchpadChat::parseDeliverableContent($content);

    expect($result['playbook_content'])->toContain('Your Bottleneck')
        ->and($result['playbook_content'])->not->toContain('# Sarah')
        ->and($result['instructions_content'])->toContain('# Sarah — AI Assistant')
        ->and($result['instructions_content'])->toContain('## Role');
});

it('falls back to full content as playbook when no split marker found', function () {
    $content = "Just a single block of content with no markers.";

    $result = LaunchpadChat::parseDeliverableContent($content);

    expect($result['playbook_content'])->toBe($content)
        ->and($result['instructions_content'])->toBeNull();
});
