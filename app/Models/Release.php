<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $fillable = [
        'version',
        'archive_name',
        'file_path',
        'sha256',
        'size_bytes',
        'minimum_required_version',
        'notes',
        'is_active',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'size_bytes' => 'integer',
        'published_at' => 'datetime',
    ];

    public function formattedSize(): string
    {
        if (!$this->size_bytes) {
            return 'Unknown';
        }
        $bytes = $this->size_bytes;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        return number_format($bytes / 1024, 2) . ' KB';
    }

    public function adoptionCount(): int
    {
        return Site::where('current_version', $this->version)->count();
    }
}
