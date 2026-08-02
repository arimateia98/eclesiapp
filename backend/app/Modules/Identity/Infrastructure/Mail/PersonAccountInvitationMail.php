<?php

namespace App\Modules\Identity\Infrastructure\Mail;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class PersonAccountInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $personName,
        public readonly string $acceptanceUrl,
        public readonly DateTimeInterface $expiresAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Seu convite para o Eclesiapp');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.identity.person-account-invitation');
    }
}
