<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Equivalencia extends Model
{
    public const TIPO_AUTOMATICA = 'a';

    public const TIPO_REQUERIDA = 'r';

    protected $table = 'equivalencias';

    protected $fillable = [
        'grupo',
        'requerida_id',
        'cursada_id',
        'tipo',
        'codcur',
        'codhab',
        'criado_por_id',
        'alterado_por_id',
    ];

    protected $attributes = [
        'tipo' => self::TIPO_REQUERIDA,
    ];

    protected $casts = [
        'grupo' => 'integer',
        'requerida_id' => 'integer',
        'cursada_id' => 'integer',
        'codcur' => 'integer',
        'codhab' => 'integer',
    ];

    public function requerida()
    {
        return $this->belongsTo(Disciplina::class, 'requerida_id');
    }

    public function cursada()
    {
        return $this->belongsTo(Disciplina::class, 'cursada_id');
    }

    public function arquivos()
    {
        return $this->hasMany(Arquivo::class, 'equivalencia_id');
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'criado_por_id');
    }

    public function alteradoPor()
    {
        return $this->belongsTo(User::class, 'alterado_por_id');
    }

    public function scopeAutomaticas(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_AUTOMATICA);
    }

    public function scopeSolicitacoes(Builder $query): Builder
    {
        return $query->where('tipo', self::TIPO_REQUERIDA);
    }

    public function scopeDoGrupo(Builder $query, int $grupo): Builder
    {
        return $query->where('grupo', $grupo);
    }

    public function scopeDoContexto(Builder $query, int $codcur, int $codhab): Builder
    {
        return $query
            ->where('codcur', $codcur)
            ->where('codhab', $codhab);
    }

    public function isAutomatica(): bool
    {
        return $this->tipo === self::TIPO_AUTOMATICA;
    }

    public function isSolicitacao(): bool
    {
        return $this->tipo === self::TIPO_REQUERIDA;
    }

    public function isPlaceholderRequerida(): bool
    {
        return (int) $this->requerida_id === (int) $this->cursada_id;
    }

    // Compatibilidade com as views atuais que leem campos da cursada no vínculo.
    public function getCoddisAttribute(): ?string
    {
        return $this->cursada?->coddis;
    }

    public function getNomeDisciplinaAttribute(): ?string
    {
        return $this->cursada?->nomdis;
    }

    public function getIesAttribute(): ?string
    {
        return $this->cursada?->ies;
    }

    public static function proximoGrupo(): int
    {
        return ((int) static::max('grupo')) + 1;
    }

    public static function primeiroVinculoDoGrupoDaRequerida(int $requeridaId, int $codcur, int $codhab): ?self
    {
        return static::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $requeridaId)
            ->orderBy('id')
            ->first();
    }

    public static function grupoDaRequerida(int $requeridaId, int $codcur, int $codhab): ?int
    {
        return static::primeiroVinculoDoGrupoDaRequerida($requeridaId, $codcur, $codhab)?->grupo;
    }

    // Vínculos placeholder são equivalências automáticas em que a disciplina
    // cursada é a mesma da requerida.
    //
    // Esses registros são criados automaticamente para garantir que toda
    // disciplina requerida pertença a um grupo de equivalências, mesmo
    // quando ainda não houver uma disciplina cursada vinculada.
    //
    // Isso facilita a manutenção e a inclusão futura de novas equivalências
    // dentro do mesmo grupo.
    public static function criarPlaceholderDaRequerida(int $requeridaId, int $codcur, int $codhab): self
    {
        return static::create([
            'grupo' => static::proximoGrupo(),
            'requerida_id' => $requeridaId,
            'cursada_id' => $requeridaId,
            'tipo' => static::TIPO_AUTOMATICA,
            'codcur' => $codcur,
            'codhab' => $codhab,
        ]);
    }

    public static function criarVinculoCursada(
        int $grupo,
        int $requeridaId,
        int $cursadaId,
        int $codcur,
        int $codhab,
        string $tipo = self::TIPO_AUTOMATICA
    ): self {
        return static::create([
            'grupo' => $grupo,
            'requerida_id' => $requeridaId,
            'cursada_id' => $cursadaId,
            'tipo' => $tipo,
            'codcur' => $codcur,
            'codhab' => $codhab,
        ]);
    }

    public function pertenceAoContexto(int $codcur, int $codhab): bool
    {
        return (int) $this->codcur === $codcur && (int) $this->codhab === $codhab;
    }

    public function pertenceARequeridaNoContexto(int $requeridaId, int $codcur, int $codhab): bool
    {
        return (int) $this->requerida_id === $requeridaId && $this->pertenceAoContexto($codcur, $codhab);
    }
}
