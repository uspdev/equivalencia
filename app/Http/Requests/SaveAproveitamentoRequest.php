<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveAproveitamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $semestres = [];

        for ($i = 1; $i < 4; $i++) {
            $campo = 'semestre_dis'.$i;

            if (! is_null($this->input($campo))) {
                $semestres[$campo] = (int) $this->input($campo);
            }
        }

        $this->merge($semestres);
    }

    public function rules(): array
    {
        $rules = [
            'coddis4' => ['required', 'string', 'max:20'],
            'disciplina4' => ['required', 'string', 'max:255'],
            'unidade_ies' => ['required', 'string', 'max:255'],
        ];

        for ($i = 1; $i < 4; $i++) {
            $rules["coddis{$i}"] = ['nullable', 'string', 'max:20'];
            $rules["disciplina{$i}"] = ['nullable', "required_with:coddis{$i}", 'string', 'max:255'];
            $rules["credit_dis{$i}"] = ['nullable', "required_with:coddis{$i}", 'integer', 'min:0'];
            $rules["cghr_dis{$i}"] = ['nullable', "required_with:coddis{$i}", 'integer', 'min:0'];
            $rules["ano_dis{$i}"] = ['nullable', "required_with:coddis{$i}", 'integer', 'min:1900', 'max:'.date('Y')];
            $rules["semestre_dis{$i}"] = ['nullable', "required_with:coddis{$i}", 'integer', 'in:1,2'];
            $rules["freq_dis{$i}"] = ['nullable', "required_with:coddis{$i}", 'numeric', 'min:0', 'max:100'];
            $rules["nota_dis{$i}"] = ['nullable', "required_with:coddis{$i}", 'numeric', 'min:0', 'max:10'];
        }

        return $rules;
    }
}
