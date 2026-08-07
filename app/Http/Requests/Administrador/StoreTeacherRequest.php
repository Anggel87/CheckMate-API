<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'regex:/^[A-Za-zÀ-ÿ\s]{2,45}$/'],
            'second_name' => ['sometimes', 'nullable', 'string', 'max:45'],
            'first_surname' => ['required', 'string', 'max:45'],
            'second_surname' => ['required', 'string', 'max:45'],
            'email' => ['required', 'email', 'max:155'],
            'phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:M,F,OTRO'],
            'photo' => ['sometimes', 'nullable', 'file'],
            'is_academic_tutor' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Debes indicar el nombre del profesor.',
            'first_surname.required' => 'Debes indicar el apellido paterno del profesor.',
            'second_surname.required' => 'Debes indicar el apellido materno del profesor.',
            'email.required' => 'Debes indicar el correo del profesor.',
            'email.email' => 'El correo no tiene un formato válido.',
            'phone.regex' => 'El teléfono debe tener 10 dígitos.',
            'birth_date.required' => 'Debes indicar la fecha de nacimiento.',
            'gender.in' => 'El género indicado no es válido.',
        ];
    }
}
