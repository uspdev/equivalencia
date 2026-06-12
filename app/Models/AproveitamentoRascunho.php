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
    ];

    protected $casts = [
        'disciplinas' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
