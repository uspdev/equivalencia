<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Arquivo extends Model
{
    public const TIPO_HISTORICO = 'historico';

    public const TIPO_EMENTA = 'ementa';

    protected $fillable = [
        'equivalencia_id',
        'tipo',
        'nome',
        'path',
    ];

    public function aproveitamento()
    {
        return $this->belongsTo(Aproveitamento::class, 'equivalencia_id');
    }

    /**
     * Cria o arquivo de histórico escolar associado a uma equivalência.
     */
    public static function criarHistorico(int $equivalenciaId, array $dadosArquivo): self
    {
        return static::criarDoFormulario($equivalenciaId, static::TIPO_HISTORICO, $dadosArquivo);
    }

    /**
     * Cria o arquivo de ementa associado a uma equivalência.
     */
    public static function criarEmenta(int $equivalenciaId, array $dadosArquivo): self
    {
        return static::criarDoFormulario($equivalenciaId, static::TIPO_EMENTA, $dadosArquivo);
    }

    /**
     * Atualiza os metadados do arquivo a partir dos dados armazenados pelo formulário.
     */
    public function atualizarDoFormulario(array $dadosArquivo): void
    {
        $this->update([
            'nome' => $dadosArquivo['original_name'],
            'path' => $dadosArquivo['stored_path'],
        ]);
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
