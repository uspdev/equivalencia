<?php

namespace App\Models;

use App\Enums\EquivalenciaEstado;
use App\Enums\EquivalenciaTipo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Aproveitamento extends Model
{
    public const ESTADO_RASCUNHO = EquivalenciaEstado::RASCUNHO->value;

    public const ESTADO_PROCESSANDO = EquivalenciaEstado::PROCESSANDO->value;

    public const ESTADO_DEFERIDO = EquivalenciaEstado::DEFERIDO->value;

    public const ESTADO_NEGADO = EquivalenciaEstado::NEGADO->value;

    public const TIPO_AUTOMATICA = EquivalenciaTipo::AUTOMATICA->value;

    public const TIPO_REQUERIDA = EquivalenciaTipo::REQUERIDA->value;

    protected $table = 'equivalencias';

    protected $fillable = [
        'grupo',
        'estado',
        'requerida_id',
        'cursada_id',
        'tipo',
        'codcur',
        'codhab',
        'criado_por_id',
        'alterado_por_id',
    ];

    protected $attributes = [
        'tipo' => EquivalenciaTipo::REQUERIDA->value,
    ];

    protected $casts = [
        'grupo' => 'integer',
        'estado' => EquivalenciaEstado::class,
        'requerida_id' => 'integer',
        'cursada_id' => 'integer',
        'tipo' => EquivalenciaTipo::class,
        'codcur' => 'integer',
        'codhab' => 'integer',
        'criado_por_id' => 'integer',
        'alterado_por_id' => 'integer',
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
        return $query->where('tipo', EquivalenciaTipo::AUTOMATICA->value);
    }

    public function scopeRequeridas(Builder $query): Builder
    {
        return $query->where('tipo', EquivalenciaTipo::REQUERIDA->value);
    }

    public function scopeDoUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('criado_por_id', $userId);
    }

    public function scopeDoGrupo(Builder $query, int $grupo): Builder
    {
        return $query->where('grupo', $grupo);
    }

    public function scopeRascunhos(Builder $query): Builder
    {
        return $query->where('estado', EquivalenciaEstado::RASCUNHO->value);
    }

    public function scopeNaoRascunhos(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query
                ->whereNull('estado')
                ->orWhere('estado', '!=', EquivalenciaEstado::RASCUNHO->value);
        });
    }

    public function estadoEnum(): ?EquivalenciaEstado
    {
        $estado = $this->getRawOriginal('estado');

        if ($estado === null || $estado === '') {
            return null;
        }

        return EquivalenciaEstado::tryFrom((string) $estado);
    }

    public function estadoLabel(): ?string
    {
        $estado = $this->getRawOriginal('estado');

        return $this->estadoEnum()?->label() ?: ($estado ?: null);
    }

    public function marcarComoProcessando(): void
    {
        $this->estado = EquivalenciaEstado::PROCESSANDO;
    }

    public function marcarComoDeferido(): void
    {
        $this->estado = EquivalenciaEstado::DEFERIDO;
    }

    public function marcarComoNegado(): void
    {
        $this->estado = EquivalenciaEstado::NEGADO;
    }

    public function atualizarEstado(EquivalenciaEstado $estado, ?int $alteradoPorId = null): void
    {
        $dados = ['estado' => $estado];

        if ($alteradoPorId !== null) {
            $dados['alterado_por_id'] = $alteradoPorId;
        }

        $this->update($dados);
    }

    public function scopeDoContexto(Builder $query, int $codcur, int $codhab): Builder
    {
        return $query
            ->where('codcur', $codcur)
            ->where('codhab', $codhab);
    }

    /**
     * Verifica se o vínculo é um placeholder da requerida.
     */
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

    /**
     * Retorna o próximo número de grupo disponível.
     */
    public static function proximoGrupo(): int
    {
        return ((int) static::max('grupo')) + 1;
    }

    /**
     * Busca o primeiro vínculo do grupo associado à requerida no contexto.
     */
    public static function primeiroVinculoDoGrupoDaRequerida(int $requeridaId, int $codcur, int $codhab): ?self
    {
        return static::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $requeridaId)
            ->orderBy('id')
            ->first();
    }

    /**
     * Retorna o número do grupo da requerida no contexto, quando existir.
     */
    public static function grupoDaRequerida(int $requeridaId, int $codcur, int $codhab): ?int
    {
        return static::primeiroVinculoDoGrupoDaRequerida($requeridaId, $codcur, $codhab)?->grupo;
    }

    /**
     * Cria o placeholder automático que mantém a requerida vinculada a um grupo.
     *  Vínculos placeholder são equivalências automáticas em que a disciplina cursada é a mesma da requerida.
     *   Esses registros são criados automaticamente para garantir que toda
     *  quando ainda não houver uma disciplina cursada vinculada.
     *      [
     *     0 => $vinculoReferencia,
     *     1 => outroVinculoReal,
     *     2 => outroVinculoReal,
     *    ...]
     *
     */
    public static function criarPlaceholderDaRequerida(int $requeridaId, int $codcur, int $codhab): self
    {
        return static::criarVinculo(
            static::proximoGrupo(),
            $requeridaId,
            $requeridaId,
            EquivalenciaTipo::AUTOMATICA,
            codcur: $codcur,
            codhab: $codhab
        );
    }

    /**
     * Cria um vínculo entre a disciplina requerida e uma disciplina cursada.
     */
    public static function criarVinculo(
        int $grupo,
        int $requeridaId,
        int $cursadaId,
        string|EquivalenciaTipo $tipo = EquivalenciaTipo::REQUERIDA,
        ?EquivalenciaEstado $estado = null,
        ?int $codcur = null,
        ?int $codhab = null,
        ?int $criadoPorId = null,
        ?int $alteradoPorId = null
    ): self {
        $dados = [
            'grupo' => $grupo,
            'requerida_id' => $requeridaId,
            'cursada_id' => $cursadaId,
            'tipo' => $tipo instanceof EquivalenciaTipo ? $tipo : EquivalenciaTipo::from($tipo),
            'codcur' => $codcur,
            'codhab' => $codhab,
            'criado_por_id' => $criadoPorId,
            'alterado_por_id' => $alteradoPorId,
        ];

        if ($estado !== null) {
            $dados['estado'] = $estado;
        }

        return static::create($dados);
    }

    /**
     * Cria um vínculo automático entre a disciplina requerida e uma disciplina cursada.
     */
    public static function criarVinculoCursada(
        int $grupo,
        int $requeridaId,
        int $cursadaId,
        int $codcur,
        int $codhab,
        string|EquivalenciaTipo $tipo = EquivalenciaTipo::AUTOMATICA
    ): self {
        return static::criarVinculo($grupo, $requeridaId, $cursadaId, $tipo, codcur: $codcur, codhab: $codhab);
    }

    /**
     * Cria um grupo automático com uma ou mais disciplinas cursadas.
     */
    public static function criarGrupoDeCursadas(
        Disciplina $requerida,
        int $codcur,
        int $codhab,
        array $conjuntosDeCursadas
    ): void {
        $grupo = static::proximoGrupo();

        foreach ($conjuntosDeCursadas as $dadosCursada) {
            $cursada = Disciplina::criarCursadaPorFormulario($dadosCursada);

            static::criarVinculoCursada(
                $grupo,
                $requerida->id,
                $cursada->id,
                $codcur,
                $codhab,
                static::TIPO_AUTOMATICA
            );
        }
    }

    /**
     * Finaliza um requerimento manual a partir das equivalências salvas como rascunho.
     *
     * @param array<int, array{original_name: string, stored_path: string}> $histories Históricos já armazenados.
     *
     * @return array{group: int, name: string}
     */
    public static function criarRequerimentoDoRascunho(
        AproveitamentoRascunho $draft,
        array $histories,
        int $userId
    ): array {
        return DB::transaction(function () use ($draft, $histories, $userId) {
            $group = $draft->grupo();
            $requiredName = $draft->nomeDaDisciplinaRequerida() ?? $draft->requerida_coddis;

            $draft->placeholders()->each->delete();

            $draft->equivalenciasReais()
                ->each(fn (Aproveitamento $equivalence) => $equivalence->atualizarEstado(
                    EquivalenciaEstado::PROCESSANDO,
                    $userId
                ));

            foreach ($histories as $history) {
                Arquivo::criarHistorico($group, [
                    'original_name' => $history['original_name'],
                    'stored_path' => $history['stored_path'],
                ]);
            }

            return ['group' => $group, 'name' => $requiredName];
        });
    }

    /**
     * Lista os vínculos reais de cursadas de um grupo, ignorando o placeholder.
     */
    public static function vinculosReaisDoGrupo(
        Disciplina $requerida,
        int $grupo,
        int $codcur,
        int $codhab
    ): Collection {
        return static::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $requerida->id)
            ->doGrupo($grupo)
            ->whereColumn('cursada_id', '!=', 'requerida_id')
            ->with('cursada')
            ->orderBy('id')
            ->get();
    }

    /**
     * Atualiza as cursadas de um grupo, reaproveitando vínculos existentes quando possível.
     */
    public static function atualizarGrupoDeCursadas(
        Disciplina $requerida,
        self $vinculoReferencia,
        int $codcur,
        int $codhab,
        array $conjuntosDeCursadas
    ): void {
        $vinculosOrdenados = collect([$vinculoReferencia])
            // Reaproveita o vínculo de referência como primeiro item do grupo, mesmo que ele seja um placeholder.
            ->merge(
                static::vinculosReaisDoGrupo($requerida, (int) $vinculoReferencia->grupo, $codcur, $codhab)
                    ->reject(fn(Aproveitamento $item) => $item->id === $vinculoReferencia->id)
                    ->values()
            )
            ->values();
        // Itera sobre os dados enviados e atualiza ou cria vínculos conforme a posição,
        // reaproveitando o máximo possível dos vínculos existentes.
        foreach ($conjuntosDeCursadas as $index => $dadosCursada) {
            $vinculoExistente = $vinculosOrdenados->get($index);

            if ($vinculoExistente) {
                $vinculoExistente->loadMissing('cursada');

                if (! $vinculoExistente->cursada) {
                    throw new ModelNotFoundException();
                }

                $vinculoExistente->cursada->atualizarCursadaPorFormulario($dadosCursada);

                continue;
            }
            // Cria nova cursada e vínculo quando não houver mais vínculos existentes para reaproveitar.
            $novaCursada = Disciplina::criarCursadaPorFormulario($dadosCursada);
            // Garante que o primeiro vínculo do grupo seja sempre o de referência, mesmo que ele seja um placeholder
            static::criarVinculoCursada(
                (int) $vinculoReferencia->grupo,
                $requerida->id,
                $novaCursada->id,
                $codcur,
                $codhab,
                static::TIPO_AUTOMATICA
            );
        }
    }

    /**
     * Remove todos os vínculos de uma requerida no contexto e limpa disciplinas órfãs.
     *
     * Para a exclusão de um requerimento, é necessário remover todos os vínculos associados à disciplina requerida
     */
    public static function removerVinculosDaRequeridaNoContexto(Disciplina $requerida, int $codcur, int $codhab): void
    {
        $vinculos = static::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $requerida->id)
            ->get();
        $grupos = $vinculos->pluck('grupo')->unique();

        $cursadasParaLimpeza = static::idsDeCursadasReais($vinculos);

        static::query()
            ->whereIn('id', $vinculos->pluck('id'))
            ->delete();

        foreach ($grupos as $grupo) {
            Arquivo::removerHistoricosDoGrupo((int) $grupo);
        }

        foreach ($cursadasParaLimpeza as $cursadaId) {
            Disciplina::removerSeOrfaPorId((int) $cursadaId);
        }

        $requerida->removerSeOrfa();
    }

    /**
     * Remove este vínculo e limpa a disciplina cursada se ela ficar órfã.
     */
    public function removerELimparCursada(): void
    {
        $cursadaId = $this->cursada_id;

        $this->delete();

        Disciplina::removerSeOrfaPorId((int) $cursadaId);
    }

    /**
     * Remove um grupo de equivalências e limpa as disciplinas relacionadas quando órfãs.
     */
    public static function removerGrupoELimparDisciplinas(
        Disciplina $requerida,
        self $vinculoReferencia,
        int $codcur,
        int $codhab
    ): void {
        $vinculosDoGrupo = static::query()
            ->doContexto($codcur, $codhab)
            ->where('requerida_id', $requerida->id)
            ->where('grupo', $vinculoReferencia->grupo)
            ->get();

        $cursadasParaLimpeza = static::idsDeCursadasReais($vinculosDoGrupo);

        static::query()
            ->whereIn('id', $vinculosDoGrupo->pluck('id'))
            ->delete();

        Arquivo::removerHistoricosDoGrupo((int) $vinculoReferencia->grupo);

        foreach ($cursadasParaLimpeza as $cursadaId) {
            Disciplina::removerSeOrfaPorId((int) $cursadaId);
        }

        $requerida->removerSeOrfa();
    }

    /**
     * Monta os dados de formulário de edição para cada disciplina requerida.
     */
    public static function dadosParaFormularioEdicaoDeEquivalencias(Collection $disciplinas): array
    {
        return $disciplinas
            ->reduce(function (array $forms, Disciplina $disciplinaUsp) {
                $formsDaDisciplina = $disciplinaUsp->equivalentes
                    ->mapWithKeys(function (Aproveitamento $equivalenciaFilha) use ($disciplinaUsp) {
                        return [
                            $equivalenciaFilha->id => $disciplinaUsp->defaultsParaFormularioEdicaoDeGrupo($equivalenciaFilha),
                        ];
                    })
                    ->all();

                return $forms + $formsDaDisciplina;
            }, []);
    }

    /**
     * Lista os requerimentos criados por um usuário agrupados por equivalência.
     */
    public static function requerimentosDoUsuario(int $userId): array
    {
        return static::query()
            ->doUsuario($userId)
            ->naoRascunhos()
            ->with('requerida')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('grupo')
            ->map(function ($equivalenciasDoGrupo, $grupo) {
                $primeiraEquivalencia = $equivalenciasDoGrupo->first();

                return [
                    'nomdis' => $primeiraEquivalencia->requerida?->nomdis,
                    'estado' => $primeiraEquivalencia->estadoLabel(),
                    'grupo' => (int) $grupo,
                ];
            })
            ->all();
    }

    /**
     * Retorna as equivalências de um requerimento do usuário ou lança exceção.
     */
    public static function equivalenciasDoRequerimentoDoUsuario(int $group, int $userId): Collection
    {
        $equivalencias = static::query()
            ->doGrupo($group)
            ->doUsuario($userId)
            ->naoRascunhos()
            ->with(['requerida', 'cursada', 'arquivos'])
            ->orderBy('id')
            ->get();

        if ($equivalencias->isEmpty()) {
            throw new ModelNotFoundException();
        }

        return $equivalencias;
    }

    /**
     * Monta os dados usados para exibir um requerimento do usuário.
     */
    public static function dadosDeExibicaoDoRequerimento(int $group, int $userId): array
    {
        $equivalencias = static::equivalenciasDoRequerimentoDoUsuario($group, $userId);
        $primeiraEquivalencia = $equivalencias->first();
        $requerida = $primeiraEquivalencia->requerida;

        if (! $requerida) {
            throw new ModelNotFoundException();
        }

        $showData = [
            'requerida' => [
                'coddis' => $requerida->coddis,
                'nomdis' => $requerida->nomdis,
                'sglund' => $requerida->sglund,
            ],
            'grupo' => $group,
            'estado' => $primeiraEquivalencia->estadoLabel(),
            'created_at' => $equivalencias->min('created_at'),
            'cursadas' => [],
            'historicos' => Arquivo::historicosDoGrupo($group)
                ->map(fn (Arquivo $arquivo) => [
                    'id' => $arquivo->id,
                    'name' => $arquivo->nome,
                ])
                ->all(),
        ];

        foreach ($equivalencias as $equivalencia) {
            $cursada = $equivalencia->cursada;

            if (! $cursada) {
                throw new ModelNotFoundException();
            }

            $ementa = $equivalencia->arquivos
                ->firstWhere('tipo', Arquivo::TIPO_EMENTA);

            $showData['cursadas'][] = [
                'coddis' => $cursada->coddis,
                'nomdis' => $cursada->nomdis,
                'ementa_file' => $ementa ? [
                    'id' => $ementa->id,
                    'name' => $ementa->nome,
                ] : null,
                'semestre' => $cursada->semestre,
                'ano' => $cursada->ano,
                'freq' => $cursada->frequencia,
                'nota' => $cursada->nota,
                'creditos' => $cursada->creditos,
                'carga_hr' => $cursada->carga_horaria,
                'ies' => $cursada->ies,
            ];
        }

        return $showData;
    }

    /**
     * Remove um requerimento do usuário e retorna o nome da disciplina requerida removida.
     */
    public static function removerRequerimentoDoUsuario(int $group, int $userId): string
    {
        $equivalencias = static::equivalenciasDoRequerimentoDoUsuario($group, $userId);
        $requerida = $equivalencias->first()->requerida;

        if (! $requerida) {
            throw new ModelNotFoundException();
        }

        $nomeRequerida = $requerida->nomdis;

        Arquivo::removerHistoricosDoGrupo($group);

        foreach ($equivalencias as $equivalencia) {
            $equivalencia->cursada?->delete();
        }

        $requerida->delete();

        return $nomeRequerida;
    }

    /**
     * Verifica se a equivalência pertence ao curso e habilitação informados.
     */
    public function pertenceAoContexto(int $codcur, int $codhab): bool
    {
        return (int) $this->codcur === $codcur && (int) $this->codhab === $codhab;
    }

    /**
     * Verifica se a equivalência pertence à requerida dentro do contexto informado.
     */
    public function pertenceARequeridaNoContexto(int $requeridaId, int $codcur, int $codhab): bool
    {
        return (int) $this->requerida_id === $requeridaId && $this->pertenceAoContexto($codcur, $codhab);
    }

    /**
     * Verifica se a equivalência é um vínculo real da requerida no contexto.
     */
    public function isEquivalenciaRealDaRequeridaNoContexto(int $requeridaId, int $codcur, int $codhab): bool
    {
        return $this->pertenceARequeridaNoContexto($requeridaId, $codcur, $codhab)
            && ! $this->isPlaceholderRequerida();
    }

    private static function idsDeCursadasReais(Collection $vinculos): Collection
    {
        return $vinculos
            ->filter(fn (Aproveitamento $item) => ! $item->isPlaceholderRequerida())
            ->pluck('cursada_id')
            ->unique()
            ->values();
    }
}
