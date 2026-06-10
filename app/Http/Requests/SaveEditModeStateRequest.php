<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveEditModeStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('svgrad') ?? false;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }
}
