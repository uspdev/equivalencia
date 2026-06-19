<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\Disciplina;
use Illuminate\Foundation\Http\FormRequest;

class StoreEquivalenciaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'coddis' => ['required', 'string', 'max:7'],
            'verdis' => ['nullable', 'integer', 'min:1', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($validator->errors()->has('coddis') || $validator->errors()->has('verdis')) {
                    return;
                }

                $codcur = (int) $this->route('codcur');
                $codhab = (int) $this->route('codhab');
                $disciplina = Disciplina::disciplinaUspNoReplicado(
                    (string) $this->input('coddis'),
                    $this->filled('verdis') ? (int) $this->input('verdis') : null
                );

                if (! $disciplina || ! isset($disciplina['verdis'])) {
                    $validator->errors()->add('coddis', 'Selecione uma disciplina USP válida.');

                    return;
                }

                if (Disciplina::existeComoRequeridaNoContexto(
                    (string) $disciplina['coddis'],
                    (int) $disciplina['verdis'],
                    $codcur,
                    $codhab
                )) {
                    $validator->errors()->add(
                        'coddis',
                        'A disciplina requerida informada já está cadastrada para este curso/habilitação nesta versão.'
                    );
                }
            },
        ];
    }
}
