<?php

namespace App\Http\Requests;

use App\Models\AproveitamentoRascunho;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreAproveitamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (! $this->draft()->requerida_coddis || $this->draft()->disciplinas()->isEmpty()) {
            return [];
        }

        return [
            'historico' => [$this->draft()->temHistorico() ? 'nullable' : 'required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'historico.required' => 'Envie o histórico escolar do requerimento.',
            'historico.mimes' => 'O histórico escolar deve ser um arquivo PDF.',
            'historico.max' => 'O histórico escolar pode ter no máximo 10 MB.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $draft = $this->draft();

                if (! $draft->requerida_coddis) {
                    $validator->errors()->add(
                        'requerida_coddis',
                        'Selecione a disciplina para a qual deseja equivalência.'
                    );
                }

                if ($draft->disciplinas()->isEmpty()) {
                    $validator->errors()->add('disciplinas', 'Adicione ao menos uma disciplina cursada.');
                }
            },
        ];
    }

    public function draft(): AproveitamentoRascunho
    {
        return AproveitamentoRascunho::atualDoUsuario((int) $this->user()->id);
    }
}
