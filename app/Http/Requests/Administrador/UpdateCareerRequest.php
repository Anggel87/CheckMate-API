<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCareerRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'regex:/^.{3,150}$/'],
            'short_name' => ['sometimes', 'nullable', 'string', 'max:20'],
            'code' => ['sometimes', 'string', 'regex:/^[A-Z0-9\-]{2,30}$/'],
            'director_id' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'El nombre debe tener entre 3 y 150 caracteres.',
            'code.regex' => 'El código solo puede contener letras mayúsculas, números y guiones (2-30 caracteres).',
        ];
    }
}
