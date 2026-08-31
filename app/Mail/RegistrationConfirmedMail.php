<?php

namespace App\Mail;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationConfirmedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Participant $participant)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pendaftaran Running Event Berhasil Dikonfirmasi',
        );
    }

    public function content(): Content
    {
        $this->participant->loadMissing(['raceCategory', 'event']);

        return new Content(
            markdown: 'emails.registration-confirmed',
            with: ['participant' => $this->participant],
        );
    }
}
