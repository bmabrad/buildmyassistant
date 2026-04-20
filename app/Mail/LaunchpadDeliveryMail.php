<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LaunchpadDeliveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Lead $lead,
        public array $output,
        public string $pdfPath,
        public string $pdfFilename = 'launchpad.pdf',
    ) {}

    public function envelope(): Envelope
    {
        $fallbackSubject = 'Your starter prompt for your first AI assistant, ' . ($this->lead->buyer_name ?: 'there');

        return new Envelope(
            subject: $this->output['email_body']['subject'] ?? $fallbackSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.launchpad',
            with: [
                'lead' => $this->lead,
                'output' => $this->output,
                'subject' => $this->output['email_body']['subject'] ?? 'Your starter prompt',
                'builderUrl' => url('/'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as($this->pdfFilename)
                ->withMime('application/pdf'),
        ];
    }
}
