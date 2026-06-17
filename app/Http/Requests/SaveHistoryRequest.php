<?php

namespace App\Http\Requests;

use App\Models\AproveitamentoRascunho;
use Illuminate\Foundation\Http\FormRequest;

class SaveHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'historico' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'historico.required' => 'Envie o histórico escolar do requerimento.',
            'historico.mimes' => 'O histórico escolar deve ser um arquivo PDF.',
            'historico.max' => 'O histórico escolar pode ter no máximo 10 MB.',
        ];
    }

    public function draft(): AproveitamentoRascunho
    {
        return AproveitamentoRascunho::atualDoUsuario((int) $this->user()->id);
    }
}
