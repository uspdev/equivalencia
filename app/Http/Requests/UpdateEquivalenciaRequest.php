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

        return [
            'coddis' => [
                'sometimes',
                'required',
                'string',
                'max:7',
                Rule::unique('equivalencias')
                    ->whereNull('equivalencias_id')
                    ->ignore($equivalenciaId),
            ],

            'verdis' => 'sometimes|nullable|integer|min:0|max:127',
            'codcur' => 'sometimes|nullable|integer|min:0',
            'codhab' => 'sometimes|nullable|integer|min:0|max:32767',
        ];
    }
}
