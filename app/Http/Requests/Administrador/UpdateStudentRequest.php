<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentRequest extends FormRequest
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
            'first_name' => ['sometimes', 'string', 'regex:/^[A-Za-zÀ-ÿ\s]{2,45}$/'],
            'second_name' => ['sometimes', 'nullable', 'string', 'max:45'],
            'first_surname' => ['sometimes', 'string', 'max:45'],
            'second_surname' => ['sometimes', 'string', 'max:45'],
            'email' => ['sometimes', 'email', 'max:155'],
            'phone' => ['sometimes', 'string', 'regex:/^\d{10}$/'],
            'birth_date' => ['sometimes', 'date'],
            'gender' => ['sometimes', 'string', 'in:M,F,OTRO'],
            'group_id' => ['sometimes', 'integer'],
            'active' => ['sometimes', 'boolean'],
            'photo' => ['sometimes', 'nullable', 'file'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.email' => 'El correo no tiene un formato válido.',
            'phone.regex' => 'El teléfono debe tener 10 dígitos.',
            'gender.in' => 'El género indicado no es válido.',
        ];
    }
}
