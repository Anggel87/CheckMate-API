<?php

namespace App\Http\Requests\Profesor;

use Illuminate\Foundation\Http\FormRequest;

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
            'students.*.present' => ['required', 'boolean'],
            'comment' => ['nullable', 'string', 'max:300'],
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
            'students.*.present.required' => 'Debes indicar si el alumno está presente.',
            'comment.max' => 'El comentario no puede superar los 300 caracteres.',
        ];
    }
}
