<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubjectRequest extends FormRequest
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
            'name' => ['required', 'string', 'regex:/^.{3,100}$/'],
            'code' => ['required', 'string', 'regex:/^[A-Z0-9\-]{2,30}$/'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Debes indicar el nombre de la materia.',
            'name.regex' => 'El nombre debe tener entre 3 y 100 caracteres.',
            'code.required' => 'Debes indicar el código de la materia.',
            'code.regex' => 'El código solo puede contener letras mayúsculas, números y guiones (2-30 caracteres).',
        ];
    }
}
