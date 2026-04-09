<?php

namespace App\Services;

/**
 * Validates and repairs Playbook + Assistant Instructions output.
 *
 * Checks that the AI-generated content has the required structure
 * and markers. Returns issues found and can auto-fix common problems.
 */
class PlaybookValidator
{
    // ── Required marker ──
    private const INSTRUCTIONS_MARKER = '<!-- INSTRUCTIONS_START -->';

    // ── Required Playbook sections (Output 1) ──
    private const PLAYBOOK_SECTIONS = [
        'Your Bottleneck' => '/\*\*1\.\s*Your Bottleneck\*\*/',
        'Your Process Map' => '/\*\*2\.\s*Your Process Map\*\*/',
        'How .+ Works' => '/\*\*3\.\s*How .+ Works\*\*/',
        'Getting Started' => '/\*\*4\.\s*Getting Started\*\*/',
        'What Happens Next' => '/\*\*5\.\s*What Happens Next\*\*/',
    ];

    // ── Required Instructions sections (Output 2) ──
    private const INSTRUCTION_SECTIONS = [
        'Role' => '/^##\s*Role\b/m',
        'Business Context' => '/^##\s*Business Context\b/m',
        'The Process You Handle' => '/^##\s*The Process You Handle\b/m',
        'How You Learn' => '/^##\s*How You Learn\b/m',
        'Onboarding Sequence' => '/^##\s*Onboarding Sequence\b/m',
        'Rules' => '/^##\s*Rules\b/m',
        'Output Style' => '/^##\s*Output Style\b/m',
        'Defaults' => '/^##\s*Defaults\b/m',
    ];

    /**
     * Validate the full AI output and return a result.
     *
     * @return array{valid: bool, issues: string[], content: string}
     */
    public function validate(string $content): array
    {
        $issues = [];

        // Check for the instructions marker
        if (! str_contains($content, self::INSTRUCTIONS_MARKER)) {
            $issues[] = 'missing_instructions_marker';
        }

        // Split into playbook and instructions
        if (str_contains($content, self::INSTRUCTIONS_MARKER)) {
            [$playbookPart, $instructionsPart] = explode(self::INSTRUCTIONS_MARKER, $content, 2);
        } else {
            $playbookPart = $content;
            $instructionsPart = '';
        }

        // Check playbook sections
        foreach (self::PLAYBOOK_SECTIONS as $name => $pattern) {
            if (! preg_match($pattern, $playbookPart)) {
                $issues[] = "missing_playbook_section:{$name}";
            }
        }

        // Check stat block in Your Bottleneck section
        if (! preg_match('/---\s*\n.*?(?:—|-)\s+\w.*?\n/s', $playbookPart)) {
            $issues[] = 'missing_stat_block';
        }

        // Check instructions sections (only if we have an instructions part)
        if (trim($instructionsPart)) {
            foreach (self::INSTRUCTION_SECTIONS as $name => $pattern) {
                if (! preg_match($pattern, $instructionsPart)) {
                    $issues[] = "missing_instruction_section:{$name}";
                }
            }
        } elseif (! in_array('missing_instructions_marker', $issues)) {
            $issues[] = 'empty_instructions';
        }

        // Check minimum content length (a proper playbook should be substantial)
        if (strlen(trim($playbookPart)) < 500) {
            $issues[] = 'playbook_too_short';
        }
        if (trim($instructionsPart) && strlen(trim($instructionsPart)) < 300) {
            $issues[] = 'instructions_too_short';
        }

        // Check for em dashes (brand rule violation)
        if (str_contains($content, '—')) {
            $issues[] = 'contains_em_dashes';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'content' => $content,
        ];
    }

    /**
     * Attempt to auto-fix common issues in the content.
     *
     * @param  string  $content  The raw AI output
     * @param  array   $issues   Issues from validate()
     * @return array{fixed: bool, content: string, fixes_applied: string[]}
     */
    public function autoFix(string $content, array $issues): array
    {
        $fixes = [];

        // Fix: missing instructions marker — try to find the split point
        if (in_array('missing_instructions_marker', $issues)) {
            $content = $this->insertInstructionsMarker($content);
            if (str_contains($content, self::INSTRUCTIONS_MARKER)) {
                $fixes[] = 'inserted_instructions_marker';
            }
        }

        // Fix: em dashes — replace with hyphens
        if (in_array('contains_em_dashes', $issues)) {
            $content = str_replace('—', '-', $content);
            $fixes[] = 'replaced_em_dashes';
        }

        return [
            'fixed' => ! empty($fixes),
            'content' => $content,
            'fixes_applied' => $fixes,
        ];
    }

    /**
     * Try to find where the instructions section starts and insert the marker.
     */
    private function insertInstructionsMarker(string $content): string
    {
        // Look for common instruction section starts
        $patterns = [
            // # AssistantName — AI Assistant for BuyerName
            '/(\n)(#\s+\w+\s*[-–]\s*AI Assistant for)/i',
            // ## Role followed by "You are X, an AI assistant"
            '/(\n)(##\s*Role\s*\n.*?You are \w+)/is',
            // Heading with "Assistant Instructions" or "Instruction Sheet"
            '/(\n)(#{1,3}\s*(?:Assistant Instructions|Complete Instruction|Instruction Sheet))/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content, $m, PREG_OFFSET_CAPTURE)) {
                $insertPos = $m[1][1];

                return substr($content, 0, $insertPos)
                    . "\n\n" . self::INSTRUCTIONS_MARKER . "\n\n"
                    . substr($content, $insertPos + strlen($m[1][0]));
            }
        }

        return $content;
    }

    /**
     * Build a repair prompt listing what's missing so the AI can fix it.
     *
     * @param  array  $issues  Issues from validate()
     * @return string  Directive for the AI to fix the output
     */
    public function buildRepairPrompt(array $issues): string
    {
        $problems = [];

        foreach ($issues as $issue) {
            if ($issue === 'missing_instructions_marker') {
                $problems[] = 'The <!-- INSTRUCTIONS_START --> marker between the Playbook and Assistant Instructions is missing. Add it on its own line between the two sections.';
            } elseif (str_starts_with($issue, 'missing_playbook_section:')) {
                $section = str_replace('missing_playbook_section:', '', $issue);
                $problems[] = "The Playbook is missing the \"{$section}\" section. Add it with the correct heading format.";
            } elseif (str_starts_with($issue, 'missing_instruction_section:')) {
                $section = str_replace('missing_instruction_section:', '', $issue);
                $problems[] = "The Assistant Instructions are missing the \"## {$section}\" section. Add it.";
            } elseif ($issue === 'missing_stat_block') {
                $problems[] = 'The stat block (three metrics after a --- separator) is missing from the Your Bottleneck section.';
            } elseif ($issue === 'empty_instructions') {
                $problems[] = 'The Assistant Instructions section is empty. Generate the full instructions after the <!-- INSTRUCTIONS_START --> marker.';
            } elseif ($issue === 'playbook_too_short') {
                $problems[] = 'The Playbook content is too short. Expand each section with specific details about the buyer\'s process.';
            } elseif ($issue === 'instructions_too_short') {
                $problems[] = 'The Assistant Instructions are too short. Include detailed directives for each section.';
            } elseif ($issue === 'contains_em_dashes') {
                // Auto-fixed, no need for repair prompt
                continue;
            }
        }

        if (empty($problems)) {
            return '';
        }

        return "REPAIR NEEDED: The Playbook output has the following issues that must be fixed:\n\n"
            . implode("\n", array_map(fn ($p) => "- {$p}", $problems))
            . "\n\nRegenerate the COMPLETE Playbook and Assistant Instructions with all issues fixed. Use the exact same format as before. Include the <!-- INSTRUCTIONS_START --> marker between the two sections.";
    }
}
