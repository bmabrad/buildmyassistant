<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminLaunchpadAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public Lead $lead,
        public string $reason,
        public array $context = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Launchpad] {$this->reason} — {$this->lead->buyer_email}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-launchpad-alert',
            with: [
                'lead' => $this->lead,
                'reason' => $this->reason,
                'context' => $this->context,
                'adminUrl' => url('/admin/leads/' . $this->lead->id),
            ],
        );
    }
}
