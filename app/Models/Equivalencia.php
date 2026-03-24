<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equivalencia extends Model
{
    protected $fillable = [
        'verdis',
        'codcur',
        'codhab',
        'coddis',
        'nome_disciplina',
        'creditos',
        'carga_horaria',
        'nomcur',
        'ies',
        'ano',
        'semestre',
        'frequencia',
        'nota',
        'tipo',
        'equivalencias_id',
        'pdf_path',
    ];

    // Relacionamento para auto-relacionamento (disciplinas equivalentes)
    // Uma disciplina pode ter uma disciplina equivalente (parent) 
    // e pode ser equivalente a várias outras disciplinas (children)
    public function parent()
    {
        return $this->belongsTo(Equivalencia::class, 'equivalencias_id');
    }

    // Uma disciplina pode ser equivalente a várias outras disciplinas (children)
    public function children()
    {
        return $this->hasMany(Equivalencia::class, 'equivalencias_id');
    }
}
