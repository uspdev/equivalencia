<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Arquivo extends Model
{
    public const TIPO_HISTORICO = 'historico';

    public const TIPO_EMENTA = 'ementa';

    protected $fillable = [
        'tipo',
        'nome',
        'path',
    ];

    public static function armazenarUploadDoAproveitamento(int $aproveitamentoId, UploadedFile $arquivo, string $diretorio): array
    {
        return [
            'original_name' => $arquivo->getClientOriginalName(),
            'stored_path' => $arquivo->store("aproveitamentos/{$aproveitamentoId}/{$diretorio}"),
        ];
    }

    public static function criarHistorico(Aproveitamento $aproveitamento, array $dadosArquivo): self
    {
        if ($aproveitamento->historico) {
            $aproveitamento->historico->removerArquivoERegistro();
        }

        $arquivo = static::criarDoFormulario(static::TIPO_HISTORICO, $dadosArquivo);
        $aproveitamento->update(['historico_id' => $arquivo->id]);
        $aproveitamento->setRelation('historico', $arquivo);

        return $arquivo;
    }

    public static function salvarEmentaDaDisciplina(
        Disciplina $disciplina,
        string $unidadeTipo,
        ?UploadedFile $ementa
    ): void {
        $ementaAtual = $disciplina->ementa;

        if ($unidadeTipo === 'OUTRA' && $ementa) {
            $dadosArquivo = static::armazenarUploadDoAproveitamento(
                (int) $disciplina->aproveitamento_id,
                $ementa,
                'ementas'
            );

            if ($ementaAtual) {
                $ementaAtual->atualizarDoFormulario($dadosArquivo);
                return;
            }

            $arquivo = static::criarDoFormulario(static::TIPO_EMENTA, $dadosArquivo);
            $disciplina->update(['ementa_id' => $arquivo->id]);
            $disciplina->setRelation('ementa', $arquivo);

            return;
        }

        if ($unidadeTipo !== 'OUTRA' && $ementaAtual) {
            $ementaAtual->removerArquivoERegistro();
            $disciplina->update(['ementa_id' => null]);
            $disciplina->unsetRelation('ementa');
        }
    }

    public static function pertencenteAoRequerimentoDoUsuarioOrFail(
        int $arquivoId,
        int $aproveitamentoId,
        int $userId
    ): self {
        $aproveitamento = Aproveitamento::requerimentoDoUsuarioOrFail($aproveitamentoId, $userId);
        $idsPermitidos = collect([$aproveitamento->historico_id])
            ->merge($aproveitamento->cursadas->pluck('ementa_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (! $idsPermitidos->contains((int) $arquivoId)) {
            throw (new ModelNotFoundException())->setModel(static::class, [$arquivoId]);
        }

        $arquivo = static::query()->whereKey($arquivoId)->first();

        if (! $arquivo) {
            throw (new ModelNotFoundException())->setModel(static::class, [$arquivoId]);
        }

        return $arquivo;
    }

    public static function removerDaDisciplina(Disciplina $disciplina): void
    {
        if ($disciplina->ementa) {
            $disciplina->ementa->removerArquivoERegistro();
            $disciplina->update(['ementa_id' => null]);
        }
    }

    public function atualizarDoFormulario(array $dadosArquivo): void
    {
        if ($this->path !== $dadosArquivo['stored_path']) {
            Storage::delete($this->path);
        }

        $this->update([
            'nome' => $dadosArquivo['original_name'],
            'path' => $dadosArquivo['stored_path'],
        ]);
    }

    public function removerArquivoERegistro(): void
    {
        Storage::delete($this->path);
        $this->delete();
    }

    private static function criarDoFormulario(string $tipo, array $dadosArquivo): self
    {
        return static::create([
            'tipo' => $tipo,
            'nome' => $dadosArquivo['original_name'],
            'path' => $dadosArquivo['stored_path'],
        ]);
    }
}
