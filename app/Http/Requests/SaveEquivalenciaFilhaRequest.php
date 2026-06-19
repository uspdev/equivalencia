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
            'coddis' => ['nullable', 'string', 'max:'.$this->maxCoddisLength('')],
            'nome_disciplina' => ['nullable', 'string', 'max:240'],
            'ies' => ['nullable', 'string', 'max:255'],
            'numero_reuniao' => ['nullable', 'integer'],
            'data_reuniao' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string'],
            'is_usp2' => ['nullable', 'boolean'],
            'coddis2' => ['nullable', 'string', 'max:'.$this->maxCoddisLength('2')],
            'nome_disciplina2' => ['nullable', 'string', 'max:240'],
            'ies2' => ['nullable', 'string', 'max:255'],
            'numero_reuniao2' => ['nullable', 'integer'],
            'data_reuniao2' => ['nullable', 'date'],
            'observacoes2' => ['nullable', 'string'],
            'is_usp3' => ['nullable', 'boolean'],
            'coddis3' => ['nullable', 'string', 'max:'.$this->maxCoddisLength('3')],
            'nome_disciplina3' => ['nullable', 'string', 'max:240'],
            'ies3' => ['nullable', 'string', 'max:255'],
            'numero_reuniao3' => ['nullable', 'integer'],
            'data_reuniao3' => ['nullable', 'date'],
            'observacoes3' => ['nullable', 'string'],
        ];
    }

    private function maxCoddisLength(string $sufixo): int
    {
        return $this->boolean('is_usp'.$sufixo) ? 7 : 15;
    }

    /**
     * Processa os dados dos conjuntos de equivalência preenchidos no formulário, validando as regras de negócio
     * e retornando um array estruturado para ser salvo.
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
            $kNumeroReuniao = 'numero_reuniao'.$sufixo;
            $kDataReuniao = 'data_reuniao'.$sufixo;
            $kObservacoes = 'observacoes'.$sufixo;

            $coddis = trim((string) ($dados[$kCoddis] ?? ''));
            $nome = trim((string) ($dados[$kNome] ?? ''));
            $ies = trim((string) ($dados[$kIes] ?? ''));
            $numeroReuniao = trim((string) ($dados[$kNumeroReuniao] ?? ''));
            $dataReuniao = trim((string) ($dados[$kDataReuniao] ?? ''));
            $observacoes = trim((string) ($dados[$kObservacoes] ?? ''));
            $marcadaComoUsp = $this->boolean($kIsUsp);
            $temDadosPreenchidos = $coddis !== '' ||
                $nome !== '' ||
                $ies !== '' ||
                $numeroReuniao !== '' ||
                $dataReuniao !== '' ||
                $observacoes !== '';

            // Se não tem dados preenchidos, ignora o conjunto, a menos que seja o primeiro (sufixo vazio)
            // ou esteja marcado como USP
            // ignora o primeiro pois é obrigatório preencher ao menos um conjunto,
            // e os outros apenas se tiverem dados ou forem USP
            if (! $temDadosPreenchidos && ($sufixo !== '' || ! $marcadaComoUsp)) {
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
                'numero_reuniao' => $numeroReuniao !== '' ? (int) $numeroReuniao : null,
                'data_reuniao' => $dataReuniao !== '' ? $dataReuniao : null,
                'observacoes' => $observacoes !== '' ? $observacoes : null,
            ];

            if (! $marcadaComoUsp) {
                if (empty($dadosCursada['nome_disciplina'])) {
                    $erros[$kNome] = 'Nome da disciplina é obrigatório quando a disciplina não for USP.';
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
