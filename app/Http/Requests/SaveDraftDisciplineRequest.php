<?php

namespace App\Http\Requests;

use App\Models\AproveitamentoRascunho;
use App\Replicado\Graduacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveDraftDisciplineRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->session()->flash('discipline_modal', $this->route('disciplineId') ?: 'create');

        $codtur = trim((string) $this->input('codtur', ''));

        if (preg_match('/^\d{4}[12]$/', $codtur)) {
            $this->merge([
                'codtur' => $codtur,
                'ano' => (int) substr($codtur, 0, 4),
                'semestre' => (int) substr($codtur, 4, 1),
            ]);
        } else {
            $this->merge(['codtur' => $codtur]);
        }

        abort_if(
            ! $this->route('disciplineId') && $this->draft()->atingiuLimiteDeDisciplinas(),
            422,
            'O limite de três disciplinas foi atingido.'
        );
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isExternal = $this->isExternalDiscipline();

        return [
            'unidade_tipo' => ['required', Rule::in(['USP', 'OUTRA'])],
            'unidade_nome' => [$isExternal ? 'required' : 'nullable', 'string', 'max:255'],
            'coddis' => [
                'bail',
                'required',
                'string',
                $isExternal
                    ? 'regex:/^[A-Za-z0-9]{1,15}$/'
                    : 'regex:/^[A-Za-z0-9]{3,7}$/',
            ],
            'nomdis' => [$isExternal ? 'required' : 'nullable', 'string', 'max:240'],
            'codtur' => ['required', 'regex:/^\d{4}[12]$/'],
            'ano' => ['required', 'integer', 'min:1900', 'max:' . date('Y')],
            'semestre' => ['required', 'integer', Rule::in([1, 2])],
            'ementa' => [$this->needsSyllabus() ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:10240'],
            'frequencia' => [$isExternal ? 'required' : 'nullable', 'numeric', 'min:0', 'max:100'],
            'nota' => [$isExternal ? 'required' : 'nullable', 'numeric', 'min:0', 'max:10'],
            'creditos' => [$isExternal ? 'required' : 'nullable', 'integer', 'min:1'],
            'carga_horaria' => [$isExternal ? 'required' : 'nullable', 'integer', 'min:1'],
            'requerida_coddis' => ['bail', 'required', 'string', 'regex:/^[A-Za-z0-9]{3,7}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'unidade_nome.required' => 'Informe o nome da unidade ou instituição.',
            'nomdis.required' => 'Informe o nome da disciplina externa.',
            'ano.max' => 'Informe o ano do calendário, que não pode ser posterior ao ano atual.',
            'semestre.in' => 'O semestre deve ser 1 ou 2.',
            'codtur.required' => 'Informe o ano e semestre em que cursou.',
            'codtur.regex' => 'Informe o período no padrão ano e semestre com 5 dígitos. Exemplo: 20251.',
            'ementa.required' => 'Envie a ementa da disciplina externa.',
            'ementa.mimes' => 'A ementa deve ser um arquivo PDF.',
            'ementa.max' => 'A ementa pode ter no máximo 10 MB.',
            'coddis.regex' => 'Informe um código de disciplina válido, com até 15 letras ou números.',
            'requerida_coddis.required' => 'Selecione a disciplina para a qual deseja equivalência.',
            'requerida_coddis.regex' => 'Selecione uma disciplina USP válida.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $graduacao = app(Graduacao::class);

                if (
                    ! $validator->errors()->has('requerida_coddis') &&
                    ! $graduacao->existeDisciplinaPorCodigoVersao(
                        $this->requiredDisciplineCode(),
                        $this->requiredDisciplineVersion()
                    )
                ) {
                    $validator->errors()->add(
                        'requerida_coddis',
                        'A disciplina USP selecionada não foi encontrada.'
                    );
                }

                if ($this->isExternalDiscipline() || $validator->errors()->has('coddis')) {
                    return;
                }

                if (! $graduacao->existeDisciplinaPorCodigoVersao(
                    $this->disciplineCode(),
                    $this->disciplineVersion()
                )) {
                    $validator->errors()->add('coddis', 'A disciplina USP selecionada não foi encontrada.');
                    return;
                }

                if ($validator->errors()->has('codtur') || $validator->errors()->has('ano') || $validator->errors()->has('semestre')) {
                    return;
                }

                if (! $graduacao->obterDisciplinaCursadaPorAlunoEmPeriodoCodtur(
                    (int) $this->user()->codpes,
                    $this->disciplineCode(),
                    (string) $this->input('codtur'),
                    $this->disciplineVersion()
                )) {
                    $validator->errors()->add(
                        'coddis',
                        'A disciplina USP informada não foi encontrada no seu histórico escolar para o período selecionado.'
                    );
                }
            },
        ];
    }


    /**
     * Retorna o rascunho de aproveitamento atualmente associado ao usuário autenticado.
     *
     * @return AproveitamentoRascunho
     */
    public function draft(): AproveitamentoRascunho
    {
        return AproveitamentoRascunho::atualDoUsuario((int) $this->user()->id);
    }

    /**
     * Obtém os dados da disciplina atualmente referenciada pela rota.
     *
     * Quando o parâmetro `disciplineId` estiver presente na rota, retorna
     * a disciplina correspondente armazenada no rascunho do usuário.
     * Caso contrário, retorna null.
     *
     * @return array<string, mixed>|null
     */
    public function currentDiscipline(): ?array
    {
        $disciplineId = $this->route('disciplineId');

        return $disciplineId ? $this->draft()->disciplinaPorIdOrFail((string) $disciplineId) : null;
    }

    /**
     * Retorna o código da disciplina requerida normalizado.
     *
     * O valor é convertido para maiúsculas e espaços em branco
     * nas extremidades são removidos.
     *
     * @return string
     */
    public function requiredDisciplineCode(): string
    {
        return Str::upper(trim((string) $this->input('requerida_coddis')));
    }

    public function requiredDisciplineVersion(): ?int
    {
        return null;
    }

    /**
     * Retorna o código da disciplina normalizado.
     *
     * O valor é convertido para maiúsculas e espaços em branco
     * nas extremidades são removidos.
     *
     * @return string
     */
    private function disciplineCode(): string
    {
        return Str::upper(trim((string) $this->input('coddis')));
    }

    private function disciplineVersion(): ?int
    {
        return null;
    }

    /**
     * Verifica se a disciplina informada pertence a uma unidade externa.
     *
     * @return bool
     */
    private function isExternalDiscipline(): bool
    {
        return $this->input('unidade_tipo') === 'OUTRA';
    }

    /**
     * Determina se o envio da ementa é obrigatório.
     *
     * A ementa é exigida quando a disciplina pertence a uma unidade externa
     * e ainda não existe uma ementa cadastrada para a disciplina atual.
     *
     * @return bool
     */
    private function needsSyllabus(): bool
    {
        return $this->isExternalDiscipline() && ! isset($this->currentDiscipline()['ementa']);
    }
}
