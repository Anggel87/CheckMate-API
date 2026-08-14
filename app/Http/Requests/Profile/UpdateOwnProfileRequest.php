<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Autoedicion de perfil: cualquier rol solo puede tocar su propio telefono y
 * foto. Nombre, correo, direccion y tutor legal solo los edita un administrador.
 */
class UpdateOwnProfileRequest extends FormRequest
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
            'phone' => ['sometimes', 'string', 'regex:/^\d{10}$/'],
            'photo' => ['sometimes', 'nullable', 'file'],
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
