<?php

namespace App\Http\Requests\Profesor;

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
            // El profesor solo puede marcar presente/ausente (pase de lista normal) o a salvo
            // (por si el alumno no tiene celular para reportarse el mismo). DESCONOCIDO y
            // EXTRAVIADO quedan reservados para director/administrador.
            'students.*.status' => ['required', Rule::in(['PRESENTE', 'AUSENTE', 'SEGURO'])],
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
            'students.*.status.required' => 'Debes indicar el estatus del alumno.',
            'students.*.status.in' => 'El estatus indicado no es válido.',
            'comment.max' => 'El comentario no puede superar los 300 caracteres.',
        ];
    }
}
