<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteUpdateEvent extends Model
{
    public $timestamps = false; // append-only, only has created_at

    protected $fillable = [
        'siteid',
        'event_type',
        'status',
        'current_version',
        'target_version',
        'archive',
        'message',
        'source_ip',
        'payload_json',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at' => 'datetime',
    ];

    const UPDATED_AT = null;

    public function site()
    {
        return $this->belongsTo(Site::class, 'siteid', 'siteid');
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'installed' => 'green',
            'failed'    => 'red',
            'checked'   => 'blue',
            default     => 'gray',
        };
    }
}
