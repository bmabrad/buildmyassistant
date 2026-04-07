<?php

namespace App\Mail;

use App\Models\Assistant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PostPurchaseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $buyerName;
    public string $chatUrl;
    public ?string $invoiceUrl;

    public function __construct(public Assistant $task)
    {
        $this->buyerName = $task->name;
        $this->chatUrl = url("/launchpad/{$task->token}");
        $this->invoiceUrl = $task->stripe_invoice_url;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your AI Assistant Launchpad is ready to go',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.post-purchase',
        );
    }
}
