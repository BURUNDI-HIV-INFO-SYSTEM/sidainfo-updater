<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamenConfig extends Model
{
    protected $table = 'examens_config';

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'nom_examen',
        'valeur_usuelle1',
        'valeur_usuelle2',
        'limite1',
        'limite2',
    ];

    protected $casts = [
        'valeur_usuelle1' => 'decimal:4',
        'valeur_usuelle2' => 'decimal:4',
        'limite1'         => 'decimal:4',
        'limite2'         => 'decimal:4',
    ];
}
