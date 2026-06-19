<?php

namespace App\Models;

use App\Enums\EquivalenciaEstado;
use App\Enums\EquivalenciaTipo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AproveitamentoRascunho
{
    public const LIMITE_DISCIPLINAS = 3;

    public ?string $requerida_coddis = null;

    public ?int $requerida_verdis = null;

    private ?int $grupo = null;

    private ?Disciplina $requerida = null;

    private ?Collection $equivalencias = null;

    private function __construct(private readonly int $userId)
    {
        $this->carregar();
    }

    /**
     * Retorna o rascunho ativo do usuário.
     */
    public static function atualDoUsuario(int $userId): self
    {
        return new self($userId);
    }

    /**
     * Retorna as disciplinas do rascunho como Collection para facilitar filtros e transformações.
     */
    public function disciplinas(): Collection
    {
        return $this->equivalenciasReais()
            ->map(fn(Aproveitamento $equivalencia) => $this->dadosDaEquivalencia($equivalencia))
            ->values();
    }

    /**
     * Verifica se o rascunho já atingiu o limite de disciplinas cursadas.
     */
    public function atingiuLimiteDeDisciplinas(): bool
    {
        return $this->disciplinas()->count() >= self::LIMITE_DISCIPLINAS;
    }

    /**
     * Busca uma disciplina do rascunho pelo identificador interno.
     *
     * @throws ModelNotFoundException
     */
    public function disciplinaPorIdOrFail(string $disciplineId): array
    {
        $discipline = $this->disciplinas()
            ->first(fn(array $item) => ($item['id'] ?? null) === $disciplineId);

        if (! $discipline) {
            throw (new ModelNotFoundException())->setModel(Aproveitamento::class, [$disciplineId]);
        }

        return $discipline;
    }

    /**
     * Atualiza a disciplina USP requerida no rascunho.
     */
    public function salvarDisciplinaRequerida(string $coddis, ?int $verdis = null): void
    {
        DB::transaction(function () use ($coddis, $verdis) {
            $oldRequired = $this->requerida;
            $required = Disciplina::salvarRequeridaDoRascunho($coddis, $verdis, $this->userId, $oldRequired);

            $group = $this->grupo ?? Aproveitamento::proximoGrupo();
            $drafts = $this->equivalenciasDoRascunho();

            if ($drafts->isEmpty()) {
                $this->criarVinculoDoRascunho($group, $required->id, $required->id, placeholderRequerida: true);
            } else {
                $this->atualizarRequeridaDosVinculos($drafts, $required);
            }

            if ($oldRequired && $oldRequired->id !== $required->id) {
                $oldRequired->removerSeOrfa();
            }
        });

        $this->carregar();
    }

    /**
     * Adiciona uma disciplina cursada ao rascunho.
     */
    public function adicionarDisciplina(array $dados, ?UploadedFile $ementa = null): void
    {
        $this->garantirRequerida();

        DB::transaction(function () use ($dados, $ementa) {
            $course = Disciplina::criarCursadaDoRascunho($dados, $this->userId);
            $equivalence = $this->criarVinculoDoRascunho(
                $this->grupo,
                $this->requerida->id,
                $course->id,
                Disciplina::dadosDaOcorrenciaDoRascunho($dados, $this->userId)
            );

            Arquivo::salvarEmentaDaEquivalencia($equivalence, $dados['unidade_tipo'], $ementa);
        });

        $this->carregar();
    }

    /**
     * Atualiza uma disciplina cursada já existente no rascunho.
     */
    public function atualizarDisciplina(string $disciplineId, array $dados, ?UploadedFile $ementa = null): void
    {
        $equivalence = $this->equivalenciaPorIdOrFail($disciplineId);

        DB::transaction(function () use ($equivalence, $dados, $ementa) {
            if (! $equivalence->cursada) {
                throw (new ModelNotFoundException())->setModel(Disciplina::class, [$equivalence->cursada_id]);
            }

            $cursadaAnteriorId = (int) $equivalence->cursada_id;
            $course = $equivalence->cursada->atualizarCursadaDoRascunho($dados, $this->userId);

            // Se a disciplina era um placeholder da requerida, atualiza o vínculo para apontar para a nova cursada.
            $equivalence->update(array_merge(
                Disciplina::dadosDaOcorrenciaDoRascunho($dados, $this->userId),
                [
                    'cursada_id' => $course->id,
                    'alterado_por_id' => $this->userId,
                ]
            ));

            if ($cursadaAnteriorId !== (int) $course->id) {
                Disciplina::removerSeOrfaPorId($cursadaAnteriorId);
            }

            Arquivo::salvarEmentaDaEquivalencia($equivalence, $dados['unidade_tipo'], $ementa);
        });

        $this->carregar();
    }

    /**
     * Remove uma disciplina cursada do rascunho e apaga sua ementa armazenada, quando houver.
     */
    public function removerDisciplina(string $disciplineId): void
    {
        $equivalence = $this->equivalenciaPorIdOrFail($disciplineId);

        DB::transaction(function () use ($equivalence) {
            Arquivo::removerDaEquivalencia($equivalence);
            $equivalence->removerELimparCursada();
        });

        $this->carregar();
    }

    /**
     * Retorna o histórico escolar salvo no rascunho, quando houver.
     */
    public function historico(): ?Arquivo
    {
        return $this->grupo
            ? Arquivo::historicosDoGrupo((int) $this->grupo)->first()
            : null;
    }

    public function temHistorico(): bool
    {
        return $this->historico() !== null;
    }

    /**
     * Salva ou substitui o histórico escolar único do requerimento no rascunho.
     */
    public function salvarHistorico(UploadedFile $historico): Arquivo
    {
        $this->garantirRequerida();

        $dadosArquivo = Arquivo::armazenarUploadDoAproveitamento((int) $this->grupo, $historico, 'historicos');

        return Arquivo::criarHistorico((int) $this->grupo, $dadosArquivo);
    }

    /**
     * Consulta o nome da disciplina USP requerida para exibição do rascunho.
     */
    public function nomeDaDisciplinaRequerida(): ?string
    {
        return $this->requerida?->nomdis;
    }

    public function grupo(): ?int
    {
        return $this->grupo;
    }

    public function equivalenciasReais(): Collection
    {
        return $this->equivalenciasPorTipoDeVinculo(false);
    }

    public function placeholders(): Collection
    {
        return $this->equivalenciasPorTipoDeVinculo(true);
    }

    private function carregar(): void
    {
        $this->equivalencias = null;
        $first = $this->equivalenciasDoRascunho()->first();
        $this->grupo = $first?->grupo;
        $this->requerida = $first?->requerida;
        $this->requerida_coddis = $this->requerida?->coddis;
        $this->requerida_verdis = $this->requerida?->verdis;
    }

    private function equivalenciasDoRascunho(): Collection
    {
        if ($this->equivalencias !== null) {
            return $this->equivalencias;
        }

        $firstDraft = Aproveitamento::query()
            ->doUsuario($this->userId)
            ->rascunhos()
            ->requeridas()
            ->orderBy('grupo')
            ->orderBy('id')
            ->first();

        if (! $firstDraft) {
            return $this->equivalencias = new Collection();
        }

        return $this->equivalencias = Aproveitamento::query()
            ->doUsuario($this->userId)
            ->rascunhos()
            ->requeridas()
            ->doGrupo((int) $firstDraft->grupo)
            ->with(['requerida', 'cursada', 'arquivos'])
            ->orderBy('id')
            ->get();
    }

    private function equivalenciaPorIdOrFail(string $disciplineId): Aproveitamento
    {
        $equivalence = $this->equivalenciasReais()
            ->first(fn(Aproveitamento $item) => (string) $item->id === $disciplineId);

        if (! $equivalence) {
            throw (new ModelNotFoundException())->setModel(Aproveitamento::class, [$disciplineId]);
        }

        return $equivalence;
    }

    private function equivalenciasPorTipoDeVinculo(bool $placeholder): Collection
    {
        return $this->equivalenciasDoRascunho()
            ->filter(fn(Aproveitamento $equivalencia) => $equivalencia->isPlaceholderRequerida() === $placeholder)
            ->values();
    }

    private function garantirRequerida(): void
    {
        if (! $this->grupo || ! $this->requerida) {
            throw (new ModelNotFoundException())->setModel(Disciplina::class);
        }
    }

    private function criarVinculoDoRascunho(
        int $grupo,
        int $requeridaId,
        int $cursadaId,
        array $dadosOcorrencia = [],
        bool $placeholderRequerida = false
    ): Aproveitamento {
        return Aproveitamento::criarVinculo(
            $grupo,
            $requeridaId,
            $cursadaId,
            EquivalenciaTipo::REQUERIDA,
            EquivalenciaEstado::RASCUNHO,
            ano: $dadosOcorrencia['ano'] ?? null,
            semestre: $dadosOcorrencia['semestre'] ?? null,
            codtur: $dadosOcorrencia['codtur'] ?? null,
            frequencia: $dadosOcorrencia['frequencia'] ?? null,
            nota: $dadosOcorrencia['nota'] ?? null,
            criadoPorId: $this->userId,
            alteradoPorId: $this->userId,
            placeholderRequerida: $placeholderRequerida
        );
    }

    private function atualizarRequeridaDosVinculos(Collection $vinculos, Disciplina $requerida): void
    {
        foreach ($vinculos as $equivalencia) {
            $equivalencia->update([
                'requerida_id' => $requerida->id,
                'cursada_id' => $equivalencia->isPlaceholderRequerida()
                    ? $requerida->id
                    : $equivalencia->cursada_id,
                'alterado_por_id' => $this->userId,
            ]);
        }
    }

    private function dadosDaEquivalencia(Aproveitamento $equivalencia): array
    {
        $course = $equivalencia->cursada;

        if (! $course) {
            throw (new ModelNotFoundException())->setModel(Disciplina::class, [$equivalencia->cursada_id]);
        }

        $syllabus = $equivalencia->arquivos->firstWhere('tipo', Arquivo::TIPO_EMENTA);
        $isUsp = $course->ies === 'USP';

        return [
            'id' => (string) $equivalencia->id,
            'unidade_tipo' => $isUsp ? 'USP' : 'OUTRA',
            'unidade_nome' => $isUsp ? 'USP' : $course->ies,
            'coddis' => $course->coddis,
            'verdis' => $course->verdis,
            'nomdis' => $course->nomdis,
            'ano' => $equivalencia->ano,
            'semestre' => $equivalencia->semestre,
            'codtur' => $equivalencia->codtur,
            'frequencia' => $equivalencia->frequencia !== null ? (float) $equivalencia->frequencia : null,
            'nota' => $equivalencia->nota !== null ? (float) $equivalencia->nota : null,
            'creditos' => $course->creditos,
            'carga_horaria' => $course->carga_horaria,
            'sglund' => $course->sglund,
            'programa' => $course->programa,
            'programa_resumo' => $course->programa_resumo,
            'objetivo' => $course->objetivo,
            'disciplina_ativa' => $course->disciplina_ativa,
            'ementa' => $syllabus ? [
                'name' => $syllabus->nome,
                'path' => $syllabus->path,
            ] : null,
        ];
    }
}
