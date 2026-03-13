<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [
        'name',
        'url',
        'installation_key',
        'current_version',
        'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function updateLogs()
    {
        return $this->hasMany(UpdateLog::class);
    }
