<?php

namespace App\Http\Requests\Device;

use Illuminate\Foundation\Http\FormRequest;

class NfcTapRequest extends FormRequest
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
            'mac_address' => ['required', 'string', 'regex:/^([0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/'],
            'nfc_uid' => ['required', 'string', 'regex:/^[A-Fa-f0-9:\- ]{1,100}$/'],
            'scanned_at' => ['sometimes', 'date_format:Y-m-d\TH:i:s'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mac_address.required' => 'Debes indicar la dirección MAC del dispositivo.',
            'mac_address.regex' => 'La dirección MAC no tiene un formato válido.',
            'nfc_uid.required' => 'Debes indicar el UID de la tarjeta NFC.',
            'nfc_uid.regex' => 'El UID de la tarjeta NFC no tiene un formato válido.',
            'scanned_at.date_format' => 'El momento del registro debe tener el formato AAAA-MM-DDTHH:MM:SS.',
        ];
    }
}
