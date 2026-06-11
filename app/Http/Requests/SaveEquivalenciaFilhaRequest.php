<?php

namespace App\Http\Requests;

use App\Models\Disciplina;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SaveEquivalenciaFilhaRequest extends FormRequest
{
    private const SUFIXOS_DE_CONJUNTOS = ['', '2', '3'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_usp' => ['nullable', 'boolean'],
            'coddis' => ['nullable', 'string', 'max:7'],
            'nome_disciplina' => ['nullable', 'string', 'max:240'],
            'ies' => ['nullable', 'string', 'max:255'],
            'is_usp2' => ['nullable', 'boolean'],
            'coddis2' => ['nullable', 'string', 'max:7'],
            'nome_disciplina2' => ['nullable', 'string', 'max:240'],
            'ies2' => ['nullable', 'string', 'max:255'],
            'is_usp3' => ['nullable', 'boolean'],
            'coddis3' => ['nullable', 'string', 'max:7'],
            'nome_disciplina3' => ['nullable', 'string', 'max:240'],
            'ies3' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function conjuntosDeEquivalencia(): array
    {
        $dados = $this->validated();
        $conjuntos = [];
        $erros = [];

        foreach (self::SUFIXOS_DE_CONJUNTOS as $sufixo) {
            $kCoddis = 'coddis'.$sufixo;
            $kNome = 'nome_disciplina'.$sufixo;
            $kIes = 'ies'.$sufixo;
            $kIsUsp = 'is_usp'.$sufixo;

            $coddis = trim((string) ($dados[$kCoddis] ?? ''));
            $nome = trim((string) ($dados[$kNome] ?? ''));
            $ies = trim((string) ($dados[$kIes] ?? ''));
            $marcadaComoUsp = $this->boolean($kIsUsp);

            if ($coddis === '' && $nome === '' && $ies === '' && ! $marcadaComoUsp) {
                continue;
            }

            if ($coddis === '') {
                $erros[$kCoddis] = 'O campo código da equivalência é obrigatório para cada conjunto preenchido.';

                continue;
            }

            $disciplinaUsp = $marcadaComoUsp
                ? Disciplina::disciplinaUspNoReplicado($coddis)
                : null;

            if ($marcadaComoUsp && ! $disciplinaUsp) {
                $erros[$kCoddis] = 'Selecione uma disciplina USP válida.';

                continue;
            }

            $dadosCursada = [
                'is_usp' => $marcadaComoUsp,
                'coddis' => $coddis,
                'nome_disciplina' => $nome !== '' ? $nome : null,
                'ies' => $ies !== '' ? $ies : null,
            ];

            if (! $marcadaComoUsp) {
                if (empty($dadosCursada['nome_disciplina'])) {
                    $erros[$kNome] = 'Nome da equivalência é obrigatório quando a disciplina não for USP.';
                }

                if (empty($dadosCursada['ies'])) {
                    $erros[$kIes] = 'IES é obrigatória quando a disciplina não for USP.';
                }
            } else {
                $dadosCursada['nome_disciplina'] = null;
                $dadosCursada['ies'] = 'USP';
            }

            $conjuntos[] = $dadosCursada;
        }

        if ($erros) {
            throw ValidationException::withMessages($erros);
        }

        if (count($conjuntos) === 0) {
            throw ValidationException::withMessages([
                'coddis' => 'Preencha ao menos um conjunto de equivalência.',
            ]);
        }

        return $conjuntos;
    }
}
