<?php

namespace App\Models;

use App\Enums\EquivalenciaTipo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;

class Aproveitamento extends Model
{
    public const TIPO_AUTOMATICA = EquivalenciaTipo::AUTOMATICA->value;

    public const TIPO_REQUERIDA = EquivalenciaTipo::REQUERIDA->value;

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

        $cursadasParaLimpeza = $vinculos
            ->filter(fn(Aproveitamento $item) => ! $item->isPlaceholderRequerida())
            ->pluck('cursada_id')
            ->unique()
            ->values();

        static::query()
            ->whereIn('id', $vinculos->pluck('id'))
            ->delete();

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

        foreach ($cursadasParaLimpeza as $cursadaId) {
            Disciplina::removerSeOrfaPorId((int) $cursadaId);
        }

        $requerida->removerSeOrfa();
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
     * Cria um requerimento completo a partir dos dados do formulário.
     */
    public static function criarRequerimentoDoFormulario(array $dados, array $dadosSubmissao, int $userId): array
    {
        $grupo = static::proximoGrupo();
        $requerida = Disciplina::criarRequeridaDeRequerimento($dados, $userId);

        for ($i = 1; $i < 4; $i++) {
            if (empty($dados['coddis' . $i])) {
                continue;
            }

            $cursada = Disciplina::criarCursadaDeRequerimento($dados, $i, $userId);

            $equivalencia = static::create([
                'grupo' => $grupo,
                'requerida_id' => $requerida->id,
                'cursada_id' => $cursada->id,
                'tipo' => EquivalenciaTipo::REQUERIDA,
                'criado_por_id' => $userId,
                'alterado_por_id' => $userId,
            ]);

            if ($i === 1) {
                Arquivo::criarHistorico($equivalencia->id, $dadosSubmissao['hist_esc']);
            }

            Arquivo::criarEmenta($equivalencia->id, $dadosSubmissao['file_dis' . $i]);
        }

        return ['eq_group' => $grupo, 'req_name' => $requerida->nomdis, 'modo' => 'criado'];
    }

    /**
     * Atualiza um requerimento existente com disciplinas e arquivos enviados.
     */
    public static function atualizarRequerimentoDoFormulario(
        int $group,
        array $dados,
        array $dadosSubmissao,
        int $userId
    ): array {
        $equivalencias = static::equivalenciasDoRequerimentoParaEdicao($group, $userId);

        if ($equivalencias->isEmpty()) {
            return [];
        }

        $requerida = $equivalencias->first()->requerida;

        if (! $requerida) {
            throw new ModelNotFoundException();
        }

        $requerida->atualizarRequeridaDeRequerimento($dados, $userId);

        foreach ($equivalencias as $index => $equivalencia) {
            $numeroDisciplina = $index + 1;
            $cursada = $equivalencia->cursada;

            if (! $cursada) {
                throw new ModelNotFoundException();
            }

            $cursada->atualizarCursadaDeRequerimento($dados, $numeroDisciplina, $userId);
            $equivalencia->atualizarArquivosDoRequerimento($dadosSubmissao, $numeroDisciplina);
        }

        return ['eq_group' => $group, 'req_name' => $requerida->nomdis, 'modo' => 'atualizado'];
    }

    /**
     * Monta os dados usados para exibir um requerimento do usuário.
     */
    public static function dadosDeExibicaoDoRequerimento(int $group, int $userId): array
    {
        $equivalencias = static::equivalenciasDoRequerimentoDoUsuario($group, $userId);
        $requerida = $equivalencias->first()->requerida;

        if (! $requerida) {
            throw new ModelNotFoundException();
        }

        $showData = [
            'requerida' => [
                'coddis' => $requerida->coddis,
                'nomdis' => $requerida->nomdis,
                'sglund' => $requerida->sglund,
            ],
            'cursadas' => [],
        ];

        foreach ($equivalencias as $equivalencia) {
            $cursada = $equivalencia->cursada;
            $arquivo = $equivalencia->arquivos->first();

            if (! $cursada || ! $arquivo) {
                throw new ModelNotFoundException();
            }

            $showData['cursadas'][] = [
                'coddis' => $cursada->coddis,
                'nomdis' => $cursada->nomdis,
                'ementa_file' => [
                    'name' => $arquivo->nome,
                    'path' => $arquivo->path,
                ],
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
     * Monta os valores usados para preencher o formulário de edição do requerimento.
     */
    public static function dadosParaFormularioDoRequerimento(int $group, int $userId): array
    {
        $equivalencias = static::equivalenciasDoRequerimentoDoUsuario($group, $userId);
        $primeiraEquivalencia = $equivalencias->first();
        $data = [
            'unidade_ies' => $primeiraEquivalencia->cursada?->ies,
            'coddis4' => $primeiraEquivalencia->requerida?->coddis,
            'disciplina4' => $primeiraEquivalencia->requerida?->nomdis,
        ];

        foreach ($equivalencias as $index => $equivalencia) {
            $numeroDisciplina = $index + 1;
            $cursada = $equivalencia->cursada;

            $data["coddis{$numeroDisciplina}"] = $cursada?->coddis;
            $data["disciplina{$numeroDisciplina}"] = $cursada?->nomdis;
            $data["credit_dis{$numeroDisciplina}"] = $cursada?->creditos;
            $data["cghr_dis{$numeroDisciplina}"] = $cursada?->carga_horaria;
            $data["ano_dis{$numeroDisciplina}"] = $cursada?->ano;
            $data["semestre_dis{$numeroDisciplina}"] = match ((int) $cursada?->semestre) {
                1 => '1°',
                2 => '2°',
                default => null,
            };
            $data["freq_dis{$numeroDisciplina}"] = $cursada?->frequencia;
            $data["nota_dis{$numeroDisciplina}"] = $cursada?->nota;

            foreach ($equivalencia->arquivos as $arquivo) {
                if ($arquivo->tipo === Arquivo::TIPO_HISTORICO) {
                    $data['hist_esc'] = [
                        'original_name' => $arquivo->nome,
                        'stored_path' => $arquivo->path,
                    ];

                    continue;
                }

                if ($arquivo->tipo === Arquivo::TIPO_EMENTA) {
                    $data["file_dis{$numeroDisciplina}"] = [
                        'original_name' => $arquivo->nome,
                        'stored_path' => $arquivo->path,
                    ];
                }
            }
        }

        return $data;
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

        foreach ($equivalencias as $equivalencia) {
            $equivalencia->cursada?->delete();
        }

        $requerida->delete();

        return $nomeRequerida;
    }

    /**
     * Busca as equivalências do requerimento para edição sem lançar exceção quando vazio.
     */
    private static function equivalenciasDoRequerimentoParaEdicao(int $group, int $userId): Collection
    {
        return static::query()
            ->where('grupo', $group)
            ->where('criado_por_id', $userId)
            ->with(['requerida', 'cursada', 'arquivos'])
            ->orderBy('id')
            ->get();
    }

    /**
     * Atualiza os arquivos anexados ao vínculo conforme os campos enviados.
     */
    private function atualizarArquivosDoRequerimento(array $dadosSubmissao, int $numeroDisciplina): void
    {
        foreach ($this->arquivos as $arquivo) {
            $campo = $arquivo->tipo === Arquivo::TIPO_HISTORICO
                ? 'hist_esc'
                : 'file_dis' . $numeroDisciplina;

            if (isset($dadosSubmissao[$campo])) {
                $arquivo->atualizarDoFormulario($dadosSubmissao[$campo]);
            }
        }
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
