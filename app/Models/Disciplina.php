<?php

namespace App\Models;

use App\Replicado\Graduacao;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'codtur',
        'frequencia',
        'nota',
        'programa',
        'programa_resumo',
        'objetivo',
        'disciplina_ativa',
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
        'disciplina_ativa' => 'boolean',
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
        $dados['creditos'] = static::creditosUsp($disciplinaReplicado);
        $dados['carga_horaria'] = static::cargaHorariaUsp($disciplinaReplicado);
        $dados['sglund'] = $disciplinaReplicado['sglund'] ?? null;
        $dados['ies'] = 'USP';
        $dados['programa'] = $disciplinaReplicado['pgmdis'] ?? null;
        $dados['programa_resumo'] = $disciplinaReplicado['pgmrsudis'] ?? null;
        $dados['objetivo'] = $disciplinaReplicado['objdis'] ?? null;
        $dados['disciplina_ativa'] = static::disciplinaAtivaNoReplicado($disciplinaReplicado);

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
     * Cria ou atualiza a disciplina requerida usada em um rascunho de aproveitamento.
     */
    public static function salvarRequeridaDoRascunho(
        string $coddis,
        int $userId,
        ?Disciplina $disciplina = null
    ): Disciplina {
        $dados = static::dadosDaRequeridaPorCoddis($coddis);
        $dados['nomdis'] ??= $coddis;
        $dados['ies'] = 'USP';
        $dados['criado_por_id'] = $userId;
        $dados['alterado_por_id'] = $userId;

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
        $base = [
            'coddis' => $coddis,
            'nomdis' => $dados['nome_disciplina'] ?? null,
            'ies' => $dados['ies'] ?? null,
            'creditos' => $dados['creditos'] ?? null,
            'carga_horaria' => $dados['carga_horaria'] ?? null,
            'verdis' => $dados['verdis'] ?? null,
            'sglund' => $dados['sglund'] ?? null,
            'ano' => $dados['ano'] ?? null,
            'semestre' => $dados['semestre'] ?? null,
            'codtur' => $dados['codtur'] ?? null,
            'frequencia' => $dados['frequencia'] ?? null,
            'nota' => $dados['nota'] ?? null,
            'programa' => null,
            'programa_resumo' => null,
            'objetivo' => null,
            'disciplina_ativa' => null,
        ];

        // somente tenta buscar no Replicado se for USP e tiver código de disciplina
        $disciplinaReplicado = ($isUsp && $coddis) ? static::buscarNoReplicado($coddis) : null;

        if (! $isUsp || ! $disciplinaReplicado) {
            return $base;
        }

        return array_merge(
            $base,
            [
                'coddis' => $disciplinaReplicado['coddis'] ?? $coddis,
                'nomdis' => $disciplinaReplicado['nomdis'] ?? null,
                'ies' => 'USP',
                'creditos' => static::creditosUsp($disciplinaReplicado),
                'carga_horaria' => static::cargaHorariaUsp($disciplinaReplicado),
                'verdis' => $disciplinaReplicado['verdis'] ?? null,
                'programa' => $disciplinaReplicado['pgmdis'] ?? null,
                'programa_resumo' => $disciplinaReplicado['pgmrsudis'] ?? null,
                'objetivo' => $disciplinaReplicado['objdis'] ?? null,
                'disciplina_ativa' => static::disciplinaAtivaNoReplicado($disciplinaReplicado),
            ]
        );
    }

    /**
     * Normaliza os dados validados da cursada no rascunho de aproveitamento.
     */
    public static function dadosDaCursadaDoRascunho(array $dados, int $userId): array
    {
        $isExternal = $dados['unidade_tipo'] === 'OUTRA';
        $courseData = static::dadosDaCursadaPorFormulario([
            'is_usp' => ! $isExternal,
            'coddis' => Str::upper(trim($dados['coddis'])),
            'nome_disciplina' => $isExternal ? trim($dados['nomdis']) : null,
            'ies' => $isExternal ? trim($dados['unidade_nome']) : 'USP',
            'ano' => $dados['ano'],
            'semestre' => $dados['semestre'],
            'codtur' => $dados['codtur'],
            'frequencia' => $isExternal ? $dados['frequencia'] : null,
            'nota' => $isExternal ? $dados['nota'] : null,
            'creditos' => $isExternal ? $dados['creditos'] : null,
            'carga_horaria' => $isExternal ? $dados['carga_horaria'] : null,
        ]);

        if (! $isExternal) {
            $courseData = array_merge(
                $courseData,
                static::dadosUspCursadaDoRascunho(
                    (int) $userId,
                    Str::upper(trim($dados['coddis'])),
                    (string) $dados['codtur']
                )
            );
        }

        $courseData['criado_por_id'] = $userId;
        $courseData['alterado_por_id'] = $userId;

        return $courseData;
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
     * Cria uma disciplina cursada a partir dos dados validados do rascunho.
     */
    public static function criarCursadaDoRascunho(array $dados, int $userId): Disciplina
    {
        return static::create(static::dadosDaCursadaDoRascunho($dados, $userId));
    }

    /**
     * Atualiza esta disciplina cursada com dados normalizados do formulário.
     */
    public function atualizarCursadaPorFormulario(array $dados): void
    {
        $this->update(static::dadosDaCursadaPorFormulario($dados));
    }

    /**
     * Atualiza esta cursada a partir dos dados validados do rascunho.
     */
    public function atualizarCursadaDoRascunho(array $dados, int $userId): void
    {
        $this->update(static::dadosDaCursadaDoRascunho($dados, $userId));
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
     *
     * O formulário suporta até 3 disciplinas cursadas por grupo de equivalência, e esse método monta os dados
     * para preencher os campos e controlar a visibilidade dos blocos de acordo com os valores
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
            // se não tiver valor antigo, considera como USP se a IES for USP
            //(com base no valor atual do campo, que pode vir do banco(edição) ou do formulário)
            $ies = $fieldValue('ies' . $suffix);

            if (filled($ies)) {
                return $ies === 'USP';
            }

            return true;
        };

        $hasAnyValue = function (string $suffix) use ($fieldValue) {
            // considera que o bloco tem valor se tiver código, nome ou IES preenchidos,
            // para facilitar a UX de mostrar o bloco quando o usuário começar a preencher
            return filled($fieldValue('coddis' . $suffix)) ||
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
            ->reject(fn(Aproveitamento $item) => $item->id === $equivalenciaFilha->id)
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
            $disciplinas = app(Graduacao::class)->obterDadosDisciplinaPorCodigo($coddis);
        } catch (\Throwable $e) {
            return null;
        }

        return ! empty($disciplinas) ? $disciplinas : null;
    }

    /**
     * Monta os dados locais de uma disciplina USP cursada salva como rascunho.
     * Usa o histórico do aluno como fonte obrigatória e combina com os dados cadastrais da disciplina quando disponíveis.
     * Retorna array vazio quando o usuário não tem codpes ou não há histórico compatível no Replicado.
     */
    private static function dadosUspCursadaDoRascunho(int $userId, string $coddis, string $codtur): array
    {
        $codpes = (int) (User::query()->whereKey($userId)->value('codpes') ?? 0);
        $historico = app(Graduacao::class)->obterDisciplinaCursadaPorAlunoEmPeriodoCodtur(
            $codpes,
            $coddis,
            $codtur
        );

        if (! $historico) {
            return [];
        }

        $disciplina = app(Graduacao::class)->obterDadosDisciplinaPorCodigo($coddis);

        $dadosReplicado = array_merge(
            is_array($disciplina) ? $disciplina : [],
            $historico
        );

        return [
            'coddis' => $dadosReplicado['coddis'] ?? $coddis,
            'nomdis' => $dadosReplicado['nomdis'] ?? null,
            'ies' => 'USP',
            'creditos' => static::creditosUsp($dadosReplicado),
            'carga_horaria' => static::cargaHorariaUsp($dadosReplicado),
            'verdis' => $dadosReplicado['verdis'] ?? null,
            'sglund' => $dadosReplicado['sglund'] ?? null,
            'ano' => (int) substr($codtur, 0, 4),
            'semestre' => (int) substr($codtur, 4, 1),
            'codtur' => $codtur,
            'frequencia' => $dadosReplicado['frqfim'] ?? null,
            'nota' => $dadosReplicado['notfim2'] ?? $dadosReplicado['notfim'] ?? null,
            'programa' => $dadosReplicado['pgmdis'] ?? null,
            'programa_resumo' => $dadosReplicado['pgmrsudis'] ?? null,
            'objetivo' => $dadosReplicado['objdis'] ?? null,
            'disciplina_ativa' => static::disciplinaAtivaNoReplicado($dadosReplicado),
        ];
    }

    /**
     * Calcula os créditos USP persistidos localmente a partir dos créditos aula e trabalho.
     * Retorna null quando o Replicado não fornece nenhum dos dois componentes.
     */
    private static function creditosUsp(array $dados): ?int
    {
        $creaul = $dados['creaul'] ?? null;
        $cretrb = $dados['cretrb'] ?? null;

        if ($creaul === null && $cretrb === null) {
            return null;
        }

        return (int) $creaul + (int) $cretrb;
    }

    /**
     * Calcula a carga horária USP usando o campo explícito do Replicado quando existir.
     * Na ausência dele, aplica 15 horas por crédito aula e 30 horas por crédito trabalho.
     * Retorna null quando não há campo explícito nem créditos suficientes para calcular.
     */
    private static function cargaHorariaUsp(array $dados): ?int
    {
        if (isset($dados['numhor'])) {
            return (int) $dados['numhor'];
        }

        $creaul = $dados['creaul'] ?? null;
        $cretrb = $dados['cretrb'] ?? null;

        if ($creaul === null && $cretrb === null) {
            return null;
        }

        return ((int) $creaul * 15) + ((int) $cretrb * 30);
    }

    /**
     * Deriva a situação ativa da disciplina pelas datas de ativação e desativação.
     * Retorna null quando esses campos não vierem no payload do Replicado.
     */
    private static function disciplinaAtivaNoReplicado(array $dados): ?bool
    {
        if (! array_key_exists('dtaatvdis', $dados) && ! array_key_exists('dtadtvdis', $dados)) {
            return null;
        }

        return ! empty($dados['dtaatvdis']) && empty($dados['dtadtvdis']);
    }
}
