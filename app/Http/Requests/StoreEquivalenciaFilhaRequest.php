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
            'ies' => 'nullable|string|max:255',
        ];
    }
}
