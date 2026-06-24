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
        'disciplina_ativa',
        'criado_por_id',
        'alterado_por_id',
    ];

    protected $casts = [
        'verdis' => 'integer',
        'creditos' => 'integer',
        'carga_horaria' => 'integer',
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
            ->where('placeholder_requerida', false);
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
            ->orderBy('verdis')
            ->get();

        return $disciplinas->transform(function (Disciplina $disciplina) {
            $disciplina->setRelation(
                'equivalentes',
                $disciplina->equivalentes
                    ->sortBy(function (Aproveitamento $item) {
                        return sprintf(
                            '%010d-%s-%03d',
                            (int) $item->grupo,
                            (string) ($item->coddis ?? ''),
                            (int) ($item->cursada?->verdis ?? 0)
                        );
                    })
                    ->values()
            );

            return $disciplina;
        });
    }

    /**
     * Monta os dados de uma disciplina requerida consultando o Replicado quando possível.
     */
    public static function dadosDaRequeridaPorCoddis(string $coddis, ?int $verdis = null): array
    {
        $dados = [
            'coddis' => static::normalizarCoddis($coddis),
            'verdis' => static::normalizarVerdis($verdis),
            'ies' => 'USP',
        ];

        $disciplinaReplicado = static::buscarNoReplicado($dados['coddis'], $dados['verdis']);

        if (! $disciplinaReplicado) {
            return $dados;
        }

        $dados['nomdis'] = $disciplinaReplicado['nomdis'] ?? null;
        $dados['verdis'] = $disciplinaReplicado['verdis'] ?? null;
        $dados['creditos'] = static::creditosUsp($disciplinaReplicado);
        $dados['carga_horaria'] = static::cargaHorariaUsp($disciplinaReplicado);
        $dados['sglund'] = $disciplinaReplicado['sglund'] ?? null;
        $dados['ies'] = 'USP';
        $dados['disciplina_ativa'] = static::disciplinaAtivaNoReplicado($disciplinaReplicado);

        return $dados;
    }

    /**
     * Resolve uma disciplina requerida pela identidade coddis + verdis.
     */
    public static function upsertRequeridaPorCoddis(
        string $coddis,
        ?int $verdis = null,
        ?Disciplina $disciplina = null
    ): Disciplina {
        $dados = static::dadosDaRequeridaPorCoddis($coddis, $verdis);

        return static::persistirPorIdentidade($dados, $disciplina);
    }

    /**
     * Cria ou atualiza a disciplina requerida usada em um rascunho de aproveitamento.
     */
    public static function salvarRequeridaDoRascunho(
        string $coddis,
        ?int $verdis,
        int $userId,
        ?Disciplina $disciplina = null
    ): Disciplina {
        $dados = static::dadosDaRequeridaPorCoddis($coddis, $verdis);
        $dados['nomdis'] ??= $coddis;
        $dados['ies'] = 'USP';
        $dados['criado_por_id'] = $userId;
        $dados['alterado_por_id'] = $userId;

        return static::persistirPorIdentidade($dados, $disciplina);
    }

    /**
     * Garante que a disciplina requerida exista e tenha placeholder no contexto automático.
     */
    public static function garantirRequeridaAutomaticaNoContexto(
        string $coddis,
        ?int $verdis,
        int $codcur,
        int $codhab,
        ?Disciplina $disciplina = null
    ): Disciplina {
        $requerida = static::upsertRequeridaPorCoddis($coddis, $verdis, $disciplina);

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
        $coddis = isset($dados['coddis']) ? static::normalizarCoddis((string) $dados['coddis']) : null;
        $verdis = static::normalizarVerdis($dados['verdis'] ?? null);
        $isUsp = filter_var($dados['is_usp'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $base = [
            'coddis' => $coddis,
            'nomdis' => $dados['nome_disciplina'] ?? null,
            'ies' => $dados['ies'] ?? null,
            'creditos' => $dados['creditos'] ?? null,
            'carga_horaria' => $dados['carga_horaria'] ?? null,
            'verdis' => $verdis,
            'sglund' => $dados['sglund'] ?? null,
            'disciplina_ativa' => null,
        ];

        // somente tenta buscar no Replicado se for USP e tiver código de disciplina
        $disciplinaReplicado = ($isUsp && $coddis) ? static::buscarNoReplicado($coddis, $verdis) : null;

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
                'sglund' => $disciplinaReplicado['sglund'] ?? ($base['sglund'] ?? null),
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
            'verdis' => $dados['verdis'] ?? null,
            'nome_disciplina' => $isExternal ? trim($dados['nomdis']) : null,
            'ies' => $isExternal ? trim($dados['unidade_nome']) : 'USP',
            'creditos' => $isExternal ? $dados['creditos'] : null,
            'carga_horaria' => $isExternal ? $dados['carga_horaria'] : null,
        ]);

        if (! $isExternal) {
            $courseData = array_merge(
                $courseData,
                static::dadosUspCursadaDoRascunho(
                    (int) $userId,
                    Str::upper(trim($dados['coddis'])),
                    (string) $dados['codtur'],
                    static::normalizarVerdis($dados['verdis'] ?? null)
                )
            );
        }

        $courseData['criado_por_id'] = $userId;
        $courseData['alterado_por_id'] = $userId;

        return $courseData;
    }

    /**
     * Normaliza os dados da ocorrência cursada que pertencem ao vínculo do requerimento.
     */
    public static function dadosDaOcorrenciaDoRascunho(array $dados, int $userId): array
    {
        $isExternal = $dados['unidade_tipo'] === 'OUTRA';
        $base = [
            'ano' => (int) $dados['ano'],
            'semestre' => (int) $dados['semestre'],
            'codtur' => (string) $dados['codtur'],
            'frequencia' => $isExternal ? $dados['frequencia'] : null,
            'nota' => $isExternal ? $dados['nota'] : null,
        ];

        if ($isExternal) {
            return $base;
        }

        $historico = static::historicoUspCursadaDoRascunho(
            (int) $userId,
            Str::upper(trim($dados['coddis'])),
            (string) $dados['codtur'],
            static::normalizarVerdis($dados['verdis'] ?? null)
        );

        if (! $historico) {
            return $base;
        }

        return array_merge($base, [
            'frequencia' => $historico['frqfim'] ?? null,
            'nota' => $historico['notfim2'] ?? $historico['notfim'] ?? null,
        ]);
    }

    /**
     * Busca uma disciplina USP no Replicado pelo código informado.
     */
    public static function disciplinaUspNoReplicado(?string $coddis, ?int $verdis = null): ?array
    {
        $codigo = $coddis ? trim($coddis) : '';

        if ($codigo === '') {
            return null;
        }

        return static::buscarNoReplicado($codigo, static::normalizarVerdis($verdis));
    }

    /**
     * Cria uma disciplina cursada a partir dos dados normalizados do formulário.
     */
    public static function criarCursadaPorFormulario(array $dados): Disciplina
    {
        return static::salvarCursadaPorFormulario($dados);
    }

    /**
     * Resolve uma disciplina cursada a partir dos dados normalizados do formulário.
     */
    public static function salvarCursadaPorFormulario(array $dados, ?Disciplina $disciplina = null): Disciplina
    {
        return static::persistirPorIdentidade(static::dadosDaCursadaPorFormulario($dados), $disciplina);
    }

    /**
     * Cria uma disciplina cursada a partir dos dados validados do rascunho.
     */
    public static function criarCursadaDoRascunho(array $dados, int $userId): Disciplina
    {
        return static::salvarCursadaDoRascunho($dados, $userId);
    }

    /**
     * Resolve uma disciplina cursada a partir dos dados validados do rascunho.
     */
    public static function salvarCursadaDoRascunho(
        array $dados,
        int $userId,
        ?Disciplina $disciplina = null
    ): Disciplina {
        return static::persistirPorIdentidade(static::dadosDaCursadaDoRascunho($dados, $userId), $disciplina);
    }

    /**
     * Atualiza esta disciplina cursada com dados normalizados do formulário.
     */
    public function atualizarCursadaPorFormulario(array $dados): Disciplina
    {
        return static::salvarCursadaPorFormulario($dados, $this);
    }

    /**
     * Atualiza esta cursada a partir dos dados validados do rascunho.
     */
    public function atualizarCursadaDoRascunho(array $dados, int $userId): Disciplina
    {
        return static::salvarCursadaDoRascunho($dados, $userId, $this);
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
                filled($fieldValue('verdis' . $suffix)) ||
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
                'verdis' => $fieldValue('verdis' . $suffix),
                'nome' => $fieldValue('nome_disciplina' . $suffix),
                'ies' => $fieldValue('ies' . $suffix),
            ];
        }

        return [
            'maxDisciplinas' => $maxDisciplinas,
            'initialVisible' => $initialVisible,
            'administrative' => [
                'numero_reuniao' => $fieldValue('numero_reuniao'),
                'data_reuniao' => $fieldValue('data_reuniao'),
                'observacoes' => $fieldValue('observacoes'),
            ],
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
            'verdis' => old('verdis', $equivalenciaFilha->cursada?->verdis),
            'nome_disciplina' => old('nome_disciplina', $equivalenciaFilha->nome_disciplina),
            'ies' => old('ies', $equivalenciaFilha->ies),
            'numero_reuniao' => old('numero_reuniao', $equivalenciaFilha->numero_reuniao),
            'data_reuniao' => old('data_reuniao', $equivalenciaFilha->data_reuniao?->format('Y-m-d')),
            'observacoes' => old('observacoes', $equivalenciaFilha->observacoes),
            'coddis2' => old('coddis2', $equivalencia2?->coddis),
            'verdis2' => old('verdis2', $equivalencia2?->cursada?->verdis),
            'nome_disciplina2' => old('nome_disciplina2', $equivalencia2?->nome_disciplina),
            'ies2' => old('ies2', $equivalencia2?->ies),
            'coddis3' => old('coddis3', $equivalencia3?->coddis),
            'verdis3' => old('verdis3', $equivalencia3?->cursada?->verdis),
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
        ?int $verdis,
        int $codcur,
        int $codhab,
        ?int $ignorarDisciplinaId = null
    ): bool {
        $query = self::query()
            ->where('coddis', static::normalizarCoddis($coddis))
            ->where('verdis', static::normalizarVerdis($verdis))
            ->where('ies', 'USP')
            ->whereHas('equivalenciasComoRequerida', function ($query) use ($codcur, $codhab) {
                $query->automaticas()->doContexto($codcur, $codhab);
            });

        if ($ignorarDisciplinaId !== null) {
            $query->where('id', '!=', $ignorarDisciplinaId);
        }

        return $query->exists();
    }

    /**
     * Consulta o Replicado e retorna os dados da disciplina correspondente.
     */
    private static function buscarNoReplicado(string $coddis, ?int $verdis = null): ?array
    {
        try {
            $disciplinas = app(Graduacao::class)->obterDadosDisciplinaPorCodigoVersao($coddis, $verdis);
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
    private static function dadosUspCursadaDoRascunho(
        int $userId,
        string $coddis,
        string $codtur,
        ?int $verdis = null
    ): array {
        $historico = static::historicoUspCursadaDoRascunho($userId, $coddis, $codtur, $verdis);

        if (! $historico) {
            return [];
        }

        $disciplina = app(Graduacao::class)->obterDadosDisciplinaPorCodigoVersao(
            $coddis,
            static::normalizarVerdis($historico['verdis'] ?? $verdis)
        );

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
            'disciplina_ativa' => static::disciplinaAtivaNoReplicado($dadosReplicado),
        ];
    }

    /**
     * Obtém do histórico USP os dados de uma disciplina cursada pelo aluno,
     * usando as informações presentes no rascunho.
     *
     * Busca o `codpes` do usuário informado e consulta o serviço de Graduação
     * pelo código da disciplina, código da turma e, opcionalmente, versão da
     * disciplina.
     *
     * @param int $userId ID do usuário no sistema.
     * @param string $coddis Código da disciplina.
     * @param string $codtur Código da turma/período.
     * @param int|null $verdis Versão da disciplina, quando disponível.
     *
     * @return array Dados da disciplina cursada retornados pelo serviço de Graduação.
     */
    private static function historicoUspCursadaDoRascunho(
        int $userId,
        string $coddis,
        string $codtur,
        ?int $verdis = null
    ): array {
        $codpes = (int) (User::query()->whereKey($userId)->value('codpes') ?? 0);

        return app(Graduacao::class)->obterDisciplinaCursadaPorAlunoEmPeriodoCodtur(
            $codpes,
            $coddis,
            $codtur,
            $verdis
        );
    }

    /**
     * Persiste uma disciplina respeitando sua identidade institucional.
     *
     * Caso uma disciplina seja informada e possua a mesma identidade dos dados
     * recebidos, ela é atualizada diretamente. Caso contrário, tenta localizar
     * uma disciplina existente pela identidade. Se encontrar, atualiza; se não,
     * cria um novo registro.
     *
     * @param array $dados Dados da disciplina a serem persistidos.
     * @param Disciplina|null $disciplina Disciplina já existente, quando houver.
     *
     * @return Disciplina Disciplina criada ou atualizada.
     */
    private static function persistirPorIdentidade(array $dados, ?Disciplina $disciplina = null): Disciplina
    {
        if ($disciplina && static::temMesmaIdentidade($disciplina, $dados)) {
            $disciplina->update($dados);

            return $disciplina;
        }

        $existente = static::buscarPorIdentidade($dados);

        if ($existente) {
            $existente->update($dados);

            return $existente;
        }

        return static::create($dados);
    }

    /**
     * Busca uma disciplina USP existente pela sua identidade.
     *
     * A identidade de uma disciplina USP é composta por instituição, código da
     * disciplina e versão da disciplina. Para disciplinas que não sejam USP ou
     * que não possuam os dados mínimos necessários, nenhum registro é buscado.
     *
     * @param array $dados Dados usados para identificar a disciplina.
     *
     * @return Disciplina|null Disciplina encontrada ou null quando não houver correspondência.
     */
    private static function buscarPorIdentidade(array $dados): ?Disciplina
    {
        if (($dados['ies'] ?? null) !== 'USP' || empty($dados['coddis']) || empty($dados['verdis'])) {
            return null;
        }

        return static::query()
            ->where('ies', 'USP')
            ->where('coddis', static::normalizarCoddis((string) $dados['coddis']))
            ->where('verdis', static::normalizarVerdis($dados['verdis']))
            ->first();
    }

    /**
     * Verifica se uma disciplina possui a mesma identidade dos dados informados.
     *
     * Para disciplinas USP, compara instituição, código da disciplina normalizado
     * e versão da disciplina normalizada. Para disciplinas externas, considera
     * compatível quando a disciplina atual também não pertence à USP.
     *
     * @param Disciplina $disciplina Disciplina a ser comparada.
     * @param array $dados Dados usados na comparação de identidade.
     *
     * @return bool True quando a identidade for a mesma; caso contrário, false.
     */
    private static function temMesmaIdentidade(Disciplina $disciplina, array $dados): bool
    {
        if (($dados['ies'] ?? null) !== 'USP') {
            return ($disciplina->ies ?? null) !== 'USP';
        }

        return $disciplina->ies === 'USP'
            && $disciplina->coddis === static::normalizarCoddis((string) ($dados['coddis'] ?? ''))
            && (int) $disciplina->verdis === (int) static::normalizarVerdis($dados['verdis'] ?? null);
    }

    /**
     * Normaliza o código da disciplina.
     *
     * Remove espaços em branco das extremidades e converte o código para letras
     * maiúsculas.
     *
     * @param string $coddis Código da disciplina.
     *
     * @return string Código da disciplina normalizado.
     */
    private static function normalizarCoddis(string $coddis): string
    {
        return Str::upper(trim($coddis));
    }

    /**
     * Normaliza a versão da disciplina.
     *
     * Valores nulos ou vazios são convertidos para null. Demais valores são
     * convertidos para inteiro.
     *
     * @param mixed $verdis Versão da disciplina.
     *
     * @return int|null Versão normalizada ou null quando não informada.
     */
    private static function normalizarVerdis(mixed $verdis): ?int
    {
        if ($verdis === null || $verdis === '') {
            return null;
        }

        return (int) $verdis;
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
