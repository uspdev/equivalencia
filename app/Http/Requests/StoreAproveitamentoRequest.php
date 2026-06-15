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

        $rules = [
            'historico_adicional' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];

        foreach ($this->draft()->gruposDeHistorico() as $group) {
            $rules["historicos.{$group['key']}"] = ['required', 'file', 'mimes:pdf', 'max:10240'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'historicos.*.required' => 'Envie um histórico escolar para cada unidade externa.',
            'historicos.*.mimes' => 'O histórico escolar deve ser um arquivo PDF.',
            'historicos.*.max' => 'Cada histórico escolar pode ter no máximo 10 MB.',
            'historico_adicional.mimes' => 'O histórico escolar adicional deve ser um arquivo PDF.',
            'historico_adicional.max' => 'O histórico escolar adicional pode ter no máximo 10 MB.',
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
