<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromptSegment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PromptBuilderController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()?->is_admin, 403);

        return view('admin.prompt-builder');
    }

    public function chat(Request $request): JsonResponse
    {
        abort_unless($request->user()?->is_admin, 403);

        $request->validate([
            'message' => 'required|string|max:5000',
            'history' => 'nullable|array',
        ]);

        $segments = PromptSegment::active()->ordered()->get();

        $promptSnapshot = $segments->map(function ($seg) {
            return "[{$seg->key}] ({$seg->category}" .
                ($seg->step_number ? ", step {$seg->step_number}" : '') .
                ")\n{$seg->content}";
        })->implode("\n\n---\n\n");

        $builderSystemPrompt = $this->getBuilderSystemPrompt($promptSnapshot);

        $messages = [];

        if ($request->history) {
            foreach ($request->history as $msg) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $request->message,
        ];

        $response = Http::withHeaders([
            'x-api-key' => config('services.anthropic.api_key'),
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => config('services.anthropic.model', 'claude-sonnet-4-20250514'),
            'max_tokens' => 4096,
            'system' => $builderSystemPrompt,
            'messages' => $messages,
        ]);

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Failed to get response from Claude API.',
            ], 500);
        }

        $body = $response->json();
        $assistantReply = $body['content'][0]['text'] ?? '';

        $updates = $this->extractUpdates($assistantReply);
        $appliedUpdates = [];

        if (!empty($updates)) {
            $appliedUpdates = $this->applyUpdates($updates);
            $assistantReply = $this->stripUpdateBlock($assistantReply);
        }

        return response()->json([
            'reply' => $assistantReply,
            'updates_applied' => $appliedUpdates,
        ]);
    }

    protected function getBuilderSystemPrompt(string $currentPromptSnapshot): string
    {
        return <<<SYSTEM
You are the Prompt Builder for the AI Assistant Launchpad. Your job is to help the admin (Brad) review and update the system prompt that powers the buyer-facing chat.

## Current prompt segments

The system prompt is stored as separate segments in a database. Here is the current state of every active segment:

{$currentPromptSnapshot}

## How you work

1. The admin will chat with you about changes they want to make to the system prompt.
2. You discuss the change, offer suggestions, and help refine the wording.
3. When the admin confirms a change, you output the updated segment content wrapped in a special JSON block so the system can apply it automatically.

## Output format for changes

When you have a confirmed change to apply, include a JSON block at the END of your message in this exact format:

```prompt_update
[
  {
    "action": "update",
    "key": "base_personality",
    "content": "The full updated content for this segment goes here."
  }
]
```

Available actions:
- "update" — replace the content of an existing segment (identified by key)
- "create" — add a new segment. Include "key", "label", "category", "step_number" (null if not a step), "sort_order", and "content".
- "deactivate" — set is_active to false for a segment (identified by key)

## Rules

- Never change a segment without the admin confirming first. Always show them the proposed change and get a yes before outputting the update block.
- When proposing changes, show only the specific section that would change, not the entire segment, so the admin can review quickly.
- You can propose changes to multiple segments at once if they are related.
- Keep the existing tone and style consistent across segments.
- If the admin asks to see the current prompt or a specific segment, show it to them from your context. No need to fetch it again.
- Be concise. The admin prefers short, direct responses.
SYSTEM;
    }

    protected function extractUpdates(string $reply): array
    {
        if (preg_match('/```prompt_update\s*\n(.*?)\n```/s', $reply, $matches)) {
            $decoded = json_decode($matches[1], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function applyUpdates(array $updates): array
    {
        $applied = [];

        foreach ($updates as $update) {
            $action = $update['action'] ?? null;
            $key = $update['key'] ?? null;

            if (!$action || !$key) {
                continue;
            }

            switch ($action) {
                case 'update':
                    $segment = PromptSegment::where('key', $key)->first();
                    if ($segment) {
                        $segment->update(['content' => $update['content']]);
                        $applied[] = "Updated: {$segment->label} ({$key})";
                    }
                    break;

                case 'create':
                    PromptSegment::create([
                        'key'         => $key,
                        'label'       => $update['label'] ?? Str::headline($key),
                        'category'    => $update['category'] ?? 'context',
                        'step_number' => $update['step_number'] ?? null,
                        'sort_order'  => $update['sort_order'] ?? 999,
                        'content'     => $update['content'],
                        'is_active'   => true,
                    ]);
                    $applied[] = "Created: {$key}";
                    break;

                case 'deactivate':
                    $segment = PromptSegment::where('key', $key)->first();
                    if ($segment) {
                        $segment->update(['is_active' => false]);
                        $applied[] = "Deactivated: {$segment->label} ({$key})";
                    }
                    break;
            }
        }

        return $applied;
    }

    protected function stripUpdateBlock(string $reply): string
    {
        return trim(preg_replace('/```prompt_update\s*\n.*?\n```/s', '', $reply));
    }
}
