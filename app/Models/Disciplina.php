<?php

namespace App\Models;

use App\Replicado\Graduacao;
use Illuminate\Database\Eloquent\Collection;
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
        return $this->hasMany(Aproveitamento::class, 'requerida_id');
    }

    // Equivalências onde esta disciplina é a cursada
    public function equivalenciasComoCursada()
    {
        return $this->hasMany(Aproveitamento::class, 'cursada_id');
    }

    // Usado pela tela de show para listar apenas as cursadas equivalentes (sem a linha placeholder do grupo).
    public function equivalentes()
    {
        return $this->hasMany(Aproveitamento::class, 'requerida_id')
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

    /**
     * Lista disciplinas requeridas com equivalências automáticas no contexto informado.
     *
     * @param  int  $codcur  Código do curso
     * @param  int  $codhab  Código da habilitação
     */
    public static function listarDisciplinasComEquivalencias(int $codcur, int $codhab): Collection
    {
        $disciplinas = static::query()
            ->whereHas('equivalenciasComoRequerida', function ($query) use ($codcur, $codhab) {
                $query->automaticas()->doContexto($codcur, $codhab);
            })
            ->with(['equivalentes' => function ($query) use ($codcur, $codhab) {
                $query->automaticas()->doContexto($codcur, $codhab)->with('cursada')->orderBy('id');
            }])
            ->orderBy('coddis')
            ->get();

        return $disciplinas->transform(function (Disciplina $disciplina) {
            $disciplina->setRelation(
                'equivalentes',
                $disciplina->equivalentes
                    ->sortBy(function (Aproveitamento $item) {
                        return sprintf('%010d-%s', (int) $item->grupo, (string) ($item->coddis ?? ''));
                    })
                    ->values()
            );

            return $disciplina;
        });
    }

    /**
     * Monta os dados de uma disciplina requerida consultando o Replicado quando possível.
     */
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

    /**
     * Cria ou atualiza uma disciplina requerida pelo código da disciplina.
     */
    public static function upsertRequeridaPorCoddis(string $coddis, ?Disciplina $disciplina = null): Disciplina
    {
        $dados = static::dadosDaRequeridaPorCoddis($coddis);

        if ($disciplina) {
            $disciplina->update($dados);

            return $disciplina;
        }

        return static::create($dados);
    }

    /**
     * Garante que a disciplina requerida exista e tenha placeholder no contexto automático.
     */
    public static function garantirRequeridaAutomaticaNoContexto(
        string $coddis,
        int $codcur,
        int $codhab,
        ?Disciplina $disciplina = null
    ): Disciplina {
        $requerida = static::upsertRequeridaPorCoddis($coddis, $disciplina);

        if (! Aproveitamento::grupoDaRequerida($requerida->id, $codcur, $codhab)) {
            Aproveitamento::criarPlaceholderDaRequerida($requerida->id, $codcur, $codhab);
        }

        return $requerida;
    }

    /**
     * Normaliza os dados da disciplina cursada enviados pelo formulário.
     */
    public static function dadosDaCursadaPorFormulario(array $dados): array
    {
        $coddis = isset($dados['coddis']) ? trim((string) $dados['coddis']) : null;
        $isUsp = filter_var($dados['is_usp'] ?? false, FILTER_VALIDATE_BOOLEAN);
        // somente tenta buscar no Replicado se for USP e tiver código de disciplina
        $disciplinaReplicado = ($isUsp && $coddis) ? static::buscarNoReplicado($coddis) : null;

        if ($isUsp && $disciplinaReplicado) {
            return [
                'coddis' => $disciplinaReplicado['coddis'] ?? $coddis,
                'nomdis' => $disciplinaReplicado['nomdis'] ?? null,
                'ies' => 'USP',
                'creditos' => $disciplinaReplicado['creaul'] ?? null,
                'carga_horaria' => $disciplinaReplicado['numhor'] ?? null,
                'verdis' => $disciplinaReplicado['verdis'] ?? null,
                // precisa ver como vai recuperar isso do replicado
                // do modo que está, so funciona na importação de disciplinas pelo script
                'sglund' =>  $dados['sglund'] ?? null,
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

    /**
     * Busca uma disciplina USP no Replicado pelo código informado.
     */
    public static function disciplinaUspNoReplicado(?string $coddis): ?array
    {
        $codigo = $coddis ? trim($coddis) : '';

        if ($codigo === '') {
            return null;
        }

        return static::buscarNoReplicado($codigo);
    }

    /**
     * Cria uma disciplina cursada a partir dos dados normalizados do formulário.
     */
    public static function criarCursadaPorFormulario(array $dados): Disciplina
    {
        return static::create(static::dadosDaCursadaPorFormulario($dados));
    }

    /**
     * Atualiza esta disciplina cursada com dados normalizados do formulário.
     */
    public function atualizarCursadaPorFormulario(array $dados): void
    {
        $this->update(static::dadosDaCursadaPorFormulario($dados));
    }

    /**
     * Verifica se a disciplina está vinculada como requerida ao contexto informado.
     */
    public function pertenceComoRequeridaAoContexto(int $codcur, int $codhab): bool
    {
        return $this->equivalenciasComoRequerida()
            ->doContexto($codcur, $codhab)
            ->exists();
    }

    /**
     * Remove a disciplina quando ela não possui mais vínculos como requerida ou cursada.
     */
    public function removerSeOrfa(): void
    {
        $temVinculoComoRequerida = $this->equivalenciasComoRequerida()->exists();
        $temVinculoComoCursada = $this->equivalenciasComoCursada()->exists();

        if (! $temVinculoComoRequerida && ! $temVinculoComoCursada) {
            $this->delete();
        }
    }

    /**
     * Remove a disciplina pelo ID se ela estiver órfã.
     */
    public static function removerSeOrfaPorId(int $disciplinaId): void
    {
        static::find($disciplinaId)?->removerSeOrfa();
    }

    /**
     * Monta o estado da interface para o formulário de equivalências filhas.
     */
    public static function estadoFormularioEquivalencia(array $values = [], int $maxDisciplinas = 3): array
    {
        $fieldSuffixes = ['', '2', '3'];

        $fieldValue = function (string $field) use ($values) {
            return old($field, $values[$field] ?? null);
        };

        $isUspValue = function (string $suffix) use ($fieldValue) {
            $field = 'is_usp' . $suffix;
            $old = old($field);

            if ($old !== null) {
                return (bool) $old;
            }

            return $fieldValue('ies' . $suffix) === 'USP';
        };

        $hasAnyValue = function (string $suffix) use ($fieldValue, $isUspValue) {
            return $isUspValue($suffix) ||
                filled($fieldValue('coddis' . $suffix)) ||
                filled($fieldValue('nome_disciplina' . $suffix)) ||
                filled($fieldValue('ies' . $suffix));
        };

        $initialVisible = 1;
        foreach (['2', '3'] as $suffix) {
            if ($hasAnyValue($suffix)) {
                $initialVisible = (int) $suffix;
            }
        }

        $blocks = [];
        foreach ($fieldSuffixes as $loopIndex => $suffix) {
            $number = $loopIndex + 1;

            $blocks[] = [
                'number' => $number,
                'suffix' => $suffix,
                'visible' => $number <= $initialVisible,
                'isUsp' => $isUspValue($suffix),
                'coddis' => $fieldValue('coddis' . $suffix),
                'nome' => $fieldValue('nome_disciplina' . $suffix),
                'ies' => $fieldValue('ies' . $suffix),
            ];
        }

        return [
            'maxDisciplinas' => $maxDisciplinas,
            'initialVisible' => $initialVisible,
            'blocks' => $blocks,
        ];
    }

    /**
     * Monta os valores padrão da edição de um grupo de equivalências automáticas.
     */
    public function defaultsParaFormularioEdicaoDeGrupo(Aproveitamento $equivalenciaFilha): array
    {
        $equivalentesDoMesmoGrupo = $this->equivalentes
            ->where('grupo', $equivalenciaFilha->grupo)
            ->sortBy('id')
            ->values();

        $outrosDoGrupo = $equivalentesDoMesmoGrupo
            ->reject(fn (Aproveitamento $item) => $item->id === $equivalenciaFilha->id)
            ->values();

        $equivalencia2 = $outrosDoGrupo->get(0);
        $equivalencia3 = $outrosDoGrupo->get(1);

        return [
            'coddis' => old('coddis', $equivalenciaFilha->coddis),
            'nome_disciplina' => old('nome_disciplina', $equivalenciaFilha->nome_disciplina),
            'ies' => old('ies', $equivalenciaFilha->ies),
            'coddis2' => old('coddis2', $equivalencia2?->coddis),
            'nome_disciplina2' => old('nome_disciplina2', $equivalencia2?->nome_disciplina),
            'ies2' => old('ies2', $equivalencia2?->ies),
            'coddis3' => old('coddis3', $equivalencia3?->coddis),
            'nome_disciplina3' => old('nome_disciplina3', $equivalencia3?->nome_disciplina),
            'ies3' => old('ies3', $equivalencia3?->ies),
        ];
    }

    /**
     * Cria a disciplina requerida informada em um requerimento do usuário.
     */
    public static function criarRequeridaDeRequerimento(array $dados, int $userId): Disciplina
    {
        return static::create([
            'coddis' => $dados['coddis4'],
            'nomdis' => $dados['disciplina4'],
            'ies' => 'USP',
            'criado_por_id' => $userId,
            'alterado_por_id' => $userId,
        ]);
    }

    /**
     * Atualiza a disciplina requerida informada em um requerimento do usuário.
     */
    public function atualizarRequeridaDeRequerimento(array $dados, int $userId): void
    {
        $this->update([
            'coddis' => $dados['coddis4'],
            'nomdis' => $dados['disciplina4'],
            'ies' => 'USP',
            'alterado_por_id' => $userId,
        ]);
    }

    /**
     * Cria uma disciplina cursada de um requerimento pelo índice do formulário.
     */
    public static function criarCursadaDeRequerimento(array $dados, int $numeroDisciplina, int $userId): Disciplina
    {
        return static::create(static::dadosCursadaDeRequerimento($dados, $numeroDisciplina, $userId));
    }

    /**
     * Atualiza uma disciplina cursada de requerimento pelo índice do formulário.
     */
    public function atualizarCursadaDeRequerimento(array $dados, int $numeroDisciplina, int $userId): void
    {
        $dadosCursada = static::dadosCursadaDeRequerimento($dados, $numeroDisciplina, $userId);
        unset($dadosCursada['criado_por_id']);

        $this->update($dadosCursada);
    }

    /**
     * Monta os atributos de uma disciplina cursada enviada em um requerimento.
     */
    private static function dadosCursadaDeRequerimento(array $dados, int $numeroDisciplina, int $userId): array
    {
        return [
            'coddis' => $dados['coddis'.$numeroDisciplina],
            'nomdis' => $dados['disciplina'.$numeroDisciplina],
            'creditos' => $dados['credit_dis'.$numeroDisciplina],
            'carga_horaria' => $dados['cghr_dis'.$numeroDisciplina],
            'ies' => $dados['unidade_ies'],
            'ano' => $dados['ano_dis'.$numeroDisciplina],
            'semestre' => $dados['semestre_dis'.$numeroDisciplina],
            'frequencia' => $dados['freq_dis'.$numeroDisciplina],
            'nota' => $dados['nota_dis'.$numeroDisciplina],
            'criado_por_id' => $userId,
            'alterado_por_id' => $userId,
        ];
    }

    /**
     * Verifica se já existe uma disciplina requerida com o mesmo código
     * que tenha equivalência automática no contexto informado.
     *
     * Em uso no request
     */
    public static function existeComoRequeridaNoContexto(
        string $coddis,
        int $codcur,
        int $codhab
    ): bool {
        return self::query()
            ->where('coddis', $coddis)
            ->whereHas('equivalenciasComoRequerida', function ($query) use ($codcur, $codhab) {
                $query->automaticas()->doContexto($codcur, $codhab);
            })
            ->exists();
    }

    /**
     * Consulta o Replicado e retorna os dados da disciplina correspondente.
     */
    private static function buscarNoReplicado(string $coddis): ?array
    {
        try {
            $disciplinas = Graduacao::obterDisciplinas([$coddis]) ?? [];
        } catch (\Throwable $e) {
            return null;
        }

        if (! is_iterable($disciplinas)) {
            return null;
        }

        foreach ($disciplinas as $disciplina) {
            if (! is_array($disciplina)) {
                continue;
            }

            if (($disciplina['coddis'] ?? null) === $coddis) {
                return $disciplina;
            }
        }

        $first = $disciplinas[0] ?? null;

        return is_array($first) ? $first : null;
    }
}
