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
            'coddis' => ['nullable', 'string', 'max:7'],
            'nome_disciplina' => ['nullable', 'string', 'max:240'],
            'ies' => ['nullable', 'string', 'max:255'],
            'coddis2' => ['nullable', 'string', 'max:7'],
            'nome_disciplina2' => ['nullable', 'string', 'max:240'],
            'ies2' => ['nullable', 'string', 'max:255'],
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

            $coddis = trim((string) ($dados[$kCoddis] ?? ''));
            $nome = trim((string) ($dados[$kNome] ?? ''));
            $ies = trim((string) ($dados[$kIes] ?? ''));

            if ($coddis === '' && $nome === '' && $ies === '') {
                continue;
            }

            if ($coddis === '') {
                $erros[$kCoddis] = 'O campo código da equivalência é obrigatório para cada conjunto preenchido.';

                continue;
            }

            $dadosCursada = [
                'coddis' => $coddis,
                'nome_disciplina' => $nome !== '' ? $nome : null,
                'ies' => $ies !== '' ? $ies : null,
            ];

            if (! Disciplina::disciplinaUspNoReplicado($coddis)) {
                if (empty($dadosCursada['nome_disciplina'])) {
                    $erros[$kNome] = 'Nome da equivalência é obrigatório quando a disciplina não for USP.';
                }

                if (empty($dadosCursada['ies'])) {
                    $erros[$kIes] = 'IES é obrigatória quando a disciplina não for USP.';
                }
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
