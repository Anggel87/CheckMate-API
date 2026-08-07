<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
            'group_id' => ['required', 'integer'],
            'photo' => ['sometimes', 'nullable', 'file'],
            'tutor_first_name' => ['required', 'string', 'max:45'],
            'tutor_first_surname' => ['required', 'string', 'max:45'],
            'tutor_second_surname' => ['sometimes', 'nullable', 'string', 'max:45'],
            'tutor_phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'tutor_relationship' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Debes indicar el nombre del alumno.',
            'first_surname.required' => 'Debes indicar el apellido paterno del alumno.',
            'second_surname.required' => 'Debes indicar el apellido materno del alumno.',
            'email.required' => 'Debes indicar el correo del alumno.',
            'email.email' => 'El correo no tiene un formato válido.',
            'phone.regex' => 'El teléfono debe tener 10 dígitos.',
            'birth_date.required' => 'Debes indicar la fecha de nacimiento.',
            'gender.in' => 'El género indicado no es válido.',
            'group_id.required' => 'Debes indicar el grupo del alumno.',
            'tutor_first_name.required' => 'Debes indicar el nombre del tutor principal.',
            'tutor_first_surname.required' => 'Debes indicar el apellido paterno del tutor principal.',
            'tutor_phone.regex' => 'El teléfono del tutor debe tener 10 dígitos.',
            'tutor_relationship.required' => 'Debes indicar la relación del tutor con el alumno.',
        ];
    }
}
