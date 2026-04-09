<?php

namespace App\Models;

use App\Replicado\Graduacao;
use Illuminate\Database\Eloquent\Model;

class Disciplina extends Model
{
    protected $table = 'disciplinas';

    protected $fillable = [
        'verdis',
        'coddis',
        'nomdis',
        'creditos',
        'carga_horaria',
        'ies',
        'sglund',
        'ano',
        'semestre',
        'frequencia',
        'nota',
        'criado_por_id',
        'alterado_por_id',
    ];

    protected $casts = [
        'verdis' => 'integer',
        'creditos' => 'integer',
        'carga_horaria' => 'integer',
        'ano' => 'integer',
        'semestre' => 'integer',
        'frequencia' => 'decimal:2',
        'nota' => 'decimal:2',
    ];

    // ── Relacionamentos ─────────────────────────────────────────────

    // Equivalências onde esta disciplina é a requerida
    public function equivalenciasComoRequerida()
    {
        return $this->hasMany(Equivalencia::class, 'requerida_id');
    }

    // Equivalências onde esta disciplina é a cursada
    public function equivalenciasComoCursada()
    {
        return $this->hasMany(Equivalencia::class, 'cursada_id');
    }

    // Usado pela tela de show para listar apenas as cursadas equivalentes (sem a linha placeholder do grupo).
    public function equivalentes()
    {
        return $this->hasMany(Equivalencia::class, 'requerida_id')
            ->whereColumn('cursada_id', '!=', 'requerida_id');
    }

    public function criadoPor()
    {
        return $this->belongsTo(User::class, 'criado_por_id');
    }

    public function alteradoPor()
    {
        return $this->belongsTo(User::class, 'alterado_por_id');
    }

    // Compatibilidade com as views atuais.
    public function getNomeDisciplinaAttribute(): ?string
    {
        return $this->nomdis;
    }

    public static function dadosDaRequeridaPorCoddis(string $coddis): array
    {
        $dados = [
            'coddis' => $coddis,
        ];

        $disciplinaReplicado = static::buscarNoReplicado($coddis);

        if (! $disciplinaReplicado) {
            return $dados;
        }

        $dados['nomdis'] = $disciplinaReplicado['nomdis'] ?? null;
        $dados['verdis'] = $disciplinaReplicado['verdis'] ?? null;
        $dados['creditos'] = $disciplinaReplicado['creaul'] ?? null;
        $dados['carga_horaria'] = $disciplinaReplicado['numhor'] ?? null;
        $dados['sglund'] = $disciplinaReplicado['sglund'] ?? null;
        $dados['ies'] = 'USP';

        return $dados;
    }

    public static function upsertRequeridaPorCoddis(string $coddis, ?Disciplina $disciplina = null): Disciplina
    {
        $dados = static::dadosDaRequeridaPorCoddis($coddis);

        if ($disciplina) {
            $disciplina->update($dados);

            return $disciplina;
        }

        return static::create($dados);
    }

    public static function dadosDaCursadaPorFormulario(array $dados): array
    {
        $coddis = isset($dados['coddis']) ? trim((string) $dados['coddis']) : null;
        $disciplinaReplicado = $coddis ? static::buscarNoReplicado($coddis) : null;

        if ($disciplinaReplicado) {
            return [
                'coddis' => $disciplinaReplicado['coddis'] ?? $coddis,
                'nomdis' => $disciplinaReplicado['nomdis'] ?? null,
                'ies' => 'USP',
                'creditos' => $disciplinaReplicado['creaul'] ?? null,
                'carga_horaria' => $disciplinaReplicado['numhor'] ?? null,
                'verdis' => $disciplinaReplicado['verdis'] ?? null,
                'sglund' => $disciplinaReplicado['sglund'] ?? null,
                'ano' => $dados['ano'] ?? null,
                'semestre' => $dados['semestre'] ?? null,
                'frequencia' => $dados['frequencia'] ?? null,
                'nota' => $dados['nota'] ?? null,
            ];
        }

        return [
            'coddis' => $coddis,
            'nomdis' => $dados['nome_disciplina'] ?? null,
            'ies' => $dados['ies'] ?? null,
            'creditos' => $dados['creditos'] ?? null,
            'carga_horaria' => $dados['carga_horaria'] ?? null,
            'verdis' => $dados['verdis'] ?? null,
            'ano' => $dados['ano'] ?? null,
            'semestre' => $dados['semestre'] ?? null,
            'frequencia' => $dados['frequencia'] ?? null,
            'nota' => $dados['nota'] ?? null,
        ];
    }

    public static function disciplinaUspNoReplicado(?string $coddis): ?array
    {
        $codigo = $coddis ? trim($coddis) : '';

        if ($codigo === '') {
            return null;
        }

        return static::buscarNoReplicado($codigo);
    }

    public static function criarCursadaPorFormulario(array $dados): Disciplina
    {
        return static::create(static::dadosDaCursadaPorFormulario($dados));
    }

    public function atualizarCursadaPorFormulario(array $dados): void
    {
        $this->update(static::dadosDaCursadaPorFormulario($dados));
    }

    private static function buscarNoReplicado(string $coddis): ?array
    {
        try {
            $disciplinas = Graduacao::obterDisciplinas([$coddis]) ?? [];
        } catch (\Throwable $e) {
            return null;
        }

        foreach ($disciplinas as $disciplina) {
            if (($disciplina['coddis'] ?? null) === $coddis) {
                return $disciplina;
            }
        }

        return $disciplinas[0] ?? null;
    }
}
