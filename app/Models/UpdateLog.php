<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    protected $fillable = [
        'site_id',
        'target_version',
        'status',
        'error_message',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
