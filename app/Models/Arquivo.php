<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Arquivo extends Model
{
    public const TIPO_HISTORICO = 'historico';

    public const TIPO_EMENTA = 'ementa';

    protected $fillable = [
        'equivalencia_id',
        'tipo',
        'nome',
        'path',
    ];

    public function equivalencia()
    {
        return $this->belongsTo(Equivalencia::class);
    }
}
