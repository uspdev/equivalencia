<?php

namespace App\Http\Requests;

use App\Replicado\Graduacao;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class SaveRequiredDisciplineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requerida_coddis' => ['bail', 'required', 'string', 'regex:/^[A-Za-z0-9]{3,7}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'requerida_coddis.required' => 'Selecione a disciplina para a qual deseja equivalência.',
            'requerida_coddis.regex' => 'Selecione uma disciplina USP válida.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('requerida_coddis')) {
                    return;
                }

                if (! app(Graduacao::class)->existeDisciplinaAtivaPorCodigo($this->requiredDisciplineCode())) {
                    $validator->errors()->add(
                        'requerida_coddis',
                        'A disciplina USP selecionada não foi encontrada.'
                    );
                }
            },
        ];
    }

    public function requiredDisciplineCode(): string
    {
        return Str::upper(trim((string) $this->input('requerida_coddis')));
    }
}
