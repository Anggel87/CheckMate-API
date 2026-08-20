<?php

namespace App\Http\Requests\Alumno;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJustificationRequest extends FormRequest
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
            'reason' => ['required', 'string', 'min:5', 'max:300'],
            'evidence' => ['nullable', 'file'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Debes indicar el motivo de la justificación.',
            'reason.min' => 'El motivo debe tener al menos 5 caracteres.',
            'reason.max' => 'El motivo no puede superar los 300 caracteres.',
            'evidence.file' => 'La evidencia debe ser un archivo válido.',
        ];
    }
}
