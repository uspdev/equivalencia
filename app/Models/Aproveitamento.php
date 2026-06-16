<?php

namespace App\Models;

use App\Enums\EquivalenciaTipo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Aproveitamento extends Model
{
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
        return $query->where('tipo', EquivalenciaTipo::AUTOMATICA);
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
        return static::create([
            'grupo' => static::proximoGrupo(),
            'requerida_id' => $requeridaId,
            'cursada_id' => $requeridaId,
            'tipo' => static::TIPO_AUTOMATICA,
            'codcur' => $codcur,
            'codhab' => $codhab,
        ]);
    }

    /**
     * Cria um vínculo entre a disciplina requerida e uma disciplina cursada.
     */
    public static function criarVinculoCursada(
        int $grupo,
        int $requeridaId,
        int $cursadaId,
        int $codcur,
        int $codhab,
        string|EquivalenciaTipo $tipo = EquivalenciaTipo::AUTOMATICA
    ): self {
        return static::create([
            'grupo' => $grupo,
            'requerida_id' => $requeridaId,
            'cursada_id' => $cursadaId,
            'tipo' => $tipo instanceof EquivalenciaTipo ? $tipo : EquivalenciaTipo::from($tipo),
            'codcur' => $codcur,
            'codhab' => $codhab,
        ]);
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
     * Cria um requerimento manual a partir de um rascunho e dos arquivos já armazenados.
     *
     * @param AproveitamentoRascunho $draft Rascunho do aproveitamento usado como base.
     * @param array<int, array{name: string, path: string}> $histories Históricos já armazenados.
     * @param int $userId ID do usuário responsável pela criação.
     *
     * @return array{group: int, name: string}
     */
    public static function criarRequerimentoDoRascunho(
        AproveitamentoRascunho $draft,
        array $histories,
        int $userId
    ): array {
        return DB::transaction(function () use ($draft, $histories, $userId) {
            $requiredData = Disciplina::dadosDaRequeridaPorCoddis($draft->requerida_coddis);
            $requiredData['nomdis'] ??= $draft->requerida_coddis;
            $requiredData['ies'] = 'USP';
            $requiredData['criado_por_id'] = $userId;
            $requiredData['alterado_por_id'] = $userId;
            $required = Disciplina::create($requiredData);
            $group = static::proximoGrupo();

            foreach ($draft->disciplinas() as $disciplineData) {
                $course = Disciplina::create(static::dadosDaCursadaDoRascunho($disciplineData, $userId));

                $equivalence = static::create([
                    'grupo' => $group,
                    'requerida_id' => $required->id,
                    'cursada_id' => $course->id,
                    'tipo' => EquivalenciaTipo::REQUERIDA,
                    'criado_por_id' => $userId,
                    'alterado_por_id' => $userId,
                ]);

                if (isset($disciplineData['ementa'])) {
                    Arquivo::criarEmenta($equivalence->id, [
                        'original_name' => $disciplineData['ementa']['name'],
                        'stored_path' => $disciplineData['ementa']['path'],
                    ]);
                }
            }

            foreach ($histories as $history) {
                Arquivo::criarHistorico($group, [
                    'original_name' => $history['name'],
                    'stored_path' => $history['path'],
                ]);
            }

            $draft->delete();

            return ['group' => $group, 'name' => $required->nomdis];
        });
    }

    /**
     * Monta os dados da disciplina cursada a partir dos dados salvos no rascunho.
     *
     * @param array{
     *     unidade_tipo: string,
     *     coddis: string,
     *     nomdis: string,
     *     unidade_nome: string,
     *     ano: int|string,
     *     semestre: int|string,
     *     frequencia: int|float|string,
     *     nota: int|float|string,
     *     creditos: int|float|string,
     *     carga_horaria: int|float|string
     * } $disciplineData Dados da disciplina cursada no rascunho.
     * @param int $userId ID do usuário responsável pela criação/alteração.
     *
     * @return array<string, mixed>
     */
    private static function dadosDaCursadaDoRascunho(array $disciplineData, int $userId): array
    {
        $courseData = Disciplina::dadosDaCursadaPorFormulario([
            'is_usp' => $disciplineData['unidade_tipo'] === 'USP',
            'coddis' => $disciplineData['coddis'],
            'nome_disciplina' => $disciplineData['nomdis'],
            'ies' => $disciplineData['unidade_tipo'] === 'USP'
                ? 'USP'
                : $disciplineData['unidade_nome'],
            'ano' => $disciplineData['ano'],
            'semestre' => $disciplineData['semestre'],
            'frequencia' => $disciplineData['frequencia'],
            'nota' => $disciplineData['nota'],
            'creditos' => $disciplineData['creditos'],
            'carga_horaria' => $disciplineData['carga_horaria'],
        ]);

        $courseData['criado_por_id'] = $userId;
        $courseData['alterado_por_id'] = $userId;

        return $courseData;
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
            ->where('grupo', $grupo)
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

        $cursadasParaLimpeza = $vinculos
            ->filter(fn(Aproveitamento $item) => ! $item->isPlaceholderRequerida())
            ->pluck('cursada_id')
            ->unique()
            ->values();

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

        $cursadasParaLimpeza = $vinculosDoGrupo
            ->pluck('cursada_id')
            ->unique()
            ->values();

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
            ->where('criado_por_id', $userId)
            ->with('requerida')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('grupo')
            ->map(function ($equivalenciasDoGrupo, $grupo) {
                $primeiraEquivalencia = $equivalenciasDoGrupo->first();

                return [
                    'nomdis' => $primeiraEquivalencia->requerida?->nomdis,
                    'estado' => $primeiraEquivalencia->estado,
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
            ->where('grupo', $group)
            ->where('criado_por_id', $userId)
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
            'estado' => $primeiraEquivalencia->estado,
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
}
