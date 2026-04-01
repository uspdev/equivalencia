<?php

namespace App\Http\Requests;

use App\Models\Equivalencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquivalenciaRequest extends FormRequest
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
        $equivalencia = $this->route('equivalencia');
        $equivalenciaId = $equivalencia instanceof Equivalencia
            ? $equivalencia->id
            : $equivalencia;
        // Obter os parâmetros da rota
        $codcur = (int) $this->route('codcur');
        $codhab = (int) $this->route('codhab');

        return [
            'coddis' => [
                Rule::unique('equivalencias')
                    ->whereNull('equivalencias_id')
                    ->where('codcur', $codcur)
                    ->where('codhab', $codhab)
                    ->ignore($equivalenciaId),
            ],
        ];
    }
}
