<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquivalenciaFilhaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coddis' => 'required|string|max:7',
            'nome_disciplina' => 'nullable|string|max:240',
            'creditos' => 'nullable|integer|min:0|max:20',
            'carga_horaria' => 'nullable|integer|min:0|max:1000',
            'nomcur' => 'nullable|string|max:100',
            'ies' => 'nullable|string|max:255',
            'ano' => 'nullable|integer|min:1900|max:'.date('Y'),
            'semestre' => 'nullable|integer|in:1,2',
            'frequencia' => 'nullable|numeric|min:0|max:100',
            'nota' => 'nullable|numeric|min:0|max:10',
            'tipo' => 'nullable|in:c,r',
            'pdf_path' => 'nullable|string|max:255',
        ];
    }
}
