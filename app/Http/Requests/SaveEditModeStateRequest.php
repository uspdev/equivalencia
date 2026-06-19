<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use Illuminate\Foundation\Http\FormRequest;

class SaveEditModeStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::APROVEITAMENTOS_AUTOMATICOS_MANAGE->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
        ];
    }
}
