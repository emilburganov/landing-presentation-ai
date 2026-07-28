<?php

namespace App\Services\Contact\Mail\Mailables;

use App\Services\Contact\Analysis\CommentAnalysis;
use App\Services\Contact\DTO\ContactDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserContactCopyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ContactDTO      $contact,
        public readonly CommentAnalysis $analysis,
    )
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Копия вашего обращения',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact.user-copy',
        );
    }
}
