<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TarifCentral extends Model
{
    protected $table = 'tarifs_centraux';

    protected $fillable = ['code_examen', 'annee', 'prix', 'devise'];

    protected $casts = [
        'prix'  => 'decimal:2',
        'annee' => 'integer',
    ];
}
