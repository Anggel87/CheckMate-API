<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTutorRequest extends FormRequest
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
            'first_name' => ['sometimes', 'string', 'max:45'],
            'second_name' => ['sometimes', 'nullable', 'string', 'max:45'],
            'first_surname' => ['sometimes', 'string', 'max:45'],
            'second_surname' => ['sometimes', 'string', 'max:45'],
            'phone' => ['sometimes', 'string', 'regex:/^\d{10}$/'],
            'relationship' => ['sometimes', 'string', 'max:50'],
            'is_primary' => ['sometimes', 'boolean'],
            'receives_notifications' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'El teléfono debe tener 10 dígitos.',
        ];
    }
}
