<?php

namespace App\Http\Requests\Director;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:3', 'max:90'],
            'message' => ['required', 'string', 'max:350'],
            'type' => ['required', 'string', 'in:INASISTENCIA,RETARDO,INCIDENTE,JUSTIFICANTE,RECLAMO,AVISO,RECLAMO_PROFESOR'],
            // Sin ALL: el director nunca puede dirigirse a toda la escuela, solo a su
            // propia carrera. CAREER no requiere career_ids: siempre son las carreras
            // que dirige, resueltas en el controlador via CareerScope.
            'target' => ['required', 'string', 'in:STUDENT,TUTOR,GROUP,CAREER,TEACHER'],
            'student_ids' => ['required_if:target,STUDENT', 'required_if:target,TUTOR', 'array'],
            'student_ids.*' => ['integer'],
            'group_ids' => ['required_if:target,GROUP', 'array'],
            'group_ids.*' => ['integer'],
            'teacher_ids' => ['required_if:target,TEACHER', 'array'],
            'teacher_ids.*' => ['integer'],
            'recipient_type' => ['sometimes', 'string', 'in:TUTOR,STUDENT'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Debes indicar un título.',
            'message.required' => 'Debes indicar un mensaje.',
            'type.in' => 'El tipo de notificación indicado no es válido.',
            'target.required' => 'Debes indicar a quién va dirigido el aviso.',
            'target.in' => 'El destino indicado no es válido.',
            'student_ids.required_if' => 'Debes indicar al menos un alumno.',
            'group_ids.required_if' => 'Debes indicar al menos un grupo.',
            'teacher_ids.required_if' => 'Debes indicar al menos un profesor.',
            'recipient_type.in' => 'El tipo de destinatario indicado no es válido.',
        ];
    }
}
