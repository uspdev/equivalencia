<?php

namespace App\Models;

use App\Replicado\Graduacao;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AproveitamentoRascunho extends Model
{
    public const LIMITE_DISCIPLINAS = 3;

    protected $table = 'aproveitamento_rascunhos';

    protected $fillable = [
        'user_id',
        'requerida_coddis',
        'disciplinas',
    ];

    protected $casts = [
        'disciplinas' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna o rascunho ativo do usuário ou cria um novo rascunho vazio.
     */
    public static function atualDoUsuario(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            ['disciplinas' => []]
        );
    }

    /**
     * Retorna as disciplinas do rascunho como Collection para facilitar filtros e transformações.
     */
    public function disciplinas(): Collection
    {
        return collect($this->disciplinas ?? []);
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
            ->first(fn (array $item) => ($item['id'] ?? null) === $disciplineId);

        if (! $discipline) {
            throw (new ModelNotFoundException())->setModel(static::class, [$disciplineId]);
        }

        return $discipline;
    }

    /**
     * Atualiza a disciplina USP requerida no rascunho.
     */
    public function salvarDisciplinaRequerida(string $coddis): void
    {
        $this->update(['requerida_coddis' => $coddis]);
    }

    /**
     * Adiciona uma disciplina cursada ao rascunho.
     */
    public function adicionarDisciplina(array $dados, ?UploadedFile $ementa = null): void
    {
        $disciplinas = $this->disciplinas()->all();
        $disciplinas[] = $this->dadosDaDisciplina($dados, null, $ementa);

        $this->update(['disciplinas' => $disciplinas]);
    }

    /**
     * Atualiza uma disciplina cursada já existente no rascunho.
     */
    public function atualizarDisciplina(string $disciplineId, array $dados, ?UploadedFile $ementa = null): void
    {
        $current = $this->disciplinaPorIdOrFail($disciplineId);
        $updated = $this->dadosDaDisciplina($dados, $current, $ementa);

        $disciplinas = $this->disciplinas()
            ->map(fn (array $discipline) => $discipline['id'] === $disciplineId ? $updated : $discipline)
            ->values()
            ->all();

        $this->update(['disciplinas' => $disciplinas]);
    }

    /**
     * Remove uma disciplina cursada do rascunho e apaga sua ementa armazenada, quando houver.
     */
    public function removerDisciplina(string $disciplineId): void
    {
        $discipline = $this->disciplinaPorIdOrFail($disciplineId);

        if (isset($discipline['ementa']['path'])) {
            Storage::delete($discipline['ementa']['path']);
        }

        $disciplinas = $this->disciplinas()
            ->reject(fn (array $item) => $item['id'] === $disciplineId)
            ->values()
            ->all();

        $this->update(['disciplinas' => $disciplinas]);
    }

    /**
     * Agrupa disciplinas externas por unidade para exigir um histórico por instituição.
     */
    public function gruposDeHistorico(): Collection
    {
        return $this->disciplinas()
            ->where('unidade_tipo', 'OUTRA')
            ->groupBy(fn (array $discipline) => $this->chaveDoHistorico($discipline['unidade_nome']))
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
            ->map(fn (array $group) => $this->armazenarArquivo(
                $historicos[$group['key']],
                'historicos'
            ))
            ->values()
            ->all();

        if ($historicoAdicional) {
            $arquivos[] = $this->armazenarArquivo($historicoAdicional, 'historicos');
        }

        return $arquivos;
    }

    /**
     * Consulta o nome da disciplina USP requerida para exibição do rascunho.
     */
    public function nomeDaDisciplinaRequerida(): ?string
    {
        if (! $this->requerida_coddis) {
            return null;
        }

        return app(Graduacao::class)->buscarDisciplina($this->requerida_coddis)['nomdis'] ?? null;
    }

    /**
     * Normaliza os dados validados de uma disciplina cursada para armazenamento no rascunho.
     */
    private function dadosDaDisciplina(
        array $dados,
        ?array $current = null,
        ?UploadedFile $ementa = null
    ): array {
        $isExternal = $dados['unidade_tipo'] === 'OUTRA';
        $code = Str::upper(trim($dados['coddis']));
        $uspDiscipline = $isExternal ? null : app(Graduacao::class)->buscarDisciplina($code);

        $discipline = [
            'id' => $current['id'] ?? (string) Str::uuid(),
            'unidade_tipo' => $dados['unidade_tipo'],
            'unidade_nome' => $isExternal ? trim($dados['unidade_nome']) : 'USP',
            'coddis' => $code,
            'nomdis' => $isExternal
                ? trim($dados['nomdis'])
                : trim((string) $uspDiscipline['nomdis']),
            'ano' => (int) $dados['ano'],
            'semestre' => (int) $dados['semestre'],
            'frequencia' => $isExternal ? (float) $dados['frequencia'] : null,
            'nota' => $isExternal ? (float) $dados['nota'] : null,
            'creditos' => $isExternal ? (int) $dados['creditos'] : null,
            'carga_horaria' => $isExternal ? (int) $dados['carga_horaria'] : null,
        ];

        if ($isExternal && isset($current['ementa'])) {
            $discipline['ementa'] = $current['ementa'];
        }

        if ($ementa) {
            if (isset($current['ementa']['path'])) {
                Storage::delete($current['ementa']['path']);
            }

            $discipline['ementa'] = $this->armazenarArquivo($ementa, 'ementas');
        } elseif (! $isExternal && isset($current['ementa']['path'])) {
            Storage::delete($current['ementa']['path']);
        }

        return $discipline;
    }

    /**
     * Armazena um arquivo do rascunho e retorna os metadados usados posteriormente na persistência.
     */
    private function armazenarArquivo(UploadedFile $file, string $directory): array
    {
        return [
            'name' => $file->getClientOriginalName(),
            'path' => $file->store("aproveitamentos/{$this->id}/{$directory}"),
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
