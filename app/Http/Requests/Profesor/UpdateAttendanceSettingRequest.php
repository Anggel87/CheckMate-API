<?php

namespace App\Http\Requests\Profesor;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceSettingRequest extends FormRequest
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
            'present_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:255'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:255', 'gt:present_tolerance_minutes'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'present_tolerance_minutes.required' => 'Debes indicar la tolerancia de asistencia a tiempo.',
            'late_tolerance_minutes.required' => 'Debes indicar la tolerancia de retardo.',
            'late_tolerance_minutes.gt' => 'La tolerancia de retardo debe ser mayor a la de asistencia a tiempo.',
        ];
    }
}
