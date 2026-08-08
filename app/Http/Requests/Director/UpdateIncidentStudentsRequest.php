<?php

namespace App\Http\Requests\Director;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentStudentsRequest extends FormRequest
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
            'students' => ['required', 'array', 'min:1'],
            'students.*.student_id' => ['required', 'integer', 'exists:users,id'],
            'students.*.status' => ['required', Rule::in(['DESCONOCIDO', 'PRESENTE', 'EXTRAVIADO', 'AUSENTE', 'SEGURO'])],
            'students.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'students.required' => 'Debes indicar al menos un alumno.',
            'students.*.student_id.required' => 'Debes indicar el alumno.',
            'students.*.student_id.exists' => 'Uno de los alumnos indicados no existe.',
            'students.*.status.required' => 'Debes indicar el estatus del alumno.',
            'students.*.status.in' => 'El estatus indicado no es válido.',
            'students.*.notes.max' => 'Las notas no pueden superar los 255 caracteres.',
        ];
    }
}
