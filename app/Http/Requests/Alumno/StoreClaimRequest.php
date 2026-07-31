<?php

namespace App\Http\Requests\Alumno;

use Illuminate\Foundation\Http\FormRequest;

class StoreClaimRequest extends FormRequest
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
            'subject_id' => ['required', 'integer', 'min:1', 'exists:subjects,id'],
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
            'subject_id.required' => 'Debes indicar la materia del reclamo.',
            'subject_id.exists' => 'La materia indicada no existe.',
            'description.required' => 'Debes describir el reclamo.',
            'description.min' => 'La descripción debe tener al menos 10 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
            'evidence.file' => 'La evidencia debe ser un archivo válido.',
        ];
    }
}
