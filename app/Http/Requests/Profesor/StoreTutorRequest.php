<?php

namespace App\Http\Requests\Profesor;

use Illuminate\Foundation\Http\FormRequest;

class StoreTutorRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:45'],
            'second_name' => ['sometimes', 'nullable', 'string', 'max:45'],
            'first_surname' => ['required', 'string', 'max:45'],
            'second_surname' => ['required', 'string', 'max:45'],
            'phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'relationship' => ['required', 'string', 'max:50'],
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
            'first_name.required' => 'Debes indicar el nombre del tutor.',
            'first_surname.required' => 'Debes indicar el apellido paterno del tutor.',
            'second_surname.required' => 'Debes indicar el apellido materno del tutor.',
            'phone.regex' => 'El teléfono debe tener 10 dígitos.',
            'relationship.required' => 'Debes indicar la relación del tutor con el alumno.',
        ];
    }
}
