<?php

namespace App\Services;

use App\Models\Assistant;
use Barryvdh\DomPDF\Facade\Pdf;

class InstructionSheetPdfService
{
    /**
     * Parse instruction sheet markdown into sections.
     *
     * The instruction sheet always has 8 bold-header sections in order:
     * 1. Assistant name
     * 2. What the assistant handles
     * 3. How it learns
     * 4. Training steps
     * 5. Your rules
     * 6. System prompt
     * 7. Setup steps
     * 8. First test task
     */
    public function parseSections(string $markdown): array
    {
        // Split on numbered bold headers like "1. **Assistant name**" or "**1. Assistant name**"
        // Also handles "## 1. Assistant name" style headers
        $pattern = '/(?:^|\n)(?:#{1,3}\s+)?\*{0,2}\d+\.\s+\*{0,2}([^*\n]+)\*{0,2}\s*(?:—|[-–]|:)?\s*/';

        $parts = preg_split($pattern, $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);

        $sections = [];
        // First element is any content before the first section header
        for ($i = 1; $i < count($parts) - 1; $i += 2) {
            $title = trim($parts[$i], " *\t\n\r");
            $content = isset($parts[$i + 1]) ? trim($parts[$i + 1]) : '';
            $sections[] = [
                'title' => $title,
                'content' => $content,
            ];
        }

        // If parsing didn't find sections, return the whole thing as one section
        if (empty($sections)) {
            $sections[] = [
                'title' => 'Instruction Sheet',
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
        // Handle code blocks (system prompt section)
        $html = preg_replace_callback('/```[\w]*\n(.*?)```/s', function ($matches) {
            return '<div class="code-block">' . nl2br(e($matches[1])) . '</div>';
        }, $markdown);

        // Bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);

        // Italic
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);

        // Numbered lists
        $html = preg_replace('/^\d+\.\s+(.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*?<\/li>\s*)+/s', '<ol>$0</ol>', $html);

        // Bullet lists
        $html = preg_replace('/^[-•]\s+(.+)$/m', '<li class="bullet">$1</li>', $html);
        $html = preg_replace('/(<li class="bullet">.*?<\/li>\s*)+/s', '<ul>$0</ul>', $html);

        // Paragraphs - split on double newlines
        $lines = preg_split('/\n{2,}/', $html);
        $result = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            // Don't wrap already-wrapped elements
            if (preg_match('/^<(ol|ul|div|li)/', $line)) {
                $result .= $line;
            } else {
                $result .= '<p>' . nl2br($line) . '</p>';
            }
        }

        return $result;
    }

    /**
     * Generate the branded PDF for an instruction sheet.
     */
    public function generate(Assistant $task, string $instructionContent): \Barryvdh\DomPDF\PDF
    {
        $sections = $this->parseSections($instructionContent);
        $buyerName = $task->name ?? 'Valued Customer';
        $sessionDate = $task->created_at->format('j F Y');
        $logoPath = public_path('images/logos/logo_long_white_text_2x.png');

        // Convert section content to HTML
        foreach ($sections as &$section) {
            $section['html'] = $this->markdownToHtml($section['content']);
        }

        $html = view('pdf.instruction-sheet', [
            'sections' => $sections,
            'buyerName' => $buyerName,
            'sessionDate' => $sessionDate,
            'logoPath' => $logoPath,
        ])->render();

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'sans-serif');

        return $pdf;
    }

    /**
     * Generate the filename for the PDF download.
     */
    public function filename(Assistant $task): string
    {
        $name = $task->name ?? 'Customer';

        return $name . ' - AI Assistant Instruction Sheet.pdf';
    }
}
