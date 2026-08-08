<?php

namespace App\Http\Requests\Director;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
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
            'type' => ['required', Rule::in(['FIRE', 'GAS', 'EARTHQUAKE', 'OTHER'])],
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'severity' => ['required', Rule::in(['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])],
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:users,id'],
            'evidence' => ['nullable', 'file'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Debes indicar el tipo de incidente.',
            'type.in' => 'El tipo de incidente indicado no es válido.',
            'title.required' => 'Debes indicar un título para el incidente.',
            'title.min' => 'El título debe tener al menos 3 caracteres.',
            'title.max' => 'El título no puede superar los 120 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
            'severity.required' => 'Debes indicar la severidad del incidente.',
            'severity.in' => 'La severidad indicada no es válida.',
            'schedule_id.required' => 'Debes indicar el horario del incidente.',
            'schedule_id.exists' => 'El horario indicado no existe.',
            'student_ids.required' => 'Debes indicar al menos un alumno.',
            'student_ids.*.exists' => 'Uno de los alumnos indicados no existe.',
            'evidence.file' => 'La evidencia debe ser un archivo válido.',
        ];
    }
}
