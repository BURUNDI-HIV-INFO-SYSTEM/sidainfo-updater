<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $fillable = [
        'version_number',
        'release_date',
        'minimum_required_version',
        'file_path',
        'is_active',
    ];

    protected $casts = [
        'release_date' => 'date',
        'is_active' => 'boolean',
    ];
