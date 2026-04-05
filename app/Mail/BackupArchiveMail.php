<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupArchiveMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $archivePath,
        public string $archiveName,
        public string $destinationName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "SIDAInfo backup: {$this->archiveName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.backups.archive',
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->archivePath)
                ->as($this->archiveName)
                ->withMime('application/zip'),
        ];
    }
}
