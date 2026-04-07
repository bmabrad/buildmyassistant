<?php

namespace App\Mail;

use App\Models\Assistant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LaunchpadCompletionMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $buyerName;
    public string $chatUrl;
    public string $assistantName;
    public string $assistantHandles;

    public function __construct(public Assistant $task)
    {
        $this->buyerName = $task->name;
        $this->chatUrl = url("/launchpad/{$task->token}");

        $instructionSheet = $task->chats()
            ->where('is_instruction_sheet', true)
            ->latest('created_at')
            ->first();

        $content = $instructionSheet?->content ?? '';
        $this->assistantName = $this->extractAssistantName($content);
        $this->assistantHandles = $this->extractAssistantHandles($content);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AI assistant instructions are ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.launchpad-completion',
        );
    }

    public static function extractAssistantName(string $content): string
    {
        // Match "Assistant name" heading followed by the name
        if (preg_match('/#+\s*Assistant\s+name[:\s]*\n+\s*(.+)/i', $content, $matches)) {
            return trim(strip_tags($matches[1]));
        }

        // Fallback: look for bold pattern like **Assistant name:** Sarah or **Assistant name**: Sarah
        if (preg_match('/\*\*Assistant\s+name[:\s]*\*\*[:\s]*(.+)/i', $content, $matches)) {
            return trim(strip_tags($matches[1]));
        }

        return 'your AI assistant';
    }

    public static function extractAssistantHandles(string $content): string
    {
        // Match "What the assistant handles" heading followed by content
        if (preg_match('/#+\s*What\s+the\s+assistant\s+handles[:\s]*\n+([\s\S]*?)(?=\n#+\s|\z)/i', $content, $matches)) {
            $text = trim(strip_tags($matches[1]));
            // Take just the first line/sentence for the email summary
            $firstLine = strtok($text, "\n");

            return trim($firstLine ?: $text);
        }

        // Fallback: bold pattern
        if (preg_match('/\*\*What\s+the\s+assistant\s+handles[:\s]*\*\*[:\s]*(.+)/i', $content, $matches)) {
            return trim(strip_tags($matches[1]));
        }

        return 'the process you described';
    }
}
