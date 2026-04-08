<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\PromptSegment;

class PromptAssembler
{
    /**
     * Assemble the full system prompt for a given task.
     *
     * The prompt is built from three layers:
     *   1. Base segments   — always included, stable prefix (cacheable)
     *   2. Step segment    — one segment matching the task's current phase
     *   3. Context segments — conditionally loaded based on session state
     *
     * The buyer's name, email, and exchange count are appended at the end.
     */
    public function assemble(Assistant $task): string
    {
        $parts = [];

        // Layer 1: Base segments (always included, in sort order)
        $baseSegments = PromptSegment::active()
            ->base()
            ->ordered()
            ->pluck('content');

        foreach ($baseSegments as $content) {
            $parts[] = $content;
        }

        // Layer 2: Current step segment
        $stepSegment = PromptSegment::active()
            ->step($task->phase)
            ->ordered()
            ->first();

        if ($stepSegment) {
            $parts[] = $stepSegment->content;
        }

        // Layer 3: Context segments (conditionally loaded)
        $contextKeys = $this->resolveContextKeys($task);

        if (!empty($contextKeys)) {
            $contextSegments = PromptSegment::active()
                ->context()
                ->whereIn('key', $contextKeys)
                ->ordered()
                ->pluck('content');

            foreach ($contextSegments as $content) {
                $parts[] = $content;
            }
        }

        // Append buyer context variables
        $parts[] = $this->buildBuyerContext($task);

        return implode("\n\n", $parts);
    }

    /**
     * Get the assembled prompt split into a cacheable prefix and dynamic suffix.
     *
     * Use this when sending to the Claude API with prompt caching enabled.
     * The prefix (base segments) stays identical across requests and gets cached.
     * The suffix (step + context + buyer vars) changes per request.
     */
    public function assembleWithCacheBreak(Assistant $task): array
    {
        $baseSegments = PromptSegment::active()
            ->base()
            ->ordered()
            ->pluck('content')
            ->toArray();

        $prefix = implode("\n\n", $baseSegments);

        $suffixParts = [];

        $stepSegment = PromptSegment::active()
            ->step($task->phase)
            ->ordered()
            ->first();

        if ($stepSegment) {
            $suffixParts[] = $stepSegment->content;
        }

        $contextKeys = $this->resolveContextKeys($task);

        if (!empty($contextKeys)) {
            $contextSegments = PromptSegment::active()
                ->context()
                ->whereIn('key', $contextKeys)
                ->ordered()
                ->pluck('content')
                ->toArray();

            $suffixParts = array_merge($suffixParts, $contextSegments);
        }

        $suffixParts[] = $this->buildBuyerContext($task);

        $suffix = implode("\n\n", $suffixParts);

        return [
            'cached_prefix' => $prefix,
            'dynamic_suffix' => $suffix,
        ];
    }

    /**
     * Determine which context segment keys should be loaded for this task.
     */
    protected function resolveContextKeys(Assistant $task): array
    {
        $keys = [];

        // During Pre-Playbook, prevent any upsell language
        if (! $task->in_post_playbook) {
            $keys[] = 'context_no_upsell';
        }

        if ($task->isReturnVisit()) {
            $keys[] = 'context_return_visit';
        }

        if ($task->in_post_playbook) {
            $keys[] = 'context_post_playbook';
        }

        return $keys;
    }

    /**
     * Build the buyer context block appended to the end of the prompt.
     */
    protected function buildBuyerContext(Assistant $task): string
    {
        $exchangeCount = $task->chats()->count();

        $lines = [
            '## Buyer context (injected by the system)',
            '',
            "The buyer's name is: {$task->name}",
            "The buyer's email is: {$task->email}",
            "Messages so far in this session: {$exchangeCount}",
        ];

        // Add Post-Playbook context for the AI guide
        if ($task->in_post_playbook) {
            $daysRemaining = $task->supportDaysRemaining();
            $daysText = match (true) {
                $daysRemaining === null => 'unknown',
                $daysRemaining === 0 => '0 (window expired)',
                $daysRemaining === -1 => 'less than 1 day',
                default => "{$daysRemaining} days",
            };

            $lines[] = "Fast Track nudge count so far: {$task->fast_track_nudge_count}";
            $lines[] = "Support days remaining: {$daysText}";
        }

        return implode("\n", $lines);
    }
}
