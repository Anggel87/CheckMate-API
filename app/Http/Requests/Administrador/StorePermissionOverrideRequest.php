<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionOverrideRequest extends FormRequest
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
            'permission_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:PERMITIR,DENEGAR'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permission_id.required' => 'Debes indicar el permiso a modificar.',
            'type.required' => 'Debes indicar si el permiso se otorga o se revoca.',
            'type.in' => 'El tipo de regla debe ser PERMITIR o DENEGAR.',
        ];
    }
}
