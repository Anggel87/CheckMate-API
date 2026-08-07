<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
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
            'ip' => ['sometimes', 'nullable', 'ip'],
            'classroom_id' => ['required', 'integer'],
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
            'ip.ip' => 'La dirección IP no tiene un formato válido.',
            'classroom_id.required' => 'Debes indicar el salón del dispositivo.',
        ];
    }
}
