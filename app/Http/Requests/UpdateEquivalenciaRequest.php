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
                'string',
                'max:7',
                Rule::unique('equivalencias')
                    ->whereNull('equivalencias_id')
                    ->ignore($equivalenciaId),
            ],

            'nome_disciplina' => 'sometimes|nullable|string|max:240',

            'verdis' => 'sometimes|nullable|integer|min:0|max:127',

            'codcur' => 'sometimes|nullable|integer|min:0',

            'codhab' => 'sometimes|nullable|integer|min:0|max:32767',

            'creditos' => 'sometimes|nullable|integer|min:0|max:20',

            'carga_horaria' => 'sometimes|nullable|integer|min:0|max:1000',

            'nomcur' => 'sometimes|nullable|string|max:100',

            'ano' => 'sometimes|nullable|integer|min:1900|max:'.date('Y'),

            'semestre' => 'sometimes|nullable|integer|in:1,2',

            'nota' => 'sometimes|nullable|numeric|min:0|max:10',

            'frequencia' => 'sometimes|nullable|numeric|min:0|max:100',

            'ies' => 'sometimes|nullable|string|max:255',

            'tipo' => 'nullable|in:c,r',

            'equivalencias_id' => [
                'nullable',
                'exists:equivalencias,id',
                function ($attribute, $value, $fail) {
                    $routeEquivalencia = $this->route('equivalencia');
                    $id = $routeEquivalencia instanceof Equivalencia
                        ? $routeEquivalencia->id
                        : $routeEquivalencia;

                    if ($value == $id) {
                        $fail('Não pode referenciar a si mesmo.');

                        return;
                    }

                    // Evitar loop (A -> B -> A)
                    $parent = Equivalencia::find($value);

                    while ($parent) {
                        if ($parent->id == $id) {
                            $fail('Loop de equivalência detectado.');

                            return;
                        }
                        $parent = $parent->parent;
                    }
                },
            ],

            'pdf_path' => 'sometimes|nullable|string|max:255',
        ];
    }
}
