<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AproveitamentoRascunho extends Model
{
    protected $table = 'aproveitamento_rascunhos';

    protected $fillable = [
        'user_id',
        'requerida_coddis',
        'disciplinas',
        'historicos',
    ];

    protected $casts = [
        'disciplinas' => 'array',
        'historicos' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
