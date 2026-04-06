<?php

use App\Livewire\LaunchpadChat;

it('detects instruction sheet marker in content', function () {
    $content = "Here is your instruction sheet\n\n<!-- INSTRUCTION_SHEET -->";

    expect(LaunchpadChat::detectInstructionSheet($content))->toBeTrue();
});

it('does not detect marker when absent', function () {
    $content = "Just a regular message with no marker.";

    expect(LaunchpadChat::detectInstructionSheet($content))->toBeFalse();
});

it('detects marker in the middle of content', function () {
    $content = "Some content <!-- INSTRUCTION_SHEET --> more content";

    expect(LaunchpadChat::detectInstructionSheet($content))->toBeTrue();
});

it('strips marker from content', function () {
    $content = "Here is your instruction sheet\n\n<!-- INSTRUCTION_SHEET -->";

    $stripped = LaunchpadChat::stripInstructionSheetMarker($content);

    expect($stripped)
        ->toBe("Here is your instruction sheet")
        ->not->toContain('<!-- INSTRUCTION_SHEET -->');
});

it('strips marker from middle of content', function () {
    $content = "Before <!-- INSTRUCTION_SHEET --> After";

    $stripped = LaunchpadChat::stripInstructionSheetMarker($content);

    expect($stripped)->toBe("Before  After");
});

it('returns content unchanged when no marker present', function () {
    $content = "No marker here.";

    $stripped = LaunchpadChat::stripInstructionSheetMarker($content);

    expect($stripped)->toBe("No marker here.");
});
