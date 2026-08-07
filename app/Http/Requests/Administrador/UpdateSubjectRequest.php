<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'regex:/^.{3,100}$/'],
            'code' => ['sometimes', 'string', 'regex:/^[A-Z0-9\-]{2,30}$/'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'El nombre debe tener entre 3 y 100 caracteres.',
            'code.regex' => 'El código solo puede contener letras mayúsculas, números y guiones (2-30 caracteres).',
        ];
    }
}
