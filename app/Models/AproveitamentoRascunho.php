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

    private ?Aproveitamento $aproveitamento = null;

    private function __construct(private readonly int $userId)
    {
        $this->carregar();
    }

    public static function atualDoUsuario(int $userId): self
    {
        return new self($userId);
    }

    public function disciplinas(): Collection
    {
        return $this->cursadas()
            ->map(fn (Disciplina $disciplina) => $this->dadosDaDisciplina($disciplina))
            ->values();
    }

    public function atingiuLimiteDeDisciplinas(): bool
    {
        return $this->disciplinas()->count() >= self::LIMITE_DISCIPLINAS;
    }

    public function disciplinaPorIdOrFail(string $disciplineId): array
    {
        $discipline = $this->disciplinas()
            ->first(fn (array $item) => ($item['id'] ?? null) === $disciplineId);

        if (! $discipline) {
            throw (new ModelNotFoundException())->setModel(Disciplina::class, [$disciplineId]);
        }

        return $discipline;
    }

    public function salvarDisciplinaRequerida(string $coddis, ?int $verdis = null): void
    {
        DB::transaction(function () use ($coddis, $verdis) {
            $aproveitamento = $this->garantirAproveitamento();

            Disciplina::salvarRequeridaDoRascunho(
                $coddis,
                $verdis,
                $this->userId,
                (int) $aproveitamento->id,
                $aproveitamento->requerida
            );
        });

        $this->carregar();
    }

    public function adicionarDisciplina(array $dados, ?UploadedFile $ementa = null): void
    {
        $this->garantirRequerida();

        DB::transaction(function () use ($dados, $ementa) {
            $course = Disciplina::criarCursadaDoRascunho(
                $dados,
                $this->userId,
                (int) $this->aproveitamento->id
            );

            Arquivo::salvarEmentaDaDisciplina($course, $dados['unidade_tipo'], $ementa);
        });

        $this->carregar();
    }

    public function atualizarDisciplina(string $disciplineId, array $dados, ?UploadedFile $ementa = null): void
    {
        $discipline = $this->disciplinaModelPorIdOrFail($disciplineId);

        DB::transaction(function () use ($discipline, $dados, $ementa) {
            $course = $discipline->atualizarCursadaDoRascunho($dados, $this->userId);

            Arquivo::salvarEmentaDaDisciplina($course, $dados['unidade_tipo'], $ementa);
        });

        $this->carregar();
    }

    public function removerDisciplina(string $disciplineId): void
    {
        $discipline = $this->disciplinaModelPorIdOrFail($disciplineId);

        DB::transaction(fn () => $discipline->removerSeOrfa());

        $this->carregar();
    }

    public function historico(): ?Arquivo
    {
        return $this->aproveitamento?->historico;
    }

    public function temHistorico(): bool
    {
        return $this->historico() !== null;
    }

    public function salvarHistorico(UploadedFile $historico): Arquivo
    {
        $this->garantirRequerida();

        $dadosArquivo = Arquivo::armazenarUploadDoAproveitamento(
            (int) $this->aproveitamento->id,
            $historico,
            'historicos'
        );

        $arquivo = Arquivo::criarHistorico($this->aproveitamento, $dadosArquivo);

        $this->carregar();

        return $arquivo;
    }

    public function nomeDaDisciplinaRequerida(): ?string
    {
        return $this->aproveitamento?->requerida?->nomdis;
    }

    public function aproveitamentoOrFail(): Aproveitamento
    {
        if (! $this->aproveitamento) {
            throw (new ModelNotFoundException())->setModel(Aproveitamento::class);
        }

        return $this->aproveitamento;
    }

    private function carregar(): void
    {
        $this->aproveitamento = Aproveitamento::query()
            ->doUsuario($this->userId)
            ->rascunhos()
            ->requeridas()
            ->with(['requerida', 'cursadas.ementa', 'historico'])
            ->orderBy('id')
            ->first();

        $this->requerida_coddis = $this->aproveitamento?->requerida?->coddis;
        $this->requerida_verdis = $this->aproveitamento?->requerida?->verdis;
    }

    private function garantirAproveitamento(): Aproveitamento
    {
        if ($this->aproveitamento) {
            return $this->aproveitamento->loadMissing('requerida');
        }

        $this->aproveitamento = Aproveitamento::create([
            'estado' => EquivalenciaEstado::RASCUNHO,
            'tipo' => EquivalenciaTipo::SOLICITADA,
            'criado_por_id' => $this->userId,
            'alterado_por_id' => $this->userId,
        ]);

        return $this->aproveitamento;
    }

    private function garantirRequerida(): void
    {
        if (! $this->aproveitamento || ! $this->aproveitamento->requerida) {
            throw (new ModelNotFoundException())->setModel(Disciplina::class);
        }
    }

    private function cursadas(): Collection
    {
        return $this->aproveitamento?->cursadas ?? new Collection();
    }

    private function disciplinaModelPorIdOrFail(string $disciplineId): Disciplina
    {
        $discipline = $this->cursadas()
            ->first(fn (Disciplina $item) => (string) $item->id === $disciplineId);

        if (! $discipline) {
            throw (new ModelNotFoundException())->setModel(Disciplina::class, [$disciplineId]);
        }

        return $discipline;
    }

    private function dadosDaDisciplina(Disciplina $course): array
    {
        $isUsp = $course->ies === 'USP';

        return [
            'id' => (string) $course->id,
            'unidade_tipo' => $isUsp ? 'USP' : 'OUTRA',
            'unidade_nome' => $isUsp ? 'USP' : $course->ies,
            'coddis' => $course->coddis,
            'verdis' => $course->verdis,
            'nomdis' => $course->nomdis,
            'ano' => $course->ano,
            'semestre' => $course->semestre,
            'codtur' => $course->codtur,
            'frequencia' => $course->frequencia !== null ? (float) $course->frequencia : null,
            'nota' => $course->nota !== null ? (float) $course->nota : null,
            'creditos' => $course->creditos,
            'carga_horaria' => $course->carga_horaria,
            'sglund' => $course->sglund,
            'disciplina_ativa' => $course->disciplina_ativa,
            'ementa' => $course->ementa ? [
                'name' => $course->ementa->nome,
                'path' => $course->ementa->path,
            ] : null,
        ];
    }
}
