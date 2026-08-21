<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class SetNfcUidRequest extends FormRequest
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
            'nfc_uid' => ['required', 'string', 'regex:/^[A-Za-z0-9:\- ]{1,100}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nfc_uid.required' => 'El UID de la tarjeta NFC es obligatorio.',
            'nfc_uid.regex' => 'El UID solo puede contener letras, números, espacios, ":" y "-".',
        ];
    }
}
