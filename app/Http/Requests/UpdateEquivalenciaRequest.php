<?php

namespace App\Http\Requests;

use App\Models\Equivalencia;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEquivalenciaRequest extends FormRequest
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
            'coddis' => 'sometimes|string|max:7',

            'tipo' => 'nullable|in:c,r',

            'equivalencias_id' => [
                'nullable',
                'exists:equivalencias,id',
                function ($attribute, $value, $fail) {

                    $id = $this->route('equivalencia'); // id da URL

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
        ];
    }
}
