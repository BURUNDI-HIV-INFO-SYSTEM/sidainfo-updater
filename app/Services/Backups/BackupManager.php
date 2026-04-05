<?php

namespace App\Services\Backups;

use App\Models\BackupDestination;

class BackupManager
{
    public function __construct(
        private BackupArchiveService $backupArchiveService,
        private GmailBackupSender $gmailBackupSender,
        private OneDriveBackupSender $oneDriveBackupSender,
    ) {
    }

    public function run(BackupDestination $destination): array
    {
        $archive = $this->backupArchiveService->create();

        try {
            $delivery = match ($destination->driver) {
                'gmail' => $this->gmailBackupSender->send($destination, $archive),
                'onedrive' => $this->oneDriveBackupSender->send($destination, $archive),
                default => throw new \RuntimeException('Unsupported backup destination driver.'),
            };

            return array_merge($delivery, [
                'filename' => $archive['filename'],
                'size_bytes' => $archive['size_bytes'],
            ]);
        } finally {
            if (is_file($archive['path'] ?? '')) {
                @unlink($archive['path']);
            }
        }
    }
}
