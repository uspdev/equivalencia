<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquivalenciaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'coddis' => 'required|string|max:7',

            'nome_disciplina' => 'nullable|string|max:240',

            'creditos' => 'nullable|integer|min:0|max:20',

            'carga_horaria' => 'nullable|integer|min:0|max:1000',

            'tipo' => 'nullable|in:c,r',

            'ano' => 'nullable|integer|min:1900|max:'.date('Y'),

            'semestre' => 'nullable|integer|in:1,2',

            'nota' => 'nullable|numeric|min:0|max:10',

            'frequencia' => 'nullable|numeric|min:0|max:100',

            'equivalencias_id' => [
                'nullable',
                'exists:equivalencias,id',
                function ($attribute, $value, $fail) {
                    if ($value == $this->id) {
                        $fail('Uma equivalência não pode referenciar a si mesma.');
                    }
                },
            ],
        ];
    }
}
