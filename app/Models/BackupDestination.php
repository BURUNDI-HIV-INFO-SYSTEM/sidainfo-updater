<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupDestination extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'is_active',
        'config',
        'last_run_at',
        'last_backup_filename',
        'last_backup_size_bytes',
        'last_status',
        'last_message',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'encrypted:array',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function driverLabel(): string
    {
        return match ($this->driver) {
            'gmail' => 'Gmail',
            'onedrive' => 'OneDrive',
            default => ucfirst($this->driver),
        };
    }

    public function formattedBackupSize(): ?string
    {
        if (! $this->last_backup_size_bytes) {
            return null;
        }

        $size = $this->last_backup_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = 0;

        while ($size >= 1024 && $power < count($units) - 1) {
            $size /= 1024;
            $power++;
        }

        return number_format($size, $power === 0 ? 0 : 1) . ' ' . $units[$power];
    }
}
