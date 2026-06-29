<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\Disciplina;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SaveEquivalenciaFilhaRequest extends FormRequest
{
    private const SUFIXOS_DE_CONJUNTOS = ['', '2', '3'];

    public function authorize(): bool
    {
        return $this->user()?->can(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'is_usp' => ['nullable', 'boolean'],
            'coddis' => ['nullable', 'string', 'max:'.$this->maxCoddisLength(''), 'min:3'],
            'verdis' => ['nullable', 'integer', 'min:1', 'max:255'],
            'nome_disciplina' => ['nullable', 'string', 'max:240'],
            'ies' => ['nullable', 'string', 'max:255'],
            'credito_aula' => ['nullable', 'integer', 'min:0'],
            'credito_trabalho' => ['nullable', 'integer', 'min:0'],
            'numero_reuniao' => ['nullable', 'integer'],
            'data_reuniao' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string'],
            'is_usp2' => ['nullable', 'boolean'],
            'coddis2' => ['nullable', 'string', 'max:'.$this->maxCoddisLength('2'), 'min:3'],
            'verdis2' => ['nullable', 'integer', 'min:1', 'max:255'],
            'nome_disciplina2' => ['nullable', 'string', 'max:240'],
            'ies2' => ['nullable', 'string', 'max:255'],
            'credito_aula2' => ['nullable', 'integer', 'min:0'],
            'credito_trabalho2' => ['nullable', 'integer', 'min:0'],
            'is_usp3' => ['nullable', 'boolean'],
            'coddis3' => ['nullable', 'string', 'max:'.$this->maxCoddisLength('3'), 'min:3'],
            'verdis3' => ['nullable', 'integer', 'min:1', 'max:255'],
            'nome_disciplina3' => ['nullable', 'string', 'max:240'],
            'ies3' => ['nullable', 'string', 'max:255'],
            'credito_aula3' => ['nullable', 'integer', 'min:0'],
            'credito_trabalho3' => ['nullable', 'integer', 'min:0'],
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
        $numeroReuniao = trim((string) ($dados['numero_reuniao'] ?? ''));
        $dataReuniao = trim((string) ($dados['data_reuniao'] ?? ''));
        $observacoes = trim((string) ($dados['observacoes'] ?? ''));

        foreach (self::SUFIXOS_DE_CONJUNTOS as $sufixo) {
            $kCoddis = 'coddis'.$sufixo;
            $kVerdis = 'verdis'.$sufixo;
            $kNome = 'nome_disciplina'.$sufixo;
            $kIes = 'ies'.$sufixo;
            $kCreditoAula = 'credito_aula'.$sufixo;
            $kCreditoTrabalho = 'credito_trabalho'.$sufixo;
            $kIsUsp = 'is_usp'.$sufixo;

            $coddis = trim((string) ($dados[$kCoddis] ?? ''));
            $verdis = trim((string) ($dados[$kVerdis] ?? ''));
            $nome = trim((string) ($dados[$kNome] ?? ''));
            $ies = trim((string) ($dados[$kIes] ?? ''));
            $creditoAula = trim((string) ($dados[$kCreditoAula] ?? ''));
            $creditoTrabalho = trim((string) ($dados[$kCreditoTrabalho] ?? ''));
            $marcadaComoUsp = $this->boolean($kIsUsp);
            $temDadosPreenchidos = $coddis !== '' ||
                $verdis !== '' ||
                $nome !== '' ||
                $ies !== '' ||
                $creditoAula !== '' ||
                $creditoTrabalho !== '';

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
                ? Disciplina::disciplinaUspNoReplicado($coddis, $verdis !== '' ? (int) $verdis : null)
                : null;

            if ($marcadaComoUsp && (! $disciplinaUsp || ! isset($disciplinaUsp['verdis']))) {
                $erros[$kCoddis] = 'Selecione uma disciplina USP válida.';

                continue;
            }

            $dadosCursada = [
                'is_usp' => $marcadaComoUsp,
                'coddis' => $disciplinaUsp['coddis'] ?? $coddis,
                'verdis' => $disciplinaUsp['verdis'] ?? ($verdis !== '' ? (int) $verdis : null),
                'nome_disciplina' => $nome !== '' ? $nome : null,
                'ies' => $ies !== '' ? $ies : null,
                'credito_aula' => $creditoAula !== '' ? (int) $creditoAula : null,
                'credito_trabalho' => $creditoTrabalho !== '' ? (int) $creditoTrabalho : null,
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
                $dadosCursada['credito_aula'] = null;
                $dadosCursada['credito_trabalho'] = null;
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
