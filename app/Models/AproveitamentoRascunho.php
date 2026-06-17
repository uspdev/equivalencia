<?php

namespace App\Models;

use App\Enums\EquivalenciaEstado;
use App\Enums\EquivalenciaTipo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AproveitamentoRascunho
{
    public const LIMITE_DISCIPLINAS = 3;

    public ?string $requerida_coddis = null;

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
    public function salvarDisciplinaRequerida(string $coddis): void
    {
        DB::transaction(function () use ($coddis) {
            $oldRequired = $this->requerida;
            $required = Disciplina::salvarRequeridaDoRascunho($coddis, $this->userId, $oldRequired);

            $group = $this->grupo ?? Aproveitamento::proximoGrupo();
            $drafts = $this->equivalenciasDoRascunho();

            if ($drafts->isEmpty()) {
                $this->criarVinculoDoRascunho($group, $required->id, $required->id);
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
            $equivalence = $this->criarVinculoDoRascunho($this->grupo, $this->requerida->id, $course->id);

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

            $equivalence->cursada->atualizarCursadaDoRascunho($dados, $this->userId);
            $equivalence->update(['alterado_por_id' => $this->userId]);

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
     * Agrupa disciplinas externas por unidade para exigir um histórico por instituição.
     */
    public function gruposDeHistorico(): Collection
    {
        return $this->disciplinas()
            ->where('unidade_tipo', 'OUTRA')
            ->groupBy(fn(array $discipline) => $this->chaveDoHistorico($discipline['unidade_nome']))
            ->map(function (Collection $group, string $key) {
                return [
                    'key' => $key,
                    'unit_name' => $group->first()['unidade_nome'],
                    'disciplines' => $group->values(),
                ];
            })
            ->values();
    }

    /**
     * Armazena os históricos obrigatórios por grupo e o histórico adicional opcional.
     */
    public function armazenarHistoricos(array $historicos, ?UploadedFile $historicoAdicional = null): array
    {
        $arquivos = $this->gruposDeHistorico()
            ->map(fn(array $group) => Arquivo::armazenarUploadDoAproveitamento(
                (int) $this->grupo,
                $historicos[$group['key']],
                'historicos'
            ))
            ->values()
            ->all();

        if ($historicoAdicional) {
            $arquivos[] = Arquivo::armazenarUploadDoAproveitamento((int) $this->grupo, $historicoAdicional, 'historicos');
        }

        return $arquivos;
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

    private function criarVinculoDoRascunho(int $grupo, int $requeridaId, int $cursadaId): Aproveitamento
    {
        return Aproveitamento::criarVinculo(
            $grupo,
            $requeridaId,
            $cursadaId,
            EquivalenciaTipo::REQUERIDA,
            EquivalenciaEstado::RASCUNHO,
            criadoPorId: $this->userId,
            alteradoPorId: $this->userId
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
            'nomdis' => $course->nomdis,
            'ano' => $course->ano,
            'semestre' => $course->semestre,
            'frequencia' => $course->frequencia !== null ? (float) $course->frequencia : null,
            'nota' => $course->nota !== null ? (float) $course->nota : null,
            'creditos' => $course->creditos,
            'carga_horaria' => $course->carga_horaria,
            'ementa' => $syllabus ? [
                'name' => $syllabus->nome,
                'path' => $syllabus->path,
            ] : null,
        ];
    }

    /**
     * Gera uma chave estável para agrupar históricos pela unidade externa informada.
     */
    private function chaveDoHistorico(string $unitName): string
    {
        $normalized = Str::of($unitName)
            ->ascii()
            ->lower()
            ->squish()
            ->value();

        return hash('sha256', $normalized);
    }
}
