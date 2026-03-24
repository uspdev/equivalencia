<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquivalenciaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'coddis' => [
                'required',
                'string',
                'max:7',
                Rule::unique('equivalencias')->whereNull('equivalencias_id'),
            ],

            'nome_disciplina' => 'nullable|string|max:240',

            'verdis' => 'nullable|integer|min:0|max:127',

            'codcur' => 'nullable|integer|min:0',

            'codhab' => 'nullable|integer|min:0|max:32767',

            'creditos' => 'nullable|integer|min:0|max:20',

            'carga_horaria' => 'nullable|integer|min:0|max:1000',

            'tipo' => 'nullable|in:c,r',

            'ano' => 'nullable|integer|min:1900|max:'.date('Y'),

            'semestre' => 'nullable|integer|in:1,2',

            'nota' => 'nullable|numeric|min:0|max:10',

            'frequencia' => 'nullable|numeric|min:0|max:100',

            'ies' => 'nullable|string|max:255',

            'pdf_path' => 'nullable|string|max:255',

            // store cria apenas disciplina USP (pai)
            'equivalencias_id' => 'prohibited',
        ];
    }
}
