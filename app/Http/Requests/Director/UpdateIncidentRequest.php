<?php

namespace App\Http\Requests\Director;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
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
            'type' => ['sometimes', Rule::in(['FIRE', 'GAS', 'EARTHQUAKE', 'OTHER'])],
            'title' => ['sometimes', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'severity' => ['sometimes', Rule::in(['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])],
            'evidence' => ['nullable', 'file'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de incidente indicado no es válido.',
            'title.min' => 'El título debe tener al menos 3 caracteres.',
            'title.max' => 'El título no puede superar los 120 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
            'severity.in' => 'La severidad indicada no es válida.',
            'evidence.file' => 'La evidencia debe ser un archivo válido.',
        ];
    }
}
