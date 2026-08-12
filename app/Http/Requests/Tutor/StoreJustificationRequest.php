<?php

namespace App\Http\Requests\Tutor;

use Illuminate\Foundation\Http\FormRequest;

class StoreJustificationRequest extends FormRequest
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
            'attendance_id' => ['required', 'integer'],
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
            'attendance_id.required' => 'Debes indicar la asistencia a justificar.',
            'reason.required' => 'Debes indicar el motivo de la justificación.',
            'reason.min' => 'El motivo debe tener al menos 5 caracteres.',
            'reason.max' => 'El motivo no puede superar los 300 caracteres.',
            'evidence.file' => 'La evidencia debe ser un archivo válido.',
        ];
    }
}
