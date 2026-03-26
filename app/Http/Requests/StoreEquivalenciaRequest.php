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

            'verdis' => 'nullable|integer|min:0|max:127',
            'codcur' => 'nullable|integer|min:0',
            'codhab' => 'nullable|integer|min:0|max:32767',
        ];
    }
}
