<?php

namespace App\Http\Requests;

use App\Models\Disciplina;
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
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $codcur = (int) $this->route('codcur');
        $codhab = (int) $this->route('codhab');

        return [
            'coddis' => [
                // Valida se a disciplina já existe
                function (string $attribute, mixed $value, \Closure $fail) use ($codcur, $codhab) {
                    $jaExisteNoContexto = Disciplina::query()
                        ->where('coddis', (string) $value)
                        ->whereHas('equivalenciasComoRequerida', function ($query) use ($codcur, $codhab) {
                            $query->automaticas()->doContexto($codcur, $codhab);
                        })
                        ->exists();

                    if ($jaExisteNoContexto) {
                        $fail('A disciplina requerida informada já está cadastrada para este curso/habilitação.');
                    }
                },
            ],
        ];
    }
}
