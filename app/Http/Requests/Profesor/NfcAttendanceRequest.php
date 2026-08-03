<?php

namespace App\Http\Requests\Profesor;

use Illuminate\Foundation\Http\FormRequest;

class NfcAttendanceRequest extends FormRequest
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
            'nfc_uid' => ['required', 'string', 'regex:/^[A-Fa-f0-9:\- ]{1,100}$/'],
            'scanned_at' => ['required', 'date_format:Y-m-d\TH:i:s'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nfc_uid.required' => 'Debes indicar el UID de la tarjeta NFC.',
            'nfc_uid.regex' => 'El UID de la tarjeta NFC no tiene un formato válido.',
            'scanned_at.required' => 'Debes indicar el momento del registro.',
            'scanned_at.date_format' => 'El momento del registro debe tener el formato AAAA-MM-DDTHH:MM:SS.',
        ];
    }
}
