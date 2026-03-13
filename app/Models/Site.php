<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $primaryKey = 'siteid';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'siteid',
        'site_name',
        'province',
        'district',
        'active',
        'current_version',
        'last_status',
        'last_checked_at',
        'last_installed_at',
        'last_event_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_installed_at' => 'datetime',
    ];

    public function events()
    {
        return $this->hasMany(SiteUpdateEvent::class, 'siteid', 'siteid');
    }

    public function lastEvent()
    {
        return $this->belongsTo(SiteUpdateEvent::class, 'last_event_id');
    }
}
