<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'role' => ['required', 'string', 'in:alumno,profesor,tutor_academico,director_carrera,administrador'],
            'first_name' => ['required', 'string', 'regex:/^[A-Za-zÀ-ÿ\s]{2,45}$/'],
            'second_name' => ['sometimes', 'nullable', 'string', 'max:45'],
            'first_surname' => ['required', 'string', 'max:45'],
            'second_surname' => ['required', 'string', 'max:45'],
            'email' => ['required', 'email', 'max:155'],
            'phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', 'string', 'in:M,F,OTRO'],
            'photo' => ['sometimes', 'nullable', 'file'],
            'group_id' => ['required_if:role,alumno', 'integer'],
            'nfc_uid' => ['sometimes', 'nullable', 'string', 'regex:/^[A-Za-z0-9:\- ]{1,100}$/'],
            'tutors' => ['sometimes', 'array'],
            'tutors.*.first_name' => ['required', 'string', 'max:45'],
            'tutors.*.second_name' => ['sometimes', 'nullable', 'string', 'max:45'],
            'tutors.*.first_surname' => ['required', 'string', 'max:45'],
            'tutors.*.second_surname' => ['required', 'string', 'max:45'],
            'tutors.*.phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'tutors.*.relationship' => ['required', 'string', 'max:50'],
            'tutors.*.is_primary' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.required' => 'Debes indicar el rol del usuario.',
            'role.in' => 'El rol indicado no es válido.',
            'first_name.required' => 'Debes indicar el nombre.',
            'first_surname.required' => 'Debes indicar el apellido paterno.',
            'second_surname.required' => 'Debes indicar el apellido materno.',
            'email.required' => 'Debes indicar el correo.',
            'email.email' => 'El correo no tiene un formato válido.',
            'phone.regex' => 'El teléfono debe tener 10 dígitos.',
            'birth_date.required' => 'Debes indicar la fecha de nacimiento.',
            'gender.in' => 'El género indicado no es válido.',
            'group_id.required_if' => 'Debes indicar el grupo del alumno.',
            'nfc_uid.regex' => 'El UID solo puede contener letras, números, espacios, ":" y "-".',
            'tutors.*.first_name.required' => 'Debes indicar el nombre del tutor.',
            'tutors.*.first_surname.required' => 'Debes indicar el apellido paterno del tutor.',
            'tutors.*.second_surname.required' => 'Debes indicar el apellido materno del tutor.',
            'tutors.*.phone.regex' => 'El teléfono del tutor debe tener 10 dígitos.',
            'tutors.*.relationship.required' => 'Debes indicar la relación del tutor con el alumno.',
        ];
    }
}
