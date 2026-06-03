<?php

namespace App\Http\Requests;

use App\Models\Disciplina;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

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
     */
    public function rules(): array
    {
        $codcur = (int) $this->route('codcur');
        $codhab = (int) $this->route('codhab');

        return [
            // Valida se a disciplina já existe
            'coddis' => [
                function (string $attribute, mixed $value, Closure $fail) use ($codcur, $codhab) {
                    if (Disciplina::existeComoRequeridaNoContexto($value, $codcur, $codhab)) {
                        $fail('A disciplina requerida informada já está cadastrada para este curso/habilitação.');
                    }
                },
            ],
        ];
    }
}
