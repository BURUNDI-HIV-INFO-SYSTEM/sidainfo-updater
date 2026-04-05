<?php

namespace App\Services\Backups;

use App\Mail\BackupArchiveMail;
use App\Models\BackupDestination;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class GmailBackupSender
{
    public function send(BackupDestination $destination, array $archive): array
    {
        $maxSize = (int) config('backups.gmail_max_archive_bytes', 18 * 1024 * 1024);

        if (($archive['size_bytes'] ?? 0) > $maxSize) {
            throw new RuntimeException(
                'Email backups are limited to ' . $this->formatBytes($maxSize) . '. Use OneDrive for larger backups.'
            );
        }

        Mail::to($destination->config['recipient_email'])
            ->send(new BackupArchiveMail(
                archivePath: $archive['path'],
                archiveName: $archive['filename'],
                destinationName: $destination->name,
            ));

        return [
            'message' => "Backup emailed to {$destination->config['recipient_email']}.",
        ];
    }

    private function formatBytes(int $bytes): string
    {
        return number_format($bytes / 1024 / 1024, 1) . ' MB';
    }
}
