<?php

namespace App\Models;

use App\Enums\DisciplinaRole;
use App\Enums\EquivalenciaEstado;
use App\Enums\EquivalenciaTipo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Aproveitamento extends Model
{
    public const ESTADO_RASCUNHO = EquivalenciaEstado::RASCUNHO->value;

    public const ESTADO_PROCESSANDO = EquivalenciaEstado::PROCESSANDO->value;

    public const ESTADO_DEFERIDO = EquivalenciaEstado::DEFERIDO->value;

    public const ESTADO_NEGADO = EquivalenciaEstado::NEGADO->value;

    public const TIPO_AUTOMATICA = EquivalenciaTipo::AUTOMATICA->value;

    public const TIPO_SOLICITADA = EquivalenciaTipo::SOLICITADA->value;

    protected $table = 'aproveitamentos';

    protected $fillable = [
        'estado',
        'tipo',
        'codcur',
        'codhab',
        'numero_reuniao',
        'data_reuniao',
        'observacoes',
        'historico_id',
        'criado_por_id',
        'alterado_por_id',
    ];

    protected $casts = [
        'estado' => EquivalenciaEstado::class,
        'tipo' => EquivalenciaTipo::class,
        'codcur' => 'integer',
        'codhab' => 'integer',
        'numero_reuniao' => 'integer',
        'data_reuniao' => 'date',
        'historico_id' => 'integer',
        'criado_por_id' => 'integer',
        'alterado_por_id' => 'integer',
    ];

    public function disciplinas()
    {
        return $this->hasMany(Disciplina::class)->orderBy('id');
    }

    public function requerida()
    {
        return $this->hasOne(Disciplina::class)->where('role', DisciplinaRole::REQUERIDA->value);
    }

    public function cursadas()
    {
        return $this->hasMany(Disciplina::class)->where('role', DisciplinaRole::CURSADA->value)->orderBy('id');
    }

    public function historico()
    {
        return $this->belongsTo(Arquivo::class, 'historico_id');
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
        return $query->where('tipo', EquivalenciaTipo::SOLICITADA->value);
    }

    public function scopeDoUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('criado_por_id', $userId);
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

    public function scopeDoContexto(Builder $query, int $codcur, int $codhab): Builder
    {
        return $query
            ->where('codcur', $codcur)
            ->where('codhab', $codhab);
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

    public function pertenceAoContexto(int $codcur, int $codhab): bool
    {
        return (int) $this->codcur === $codcur && (int) $this->codhab === $codhab;
    }

    public static function criarAutomatico(int $codcur, int $codhab): self
    {
        return static::create([
            'tipo' => EquivalenciaTipo::AUTOMATICA,
            'codcur' => $codcur,
            'codhab' => $codhab,
        ]);
    }

    public static function criarAproveitamentoAutomaticoComCursadas(
        Disciplina $requerida,
        int $codcur,
        int $codhab,
        array $conjuntosDeCursadas
    ): void {
        DB::transaction(function () use ($requerida, $codcur, $codhab, $conjuntosDeCursadas) {
            $aproveitamento = static::create([
                'tipo' => EquivalenciaTipo::AUTOMATICA,
                'codcur' => $codcur,
                'codhab' => $codhab,
                'numero_reuniao' => $conjuntosDeCursadas[0]['numero_reuniao'] ?? null,
                'data_reuniao' => $conjuntosDeCursadas[0]['data_reuniao'] ?? null,
                'observacoes' => $conjuntosDeCursadas[0]['observacoes'] ?? null,
            ]);

            Disciplina::create(array_merge(
                $requerida->only([
                    'verdis',
                    'coddis',
                    'nomdis',
                    'creditos',
                    'carga_horaria',
                    'ies',
                    'sglund',
                    'disciplina_ativa',
                ]),
                [
                    'aproveitamento_id' => $aproveitamento->id,
                    'role' => DisciplinaRole::REQUERIDA,
                ]
            ));

            static::salvarCursadasAutomaticas($aproveitamento, $conjuntosDeCursadas);
        });
    }

    public static function atualizarAproveitamentoAutomaticoComCursadas(
        Disciplina $requerida,
        self $aproveitamento,
        int $codcur,
        int $codhab,
        array $conjuntosDeCursadas
    ): void {
        abort_unless($aproveitamento->isAutomaticoDaRequeridaNoContexto($requerida, $codcur, $codhab), 404);

        DB::transaction(function () use ($aproveitamento, $conjuntosDeCursadas) {
            $aproveitamento->update([
                'numero_reuniao' => $conjuntosDeCursadas[0]['numero_reuniao'] ?? null,
                'data_reuniao' => $conjuntosDeCursadas[0]['data_reuniao'] ?? null,
                'observacoes' => $conjuntosDeCursadas[0]['observacoes'] ?? null,
            ]);

            $cursadas = $aproveitamento->cursadas()->get()->values();

            foreach ($conjuntosDeCursadas as $index => $dadosCursada) {
                $disciplina = $cursadas->get($index);
                $dados = array_merge(
                    Disciplina::dadosDaCursadaPorFormulario($dadosCursada),
                    [
                        'aproveitamento_id' => $aproveitamento->id,
                        'role' => DisciplinaRole::CURSADA,
                    ]
                );

                $disciplina ? $disciplina->update($dados) : Disciplina::create($dados);
            }

            $cursadas
                ->slice(count($conjuntosDeCursadas))
                ->each(fn (Disciplina $disciplina) => $disciplina->removerSeOrfa());
        });
    }

    public function removerELimparCursada(Disciplina $cursada): void
    {
        abort_unless((int) $cursada->aproveitamento_id === (int) $this->id, 404);

        $cursada->removerSeOrfa();

        if ($this->cursadas()->count() === 0) {
            $this->deleteComArquivos();
        }
    }

    public static function removerAproveitamentoAutomatico(
        Disciplina $requerida,
        self $aproveitamento,
        int $codcur,
        int $codhab
    ): void {
        abort_unless($aproveitamento->isAutomaticoDaRequeridaNoContexto($requerida, $codcur, $codhab), 404);

        $aproveitamento->deleteComArquivos();
    }

    public static function removerVinculosDaRequeridaNoContexto(Disciplina $requerida, int $codcur, int $codhab): void
    {
        static::query()
            ->automaticas()
            ->doContexto($codcur, $codhab)
            ->whereHas('requerida', function ($query) use ($requerida) {
                $query
                    ->where('coddis', $requerida->coddis)
                    ->where('verdis', $requerida->verdis)
                    ->where('ies', $requerida->ies);
            })
            ->with(['disciplinas.ementa', 'historico'])
            ->get()
            ->each(fn (Aproveitamento $aproveitamento) => $aproveitamento->deleteComArquivos());
    }

    /**
     * Monta os dados de formulário de edição para cada disciplina requerida.
     */
    public static function dadosParaFormularioEdicaoDeEquivalencias(
        EloquentCollection $disciplinas,
        bool $useOldInput = true
    ): array {
        return $disciplinas
            ->reduce(function (array $forms, Disciplina $disciplinaUsp) use ($useOldInput) {
                $formsDaDisciplina = $disciplinaUsp->equivalentes
                    ->mapWithKeys(function (Aproveitamento $equivalenciaFilha) use ($disciplinaUsp, $useOldInput) {
                        return [
                            $equivalenciaFilha->id => $disciplinaUsp->defaultsParaFormularioEdicaoDeGrupo(
                                $equivalenciaFilha,
                                $useOldInput
                            ),
                        ];
                    })
                    ->all();

                return $forms + $formsDaDisciplina;
            }, []);
    }

    public static function criarRequerimentoDoRascunho(
        AproveitamentoRascunho $draft,
        int $userId
    ): array {
        return DB::transaction(function () use ($draft, $userId) {
            $aproveitamento = $draft->aproveitamentoOrFail();
            $requiredName = $draft->nomeDaDisciplinaRequerida() ?? $draft->requerida_coddis;

            if (! $aproveitamento->historico_id) {
                throw ValidationException::withMessages([
                    'historico' => 'Envie o histórico escolar do requerimento.',
                ]);
            }

            $aproveitamento->atualizarEstado(EquivalenciaEstado::PROCESSANDO, $userId);

            return ['id' => $aproveitamento->id, 'name' => $requiredName];
        });
    }

    public static function requerimentosDoUsuario(int $userId): array
    {
        return static::query()
            ->doUsuario($userId)
            ->naoRascunhos()
            ->requeridas()
            ->with('requerida')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Aproveitamento $aproveitamento) {
                return [
                    'nomdis' => $aproveitamento->requerida?->nomdis ?? $aproveitamento->requerida?->coddis,
                    'estado' => $aproveitamento->estadoLabel(),
                    'id' => (int) $aproveitamento->id,
                ];
            })
            ->all();
    }

    public static function requerimentoDoUsuarioOrFail(int $id, int $userId): self
    {
        $aproveitamento = static::query()
            ->whereKey($id)
            ->doUsuario($userId)
            ->naoRascunhos()
            ->requeridas()
            ->with(['requerida', 'cursadas.ementa', 'historico'])
            ->first();

        if (! $aproveitamento) {
            throw new ModelNotFoundException();
        }

        return $aproveitamento;
    }

    public static function dadosDeExibicaoDoRequerimento(int $id, int $userId): array
    {
        $aproveitamento = static::requerimentoDoUsuarioOrFail($id, $userId);
        $requerida = $aproveitamento->requerida;

        if (! $requerida) {
            throw new ModelNotFoundException();
        }

        return [
            'requerida' => [
                'coddis' => $requerida->coddis,
                'verdis' => $requerida->verdis,
                'nomdis' => $requerida->nomdis,
                'sglund' => $requerida->sglund,
            ],
            'id' => (int) $aproveitamento->id,
            'estado' => $aproveitamento->estadoLabel(),
            'created_at' => $aproveitamento->created_at,
            'historicos' => $aproveitamento->historico ? [[
                'id' => $aproveitamento->historico->id,
                'name' => $aproveitamento->historico->nome,
            ]] : [],
            'cursadas' => $aproveitamento->cursadas
                ->map(fn (Disciplina $cursada) => [
                    'coddis' => $cursada->coddis,
                    'verdis' => $cursada->verdis,
                    'nomdis' => $cursada->nomdis,
                    'ementa_file' => $cursada->ementa ? [
                        'id' => $cursada->ementa->id,
                        'name' => $cursada->ementa->nome,
                    ] : null,
                    'semestre' => $cursada->semestre,
                    'ano' => $cursada->ano,
                    'codtur' => $cursada->codtur,
                    'freq' => $cursada->frequencia,
                    'nota' => $cursada->nota,
                    'creditos' => $cursada->creditos,
                    'carga_hr' => $cursada->carga_horaria,
                    'ies' => $cursada->ies,
                    'sglund' => $cursada->sglund,
                    'disciplina_ativa' => $cursada->disciplina_ativa,
                ])
                ->all(),
        ];
    }

    /**
     * Remove um requerimento do usuário e retorna o nome da disciplina requerida removida.
     */
    public static function removerRequerimentoDoUsuario(int $id, int $userId): string
    {
        $aproveitamento = static::requerimentoDoUsuarioOrFail($id, $userId);
        $nomeRequerida = $aproveitamento->requerida?->nomdis ?? $aproveitamento->requerida?->coddis ?? '';

        $aproveitamento->deleteComArquivos();

        return $nomeRequerida;
    }

    public function isAutomaticoDaRequeridaNoContexto(Disciplina $requerida, int $codcur, int $codhab): bool
    {
        $this->loadMissing('requerida');

        return $this->tipo === EquivalenciaTipo::AUTOMATICA
            && $this->pertenceAoContexto($codcur, $codhab)
            && $this->requerida
            && $this->requerida->coddis === $requerida->coddis
            && (int) $this->requerida->verdis === (int) $requerida->verdis
            && $this->requerida->ies === $requerida->ies;
    }

    public function deleteComArquivos(): void
    {
        $this->loadMissing(['disciplinas.ementa', 'historico']);

        foreach ($this->disciplinas as $disciplina) {
            $disciplina->removerSeOrfa();
        }

        if ($this->historico) {
            $this->historico->removerArquivoERegistro();
        }

        $this->delete();
    }

    private static function salvarCursadasAutomaticas(self $aproveitamento, array $conjuntosDeCursadas): void
    {
        foreach ($conjuntosDeCursadas as $dadosCursada) {
            Disciplina::create(array_merge(
                Disciplina::dadosDaCursadaPorFormulario($dadosCursada),
                [
                    'aproveitamento_id' => $aproveitamento->id,
                    'role' => DisciplinaRole::CURSADA,
                ]
            ));
        }
    }
}
