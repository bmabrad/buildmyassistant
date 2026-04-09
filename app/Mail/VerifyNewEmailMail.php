<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyNewEmailMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $verifyUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm your new email address',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-new-email',
            with: [
                'userName' => $this->user->first_name ?? explode(' ', $this->user->name)[0],
                'verifyUrl' => $this->verifyUrl,
            ],
        );
    }
}
