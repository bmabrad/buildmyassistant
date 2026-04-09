<?php

namespace App\Services;

use App\Models\Assistant;
use App\Models\Chat;
use Illuminate\Http\Response;
use Spatie\Browsershot\Browsershot;

class PlaybookPdfService
{
    /**
     * Parse Playbook markdown into sections.
     *
     * The Playbook has 5 sections:
     * 1. Your Bottleneck
     * 2. Your Process Map
     * 3. How Your Assistant Works
     * 4. Getting Started
     * 5. What Happens Next
     */
    public function parseSections(string $markdown): array
    {
        // Try multiple header patterns in order of specificity

        // Pattern 1: Numbered bold or heading — "**1. Title**" or "## 1. Title"
        $pattern1 = '/(?:^|\n)(?:#{1,3}\s+|\*{2})\d+\.\s+([^*\n]+?)\*{0,2}\s*$/m';

        // Pattern 2: Any ## heading (the most common AI output format)
        $pattern2 = '/(?:^|\n)#{1,2}\s+([^\n]+)$/m';

        foreach ([$pattern1, $pattern2] as $pattern) {
            $parts = preg_split($pattern, $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);

            $sections = [];
            for ($i = 1; $i < count($parts) - 1; $i += 2) {
                $title = trim($parts[$i], " *\t\n\r");
                $content = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';

                // Skip sections that look like the assistant instructions (after the playbook)
                if (preg_match('/Complete Instruction Sheet|Assistant Instructions/i', $title)) {
                    break;
                }

                $sections[] = [
                    'title' => $title,
                    'content' => $content,
                ];
            }

            if (count($sections) >= 2) {
                break; // Good enough, use these sections
            }
        }

        // Strip trailing transition text from last section
        if (! empty($sections)) {
            $last = &$sections[count($sections) - 1];
            $last['content'] = preg_replace('/\n---\s*\n(?:And here|Here are|Save this|There you go).*$/si', '', $last['content']);
            $last['content'] = trim($last['content']);
        }

        if (empty($sections)) {
            $sections[] = [
                'title' => 'Your AI Assistant Playbook',
                'content' => $markdown,
            ];
        }

        return $sections;
    }

    /**
     * Convert markdown content to simple HTML for the PDF.
     */
    public function markdownToHtml(string $markdown): string
    {
        // ── 1. Stat blocks: lines after --- matching "value — label" or "value - label" ──
        $markdown = preg_replace_callback(
            '/\n---\n((?:.+\s*(?:—|–|-)\s*.+\n?)+)/',
            function ($matches) {
                $lines = array_filter(array_map('trim', explode("\n", trim($matches[1]))));
                $cards = '';
                foreach ($lines as $line) {
                    if (preg_match('/^(.+?)\s*(?:—|–|-)\s+(.+)$/', $line, $m)) {
                        $cards .= '<div class="stat-card"><div class="stat-value">' . e(trim($m[1], '* ')) . '</div><div class="stat-label">' . e(trim($m[2])) . '</div></div>';
                    }
                }

                return '<div class="stat-row">' . $cards . '</div>';
            },
            $markdown
        );

        // ── 2. Process steps: "N. **Title** — desc *(Tag)*" ──
        $markdown = preg_replace_callback(
            '/(?:^\d+\.\s+\*\*[^*]+\*\*\s*(?:—|–|-)\s*.+\n?)+/m',
            function ($matches) {
                $lines = array_filter(array_map('trim', explode("\n", trim($matches[0]))));
                $steps = '';
                $count = count($lines);
                foreach ($lines as $i => $line) {
                    if (! preg_match('/^(\d+)\.\s+\*\*([^*]+)\*\*\s*(?:—|–|-)\s*(.+)$/', $line, $m)) {
                        continue;
                    }
                    $num = $m[1];
                    $title = trim($m[2]);
                    $desc = trim($m[3]);
                    $tag = '';
                    $tagClass = '';
                    if (preg_match('/\*\(([^)]+)\)\*\s*$/', $desc, $tm)) {
                        $tag = trim($tm[1]);
                        $desc = trim(preg_replace('/\s*\*\([^)]+\)\*\s*$/', '', $desc));
                        $tagLower = strtolower($tag);
                        if (str_contains($tagLower, 'manual') || str_contains($tagLower, 'always') || str_contains($tagLower, 'firm')) {
                            $tagClass = 'tag-manual';
                        } elseif (str_contains($tagLower, 'learn')) {
                            $tagClass = 'tag-learnable';
                        } else {
                            $tagClass = 'tag-automatic';
                        }
                    }
                    $isLast = ($i === $count - 1);
                    $lineHtml = $isLast ? '' : '<div class="process-step-line"></div>';
                    $tagHtml = $tag ? '<span class="process-step-tag ' . $tagClass . '">' . e($tag) . '</span>' : '';
                    $steps .= '<div class="process-step"><div class="process-step-indicator"><div class="process-step-dot">' . e($num) . '</div>' . $lineHtml . '</div><div class="process-step-content"><div class="process-step-title">' . e($title) . '</div><div class="process-step-desc">' . e($desc) . '</div>' . $tagHtml . '</div></div>';
                }

                return '<div class="process-steps">' . $steps . '</div>';
            },
            $markdown
        );

        // ── 3. Feature grid: consecutive lines "emoji **Title** | Description" ──
        $markdown = preg_replace_callback(
            '/(?:^.{1,4}\s+\*\*[^*]+\*\*\s*\|\s*.+\n?){2,}/m',
            function ($matches) {
                $lines = array_filter(array_map('trim', explode("\n", trim($matches[0]))));
                $cards = '';
                foreach ($lines as $line) {
                    if (preg_match('/^(.{1,4})\s+\*\*([^*]+)\*\*\s*\|\s*(.+)$/', $line, $m)) {
                        $icon = trim($m[1]);
                        $title = trim($m[2]);
                        $desc = trim($m[3]);
                        $cards .= '<div class="feature-card"><div class="feature-card-icon">' . $icon . '</div><div class="feature-card-title">' . e($title) . '</div><div class="feature-card-desc">' . e($desc) . '</div></div>';
                    }
                }

                return '<div class="feature-grid">' . $cards . '</div>';
            },
            $markdown
        );

        // ── 4. Timeline block: "### Heading" followed by "- **Label:** text" items ──
        $markdown = preg_replace_callback(
            '/^###\s+(.+)\n((?:\s*[-•]\s+\*\*.+?\*\*:?\s+.+\n?)+)/m',
            function ($matches) {
                $title = trim($matches[1]);
                $lines = array_filter(array_map('trim', explode("\n", trim($matches[2]))));
                $items = '';
                foreach ($lines as $line) {
                    if (preg_match('/^[-•]\s+\*\*(.+?)\*\*:?\s+(.+)$/', $line, $m)) {
                        $items .= '<div class="timeline-item"><div class="timeline-marker"></div><div class="timeline-text"><strong>' . e(trim($m[1])) . ':</strong> ' . e(trim($m[2])) . '</div></div>';
                    }
                }

                return '<div class="timeline-block"><div class="timeline-block-title">' . e($title) . '</div>' . $items . '</div>';
            },
            $markdown
        );

        // ── 4. Highlight box: "> **Label:** text" blockquote ──
        $markdown = preg_replace_callback(
            '/^>\s+\*\*(.+?):?\*\*:?\s+(.+)$/m',
            function ($matches) {
                return '<div class="highlight-box"><div class="highlight-box-label">' . e(trim($matches[1])) . '</div><div class="highlight-box-value">' . e(trim($matches[2])) . '</div></div>';
            },
            $markdown
        );

        // ── 5. Test task box: "**First test task:**" paragraph ──
        $markdown = preg_replace_callback(
            '/^\*\*First test task:?\*\*:?\s*(.+)$/mi',
            function ($matches) {
                return '<div class="test-task-box"><div class="test-task-label">Your first test task</div><div class="test-task-text">' . e(trim($matches[1])) . '</div></div>';
            },
            $markdown
        );

        // ── 6. Rules list: consecutive bullets starting with Always/Never ──
        $markdown = preg_replace_callback(
            '/(?:^[-•]\s+(?:Always|Never)\b.+\n?){2,}/mi',
            function ($matches) {
                $lines = array_filter(array_map('trim', explode("\n", trim($matches[0]))));
                $items = '';
                foreach ($lines as $line) {
                    if (preg_match('/^[-•]\s+(.+)$/', $line, $m)) {
                        $text = trim($m[1]);
                        $isAlways = preg_match('/^Always\b/i', $text);
                        $iconClass = $isAlways ? 'rule-always' : 'rule-never';
                        $icon = $isAlways ? '&#10003;' : '&#10005;';
                        $items .= '<div class="rule-item"><div class="rule-icon ' . $iconClass . '">' . $icon . '</div><div class="rule-text">' . e($text) . '</div></div>';
                    }
                }

                return '<div class="rules-list">' . $items . '</div>';
            },
            $markdown
        );

        // ── 7. Code blocks ──
        $html = preg_replace_callback('/```[\w]*\n(.*?)```/s', function ($matches) {
            return '<div class="code-block"><pre>' . e($matches[1]) . '</pre></div>';
        }, $markdown);

        // ── 8. Inline formatting ──
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);

        // ── 9. Numbered lists as setup steps (process steps already handled above) ──
        $html = preg_replace_callback(
            '/(?:^\d+\.\s+.+\n?)+/m',
            function ($matches) {
                $lines = array_filter(array_map('trim', explode("\n", trim($matches[0]))));
                $steps = '';
                foreach ($lines as $line) {
                    if (preg_match('/^(\d+)\.\s+(.+)$/', $line, $m)) {
                        $text = $m[2];
                        $hint = '';
                        // Split first sentence as title, rest as hint
                        if (preg_match('/^(.+?\.)\s+(.+)$/', $text, $tm)) {
                            $text = $tm[1];
                            $hint = $tm[2];
                        }
                        $hintHtml = $hint ? '<span class="setup-step-hint">' . $hint . '</span>' : '';
                        $steps .= '<div class="setup-step"><div class="setup-step-num">' . e($m[1]) . '</div><div class="setup-step-text">' . $text . $hintHtml . '</div></div>';
                    }
                }

                return '<div class="setup-steps">' . $steps . '</div>';
            },
            $html
        );

        // ── 10. Bullet lists (generic — rules already handled above) ──
        $html = preg_replace('/^[-•]\s+(.+)$/m', '<li class="bullet">$1</li>', $html);
        $html = preg_replace('/(<li class="bullet">.*?<\/li>\s*)+/s', '<ul>$0</ul>', $html);

        // ── 11. Paragraphs ──
        $lines = preg_split('/\n{2,}/', $html);
        $result = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (preg_match('/^<(ol|ul|div|li)/', $line)) {
                $result .= $line;
            } else {
                $result .= '<p>' . nl2br($line) . '</p>';
            }
        }

        return $result;
    }

    /**
     * Extract a short subtitle from the section title.
     *
     * If the title contains a separator (— or : or -), the part after
     * the separator becomes the subtitle and the part before stays as
     * the title. Otherwise subtitle is left empty.
     */
    public function extractSubtitle(string $title): array
    {
        if (preg_match('/^(.+?)\s*(?:—|–|-|:)\s*(.+)$/', $title, $m)) {
            return [trim($m[1]), trim($m[2])];
        }

        return [$title, ''];
    }

    /**
     * Generate the branded Playbook PDF using Browsershot (headless Chrome).
     */
    public function generate(Assistant $task, Chat $deliverable): string
    {
        $playbookContent = $deliverable->playbook_content;
        $instructionsContent = $deliverable->instructions_content;

        // If playbook_content wasn't pre-parsed (e.g. seeded data), split on the marker
        if (! $playbookContent) {
            $raw = $deliverable->content ?? '';
            $marker = '<!-- INSTRUCTIONS_START -->';
            if (str_contains($raw, $marker)) {
                [$playbookContent, $instructionsContent] = array_map('trim', explode($marker, $raw, 2));
            } else {
                $playbookContent = $raw;
            }
        }

        $sections = $this->parseSections($playbookContent);
        $buyerName = $task->name ?? 'Valued Customer';
        $assistantName = $task->assistant_name;
        $sessionDate = $task->created_at->format('j F Y');

        // Encode logo as base64 data URI for embedded rendering
        $logoPath = public_path('images/logos/logo_long_white_text_2x.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }

        // Convert section content to HTML and extract subtitles
        foreach ($sections as &$section) {
            $section['html'] = $this->markdownToHtml($section['content']);
            [$section['title'], $section['subtitle']] = $this->extractSubtitle($section['title']);
        }

        // Prepare appendix (assistant instructions) as raw escaped markdown
        $appendixHtml = null;
        if ($instructionsContent) {
            $appendixHtml = e($instructionsContent);
        }

        // Build cover description from bottleneck summary if available
        $assistantDescription = $task->bottleneck_summary
            ? 'Your ' . lcfirst(rtrim($task->bottleneck_summary, '.')) . ' assistant'
            : null;

        $footerText = $buyerName . ' — AI Assistant Playbook';

        $html = view('pdf.playbook', [
            'sections' => $sections,
            'buyerName' => $buyerName,
            'assistantName' => $assistantName,
            'assistantDescription' => $assistantDescription,
            'sessionDate' => $sessionDate,
            'logoBase64' => $logoBase64,
            'appendixHtml' => $appendixHtml,
            'footerText' => $footerText,
        ])->render();

        return Browsershot::html($html)
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->pdf();
    }

    /**
     * Return a download response for the generated PDF.
     */
    public function download(Assistant $task, Chat $deliverable): Response
    {
        $pdf = $this->generate($task, $deliverable);
        $filename = $this->filename($task);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Generate the filename for the PDF download.
     */
    public function filename(Assistant $task): string
    {
        $name = $task->name ?? 'Customer';

        return $name . ' - AI Assistant Playbook.pdf';
    }
}
