<?php

namespace App\Http\Requests\Alumno;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClaimRequest extends FormRequest
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
            'description' => ['required', 'string', 'min:10', 'max:500'],
            'evidence' => ['nullable', 'file'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'Debes describir el reclamo.',
            'description.min' => 'La descripción debe tener al menos 10 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
            'evidence.file' => 'La evidencia debe ser un archivo válido.',
        ];
    }
}
