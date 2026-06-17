<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Arquivo extends Model
{
    public const TIPO_HISTORICO = 'historico';

    public const TIPO_EMENTA = 'ementa';

    protected $fillable = [
        'equivalencia_id',
        'grupo',
        'tipo',
        'nome',
        'path',
    ];

    protected $casts = [
        'equivalencia_id' => 'integer',
        'grupo' => 'integer',
    ];

    public function aproveitamento()
    {
        return $this->belongsTo(Aproveitamento::class, 'equivalencia_id');
    }

    public static function criarHistorico(int $grupo, array $dadosArquivo): self
    {
        return static::create([
            'grupo' => $grupo,
            'tipo' => static::TIPO_HISTORICO,
            'nome' => $dadosArquivo['original_name'],
            'path' => $dadosArquivo['stored_path'],
        ]);
    }

    /**
     * Armazena um upload de aproveitamento e retorna os metadados usados na persistência.
     */
    public static function armazenarUploadDoAproveitamento(int $grupo, UploadedFile $arquivo, string $diretorio): array
    {
        return [
            'original_name' => $arquivo->getClientOriginalName(),
            'stored_path' => $arquivo->store("aproveitamentos/{$grupo}/{$diretorio}"),
        ];
    }

    /**
     * Cria o arquivo de ementa associado a uma equivalência.
     */
    public static function criarEmenta(int $equivalenciaId, array $dadosArquivo): self
    {
        return static::criarDoFormulario($equivalenciaId, static::TIPO_EMENTA, $dadosArquivo);
    }

    /**
     * Cria ou atualiza a ementa de uma equivalência conforme o tipo da unidade.
     */
    public static function salvarEmentaDaEquivalencia(
        Aproveitamento $equivalencia,
        string $unidadeTipo,
        ?UploadedFile $ementa
    ): void {
        $ementaAtual = $equivalencia->arquivos->firstWhere('tipo', static::TIPO_EMENTA);

        if ($unidadeTipo === 'OUTRA' && $ementa) {
            $dadosArquivo = static::armazenarUploadDoAproveitamento((int) $equivalencia->grupo, $ementa, 'ementas');

            $ementaAtual
                ? $ementaAtual->atualizarDoFormulario($dadosArquivo)
                : static::criarEmenta($equivalencia->id, $dadosArquivo);

            return;
        }

        if ($unidadeTipo !== 'OUTRA' && $ementaAtual) {
            $ementaAtual->removerArquivoERegistro();
        }
    }

    public static function historicosDoGrupo(int $grupo): Collection
    {
        return static::query()
            ->where('grupo', $grupo)
            ->where('tipo', static::TIPO_HISTORICO)
            ->orderBy('id')
            ->get();
    }

    /**
     * Retorna um arquivo que pertença ao requerimento do usuário ou lança exceção.
     */
    public static function doRequerimentoDoUsuarioOrFail(int $arquivoId, int $grupo, int $userId): self
    {
        $equivalencias = Aproveitamento::equivalenciasDoRequerimentoDoUsuario($grupo, $userId);

        $arquivo = static::query()
            ->whereKey($arquivoId)
            ->where(function ($query) use ($grupo, $equivalencias) {
                $query
                    ->where(function ($ementaQuery) use ($equivalencias) {
                        $ementaQuery
                            ->where('tipo', static::TIPO_EMENTA)
                            ->whereIn('equivalencia_id', $equivalencias->pluck('id'));
                    })
                    ->orWhere(function ($historicoQuery) use ($grupo) {
                        $historicoQuery
                            ->where('tipo', static::TIPO_HISTORICO)
                            ->where('grupo', $grupo);
                    });
            })
            ->first();

        if (! $arquivo) {
            throw (new ModelNotFoundException())->setModel(static::class, [$arquivoId]);
        }

        return $arquivo;
    }

    public static function removerHistoricosDoGrupo(int $grupo): void
    {
        $historicos = static::historicosDoGrupo($grupo);

        foreach ($historicos as $historico) {
            $historico->removerArquivoERegistro();
        }
    }

    /**
     * Remove todos os arquivos vinculados à equivalência.
     */
    public static function removerDaEquivalencia(Aproveitamento $equivalencia): void
    {
        foreach ($equivalencia->arquivos as $arquivo) {
            $arquivo->removerArquivoERegistro();
        }
    }

    /**
     * Atualiza os metadados do arquivo a partir dos dados armazenados pelo formulário.
     */
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

    /**
     * Persiste um arquivo de formulário usando o tipo informado.
     */
    private static function criarDoFormulario(int $equivalenciaId, string $tipo, array $dadosArquivo): self
    {
        return static::create([
            'equivalencia_id' => $equivalenciaId,
            'tipo' => $tipo,
            'nome' => $dadosArquivo['original_name'],
            'path' => $dadosArquivo['stored_path'],
        ]);
    }
}
