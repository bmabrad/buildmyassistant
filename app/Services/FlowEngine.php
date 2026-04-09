<?php

namespace App\Services;

use App\Models\Assistant;

/**
 * State machine that controls the Launchpad chat flow.
 *
 * The AI handles conversational responses within a tightly scoped context.
 * The FlowEngine decides when to advance steps, what questions to inject,
 * and when to trigger deliverable generation.
 *
 * FlowEngine is pure: it never touches the DB or calls the API.
 * It evaluates state and returns actions for LaunchpadChat to execute.
 */
class FlowEngine
{
    // ── Human-friendly assistant names to suggest ──
    private const NAMES = [
        'Sarah', 'James', 'Nina', 'Max', 'Maya', 'Alex',
        'Jordan', 'Casey', 'Riley', 'Sam', 'Emma', 'Leo',
    ];

    /**
     * Initialize flow state for a brand new session.
     */
    public static function initState(): array
    {
        return [
            'step' => 1,
            'sub_state' => 'opening',
            'ai_turns' => 0,
            'data' => [
                'process' => null,
                'tools' => null,
                'time' => null,
                'assistant_name' => null,
                'suggested_name' => null,
                'additional_notes' => null,
            ],
        ];
    }

    /**
     * Determine what should happen next after a user sends a message.
     *
     * Returns an action array:
     *   ['action' => 'call_ai', 'directive' => '...']
     *   ['action' => 'generate_playbook']
     *   ['action' => 'advance_and_call', 'new_step' => N, 'directive' => '...']
     */
    public function processUserMessage(Assistant $task, string $userMessage): array
    {
        $state = $task->flow_state ?? self::initState();
        $step = $state['step'];
        $subState = $state['sub_state'];

        return match ($step) {
            1 => $this->handleStep1UserMessage($state, $task, $userMessage),
            2 => $this->handleStep2UserMessage($state, $task, $userMessage),
            3 => $this->handleStep3UserMessage($state, $task, $userMessage),
            4 => $this->handleStep4UserMessage($state, $task, $userMessage),
            5 => $this->handleStep5UserMessage($state, $task, $userMessage),
            default => ['action' => 'call_ai', 'directive' => '', 'state' => $state],
        };
    }

    /**
     * Determine the action for the initial greeting (no user message).
     */
    public function processGreeting(Assistant $task): array
    {
        $state = $task->flow_state ?? self::initState();

        $isReturning = $task->user
            && $task->user->assistants()->where('id', '!=', $task->id)->exists();

        if ($isReturning) {
            $directive = "Welcome the buyer back warmly. Say something like 'Great to see you back here, {$task->name}!' Acknowledge they have done this before. Then ask: 'What process are we building an assistant for this time?' Keep it to 2-3 sentences. Do not explain how the session works.";
        } else {
            $directive = "Greet the buyer warmly by name ({$task->name}). Briefly explain you are here to build them a custom AI assistant that handles a process eating their time. Then ask: 'Do you already know what process you would like to automate, or would you like me to help you figure that out?' Keep it to 3-4 sentences maximum. No disclaimers.";
        }

        $state['sub_state'] = 'opening';
        $state['ai_turns'] = 1;

        return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
    }

    /**
     * Process the AI's response to extract data and determine if any
     * post-processing is needed (e.g., appending a mandatory question).
     *
     * Returns: ['state' => [...], 'append' => string|null]
     */
    public function processAiResponse(Assistant $task, string $aiResponse): array
    {
        $state = $task->flow_state ?? self::initState();

        // Increment AI turns
        $state['ai_turns'] = ($state['ai_turns'] ?? 0) + 1;

        // If the AI just acknowledged extra notes in Step 3, auto-advance to playbook generation
        if ($state['step'] === 3 && $state['sub_state'] === 'noted') {
            $state['step'] = 4;
            $state['sub_state'] = 'generating';
            $state['ai_turns'] = 0;

            return ['state' => $state, 'append' => null, 'follow_up' => 'generate_playbook'];
        }

        return ['state' => $state, 'append' => null];
    }

    // ───────────────────────────────────────────────
    // Step 1: Bottleneck Discovery
    // ───────────────────────────────────────────────

    private function handleStep1UserMessage(array $state, Assistant $task, string $userMessage): array
    {
        $aiTurns = $state['ai_turns'] ?? 0;
        $subState = $state['sub_state'];

        // If the user just answered the opening question, move to discovering
        if ($subState === 'opening') {
            $state['sub_state'] = 'discovering';
        }

        // After 3+ AI turns, force a confirmation/summary
        if ($aiTurns >= 3) {
            $state['sub_state'] = 'confirming';
        }

        // If we are in confirming and user said yes, advance to step 2
        if ($subState === 'confirming' && $this->isAffirmative($userMessage)) {
            return $this->advanceToStep2($state, $task);
        }

        // If the AI has had enough turns, force advance regardless
        if ($aiTurns >= 4) {
            return $this->advanceToStep2($state, $task);
        }

        // Build directive based on sub-state
        if ($state['sub_state'] === 'confirming') {
            $directive = "The buyer did not fully confirm. Adjust your summary based on their feedback and ask for confirmation again. Keep it to one question.";
        } else {
            $directive = "You are finding out what process is eating the buyer's time. You need to know: what the process is, what tools they use, and roughly how much time it takes. Ask ONE question, framed as yes/no. Do not ask about how they do the process, just what it is and how much time it takes.";

            // If we have enough info, ask for confirmation
            if ($aiTurns >= 2) {
                $directive = "Based on what the buyer has told you, summarise the process back to them: what it is, what tools they use, and how much time it takes. Ask: 'Is that right?' If you do not have all three details yet, ask ONE more question to fill the gap.";
                $state['sub_state'] = 'confirming';
            }
        }

        return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
    }

    private function advanceToStep2(array $state, Assistant $task): array
    {
        $state['step'] = 2;
        $state['sub_state'] = 'discovering';
        $state['ai_turns'] = 0;

        $directive = "Transition to process discovery. Based on what you learned in Step 1, ask ONE yes/no question about how the buyer currently does this process day-to-day. Focus on the mechanics: what tools, what order, what triggers the process, how things are organised. Do not ask about tone, voice, content, or style. Frame it as a specific guess they can confirm: 'I would guess you start by checking your inbox each morning and working through them one by one, is that right?'";

        return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
    }

    // ───────────────────────────────────────────────
    // Step 2: Process Discovery
    // ───────────────────────────────────────────────

    private function handleStep2UserMessage(array $state, Assistant $task, string $userMessage): array
    {
        $subState = $state['sub_state'];
        $aiTurns = $state['ai_turns'] ?? 0;

        // ── Discovery phase: ask about their current workflow ──
        if ($subState === 'discovering') {
            // After 3+ AI turns in discovery, present the process map
            if ($aiTurns >= 3) {
                $state['sub_state'] = 'presenting';
                $directive = "You have enough detail about their workflow. Present a high-level process map of what is involved in their process. List 3-5 steps showing WHAT happens, based on everything you have learned. Each step should be one line. Then ask: 'Does that cover the main steps?'";

                return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
            }

            // Ask next discovery question
            $directive = "Based on the buyer's answer, ask ONE more yes/no question about how they currently do this process. Focus on the mechanics: how things are organised, what triggers actions, what gets done manually vs automatically, whether they batch things or handle them one at a time. Do not ask about tone, voice, content style, or how the assistant should work. Frame it as a specific guess they can confirm or correct.";

            return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
        }

        // ── Presenting phase: they have seen the map ──
        if ($subState === 'presenting') {
            $state['sub_state'] = 'confirming';
        }

        // ── Confirming phase: did they accept the map? ──
        if ($subState === 'confirming' && $this->isAffirmative($userMessage)) {
            return $this->advanceToStep3($state, $task);
        }

        // Force advance if too many turns in confirm
        if ($subState === 'confirming' && $aiTurns >= 5) {
            return $this->advanceToStep3($state, $task);
        }

        // User wants changes to the map
        $directive = "The buyer wants to adjust the process map. Update it based on their feedback and ask for confirmation again. Keep the map high-level (3-5 steps, what happens not how).";

        return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
    }

    private function advanceToStep3(array $state, Assistant $task): array
    {
        $state['step'] = 3;
        $state['sub_state'] = 'name_presenting';
        $state['ai_turns'] = 0;

        // Pick a suggested name
        $suggestedName = self::NAMES[array_rand(self::NAMES)];
        $state['data']['suggested_name'] = $suggestedName;

        $directive = "STOP asking discovery questions. The discovery phase is OVER. Now summarise the core job in one sentence (e.g. 'Your assistant will handle your email triage in Gmail'). Tell them the assistant will learn from their existing data on first use. Then suggest the name '{$suggestedName}' and ask: 'I am thinking of calling your assistant {$suggestedName}. Are you happy with that, or would you like me to suggest some other options?' Do NOT ask any other questions. Do NOT describe how the assistant will do the job.";

        return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
    }

    // ───────────────────────────────────────────────
    // Step 3: Assistant Design (name + anything else)
    // ───────────────────────────────────────────────

    private function handleStep3UserMessage(array $state, Assistant $task, string $userMessage): array
    {
        $subState = $state['sub_state'];

        // name_presenting: the AI should have just asked the name question.
        // Check the last AI message to verify — if the AI went off-script
        // and asked something else, re-ask the name question.
        if ($subState === 'name_presenting') {
            $lastAiMessage = $task->chats()->where('role', 'assistant')->latest('id')->value('content') ?? '';
            $suggestedName = $state['data']['suggested_name'] ?? '';

            if ($suggestedName && stripos($lastAiMessage, $suggestedName) !== false) {
                // AI asked the name question — process the user's response
                $state['sub_state'] = 'name_question';
                $subState = 'name_question';
            } else {
                // AI went off-script — force the name question now
                $directive = "IMPORTANT: You must ask the naming question now. Say: 'I am thinking of calling your assistant {$suggestedName}. Are you happy with that, or would you like me to suggest some other options?' Do NOT ask any other questions.";

                return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
            }
        }

        if ($subState === 'name_question') {
            // Check if they accepted the name or want alternatives
            if ($this->isAffirmative($userMessage) || $this->containsName($userMessage, $state['data']['suggested_name'] ?? '')) {
                // Name accepted
                $state['data']['assistant_name'] = $state['data']['suggested_name'];
                $state['sub_state'] = 'anything_else';

                $name = $state['data']['assistant_name'];
                $directive = "The buyer has chosen the name {$name}. Say 'Great, {$name} it is!' Then ask exactly: 'Before I put your Playbook together, is there anything else I should know about how you work or what you need from this assistant?' Do NOT generate the Playbook. Do NOT discuss anything else. Just acknowledge the name and ask the question.";

                return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
            }

            // They want a different name - check if they gave one
            $providedName = $this->extractProvidedName($userMessage);
            if ($providedName) {
                $state['data']['assistant_name'] = $providedName;
                $state['data']['suggested_name'] = $providedName;
                $state['sub_state'] = 'anything_else';

                $directive = "The buyer wants to call their assistant {$providedName}. Say 'Great, {$providedName} it is!' Then ask exactly: 'Before I put your Playbook together, is there anything else I should know about how you work or what you need from this assistant?' Do NOT generate the Playbook.";

                return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
            }

            // They want alternatives
            $alternatives = $this->getAlternativeNames($state['data']['suggested_name'] ?? '');
            $state['data']['name_alternatives'] = $alternatives;
            $state['sub_state'] = 'picking_name';
            $nameList = implode(', ', $alternatives);

            $directive = "The buyer wants different name options. Suggest these alternatives: {$nameList}. Present them as a numbered list so the buyer can just type the number. Ask which one they prefer, or if they have their own idea.";

            return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
        }

        if ($subState === 'picking_name') {
            $alternatives = $state['data']['name_alternatives'] ?? [];
            $chosenName = null;

            // Check if they typed a number
            $trimmed = trim($userMessage);
            if (is_numeric($trimmed)) {
                $index = (int) $trimmed - 1;
                if (isset($alternatives[$index])) {
                    $chosenName = $alternatives[$index];
                }
            }

            // Check if they typed a name from the list
            if (! $chosenName) {
                foreach ($alternatives as $alt) {
                    if (stripos($userMessage, $alt) !== false) {
                        $chosenName = $alt;
                        break;
                    }
                }
            }

            // Check if they provided their own name
            if (! $chosenName) {
                $chosenName = $this->extractProvidedName($userMessage);
            }

            if ($chosenName) {
                $state['data']['assistant_name'] = $chosenName;
                $state['data']['suggested_name'] = $chosenName;
                $state['sub_state'] = 'anything_else';

                $directive = "The buyer has chosen the name {$chosenName}. Say 'Great, {$chosenName} it is!' Then ask exactly: 'Before I put your Playbook together, is there anything else I should know about how you work or what you need from this assistant?' Do NOT generate the Playbook.";

                return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
            }

            // Couldn't parse their choice — ask again
            $nameList = implode(', ', $alternatives);
            $directive = "I could not tell which name the buyer picked. Ask them again: which of these names would they like: {$nameList}? Or they can suggest their own.";

            return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
        }

        if ($subState === 'anything_else') {
            // If they said no / nothing else, go straight to generation
            if ($this->isNegative($userMessage)) {
                $state['step'] = 4;
                $state['sub_state'] = 'generating';
                $state['ai_turns'] = 0;

                return ['action' => 'generate_playbook', 'state' => $state];
            }

            // They raised something — store it and let the AI acknowledge before generating
            $state['data']['additional_notes'] = $userMessage;
            $state['sub_state'] = 'noted';

            $directive = "The buyer said: \"{$userMessage}\". Reply with ONLY two short sentences: 1) Acknowledge what they said. 2) Say you will include it. Example: 'Love it, I will make sure Alex can draft emails for you too. Let me put your Playbook together now.' STOP THERE. Do NOT generate the Playbook. Do NOT include any headings, lists, or content after those two sentences. The system will handle Playbook generation automatically.";

            return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
        }

        if ($subState === 'noted') {
            // The AI has acknowledged their extra notes, now generate
            $state['step'] = 4;
            $state['sub_state'] = 'generating';
            $state['ai_turns'] = 0;

            return ['action' => 'generate_playbook', 'state' => $state];
        }

        // Fallback - shouldn't reach here
        return ['action' => 'call_ai', 'directive' => 'Continue the conversation naturally.', 'state' => $state];
    }

    // ───────────────────────────────────────────────
    // Step 4: Handover (Playbook Generation)
    // ───────────────────────────────────────────────

    private function handleStep4UserMessage(array $state, Assistant $task, string $userMessage): array
    {
        $subState = $state['sub_state'] ?? '';

        // Waiting for buyer to confirm they got their files
        if ($subState === 'confirming_download') {
            $state['step'] = 5;
            $state['sub_state'] = 'closing';
            $state['ai_turns'] = 0;

            return $this->getStep5Action($state, $task, $userMessage);
        }

        // Fallback: if still in generating or any other sub-state, advance to step 5
        $state['step'] = 5;
        $state['sub_state'] = 'closing';
        $state['ai_turns'] = 0;

        return $this->getStep5Action($state, $task, $userMessage);
    }

    // ───────────────────────────────────────────────
    // Step 5: Close
    // ───────────────────────────────────────────────

    private function handleStep5UserMessage(array $state, Assistant $task, string $userMessage): array
    {
        return $this->getStep5Action($state, $task, $userMessage);
    }

    private function getStep5Action(array $state, Assistant $task, string $userMessage): array
    {
        $subState = $state['sub_state'];
        $assistantName = $state['data']['assistant_name'] ?? 'your assistant';

        if ($subState === 'closing') {
            $state['sub_state'] = 'platform_check';
            $directive = "The buyer is ready to move forward. Let them know they have 7 days of support and can come back anytime. Then ask: 'Your Playbook and instructions are set up for Claude. Do you use a different AI platform like ChatGPT or Gemini?'";

            return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
        }

        if ($subState === 'platform_check') {
            $lowerMsg = strtolower($userMessage);

            if (str_contains($lowerMsg, 'chatgpt') || str_contains($lowerMsg, 'gemini') || str_contains($lowerMsg, 'openai') || str_contains($lowerMsg, 'copilot')) {
                $directive = "The buyer uses a different AI platform. Rewrite the assistant instructions for their platform ({$userMessage}). Generate an updated instruction set with the same content but adapted for that platform's interface and setup process. Use the <!-- INSTRUCTION_SHEET --> and <!-- INSTRUCTIONS_START --> markers so the system creates new downloads.";
                $state['sub_state'] = 'enhancements';

                return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
            }

            $state['sub_state'] = 'enhancements';
        }

        // Enhancement offers - open ended
        $directive = "You are in support mode. The buyer has their Playbook. Help them with whatever they need. If this is your first message in this sub-state, suggest one specific feature enhancement that would make sense for their process. Present it as a brief explanation of what it would do, then ask a yes/no question: 'Would you like me to add instructions for that to your assistant?' If they say yes, generate updated Playbook and instructions with the enhancement. One enhancement at a time.";

        return ['action' => 'call_ai', 'directive' => $directive, 'state' => $state];
    }

    // ───────────────────────────────────────────────
    // Helpers
    // ───────────────────────────────────────────────

    private function isAffirmative(string $message): bool
    {
        $lower = strtolower(trim($message));
        $positives = ['yes', 'yep', 'yeah', 'yup', 'sure', 'ok', 'okay', 'correct', 'right', 'that\'s right', 'thats right', 'perfect', 'looks good', 'looks great', 'great', 'good', 'love it', 'sounds good', 'sounds right', 'absolutely', 'exactly', 'spot on', 'nailed it', 'all good', 'confirmed', 'confirm'];

        foreach ($positives as $pos) {
            if ($lower === $pos || str_starts_with($lower, $pos . ' ') || str_starts_with($lower, $pos . '!') || str_starts_with($lower, $pos . '.') || str_starts_with($lower, $pos . ',')) {
                return true;
            }
        }

        return false;
    }

    private function isNegative(string $message): bool
    {
        $lower = strtolower(trim($message));
        $negatives = ['no', 'nope', 'nah', 'not really', 'nothing', 'no thanks', 'all good', 'that\'s it', 'thats it', 'nothing else', 'nope that\'s everything', 'no that\'s it', "i'm good", "im good", "that's all", "thats all"];

        foreach ($negatives as $neg) {
            if ($lower === $neg || str_starts_with($lower, $neg . ' ') || str_starts_with($lower, $neg . '!') || str_starts_with($lower, $neg . '.')) {
                return true;
            }
        }

        return false;
    }

    private function containsName(string $message, string $suggestedName): bool
    {
        return stripos($message, $suggestedName) !== false;
    }

    private function extractProvidedName(string $message): ?string
    {
        $lower = strtolower(trim($message));

        // Pattern 1: Explicit naming phrases like "call it Sarah", "I like Sarah", "let's go with Sarah"
        $explicitPattern = '/(?:call (?:it|her|him)|go with|i (?:like|prefer|want)|let\'?s? (?:go with|use|call)|name (?:it|her|him))\s+(\w+)/i';

        if (preg_match($explicitPattern, trim($message), $matches)) {
            $name = ucfirst(strtolower(trim($matches[1])));
            if (strlen($name) >= 2 && strlen($name) <= 15 && ! $this->isCommonWord($name)) {
                return $name;
            }
        }

        // Pattern 2: Single word that looks like a proper name (capitalised, not a common word)
        if (preg_match('/^(\w+)$/i', trim($message), $matches)) {
            $name = ucfirst(strtolower(trim($matches[1])));
            // Much stricter filter for single-word responses — only accept if it looks like a human name
            if (strlen($name) >= 2 && strlen($name) <= 15 && ! $this->isCommonWord($name) && $this->looksLikeName($name)) {
                return $name;
            }
        }

        return null;
    }

    private function isCommonWord(string $word): bool
    {
        $commonWords = [
            'yes', 'no', 'sure', 'ok', 'okay', 'other', 'different', 'another', 'more',
            'options', 'please', 'thanks', 'something', 'none', 'nope', 'nah', 'yep',
            'yeah', 'maybe', 'varies', 'depends', 'both', 'either', 'neither', 'all',
            'some', 'few', 'many', 'much', 'most', 'each', 'every', 'any', 'several',
            'manual', 'manually', 'automatic', 'always', 'never', 'sometimes', 'often',
            'weekly', 'daily', 'monthly', 'hourly', 'gmail', 'outlook', 'email', 'emails',
            'blog', 'post', 'posts', 'content', 'social', 'media', 'instagram', 'facebook',
            'linkedin', 'twitter', 'buffer', 'hootsuite', 'later', 'canva', 'notion',
            'slack', 'zoom', 'teams', 'drive', 'docs', 'sheets', 'trello', 'asana',
            'great', 'good', 'fine', 'perfect', 'right', 'correct', 'wrong', 'true', 'false',
            'help', 'skip', 'next', 'back', 'done', 'stop', 'start', 'continue',
        ];

        return in_array(strtolower($word), $commonWords);
    }

    private function looksLikeName(string $word): bool
    {
        // Check against our known name list first
        foreach (self::NAMES as $name) {
            if (strcasecmp($word, $name) === 0) {
                return true;
            }
        }

        // Common human name patterns: 3-8 chars, starts with consonant or vowel, no digits
        if (preg_match('/^\d/', $word)) {
            return false;
        }

        // Reject words that are clearly not names (end in common suffixes)
        $nonNameSuffixes = ['ing', 'tion', 'ally', 'ment', 'ness', 'ble', 'ful', 'less', 'ous', 'ive', 'ary', 'ery', 'ory'];
        foreach ($nonNameSuffixes as $suffix) {
            if (str_ends_with(strtolower($word), $suffix) && strlen($word) > strlen($suffix) + 2) {
                return false;
            }
        }

        // Accept short words (2-8 chars) as potential names if they passed all filters
        return strlen($word) <= 8;
    }

    private function getAlternativeNames(string $exclude): array
    {
        $filtered = array_values(array_filter(self::NAMES, fn ($n) => $n !== $exclude));
        $keys = array_rand($filtered, min(4, count($filtered)));
        if (! is_array($keys)) {
            $keys = [$keys];
        }

        return array_map(fn ($k) => $filtered[$k], $keys);
    }
}
