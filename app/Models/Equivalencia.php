<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Equivalencia extends Model
{
    public const TIPO_AUTOMATICA = 'a';

    public const TIPO_CURSADA = 'c';

    public const TIPO_REQUERIDA = 'r';

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

    protected $attributes = [
        'tipo' => self::TIPO_REQUERIDA,
    ];

    protected $casts = [
        'verdis' => 'integer',
        'codcur' => 'integer',
        'codhab' => 'integer',
        'creditos' => 'integer',
        'carga_horaria' => 'integer',
        'ano' => 'integer',
        'semestre' => 'integer',
        'frequencia' => 'decimal:2',
        'nota' => 'decimal:2',
    ];

    // Relacionamento para auto-relacionamento (disciplinas equivalentes)
    // Uma disciplina pode ter uma disciplina equivalente (parent)
    // e pode ser equivalente a várias outras disciplinas (children)
    public function disciplinaUsp()
    {
        return $this->belongsTo(Equivalencia::class, 'equivalencias_id')
            ->whereIn('tipo', [self::TIPO_AUTOMATICA, self::TIPO_REQUERIDA]);
    }

    public function parent()
    {
        return $this->disciplinaUsp();
    }

    // Uma disciplina pode ser equivalente a várias outras disciplinas (children)
    public function equivalentes()
    {
        return $this->hasMany(Equivalencia::class, 'equivalencias_id')
            ->where('tipo', self::TIPO_CURSADA);
    }

    // ======= Escopos para facilitar consultas =============
    public function scopeUsp(Builder $query): Builder
    {
        return $query
            ->whereNull('equivalencias_id')
            ->whereIn('tipo', [self::TIPO_AUTOMATICA, self::TIPO_REQUERIDA]);
    }

    public function scopeEquivalencia(Builder $query): Builder
    {
        return $query
            ->whereNotNull('equivalencias_id')
            ->where('tipo', self::TIPO_CURSADA);
    }

    public function isUsp(): bool
    {
        return $this->equivalencias_id === null
            && in_array($this->tipo, [self::TIPO_AUTOMATICA, self::TIPO_REQUERIDA], true);
    }

    public function isEquivalencia(): bool
    {
        return $this->equivalencias_id !== null && $this->tipo === self::TIPO_CURSADA;
    }

    public function permiteEquivalenciaDireta(): bool
    {
        return $this->isUsp() && $this->equivalentes()->exists();
    }
}
