<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class ResendNotificationRequest extends FormRequest
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
            'target' => ['sometimes', 'string', 'in:STUDENT,TUTOR,GROUP,CAREER,ALL'],
            'student_ids' => ['required_if:target,STUDENT', 'required_if:target,TUTOR', 'array'],
            'student_ids.*' => ['integer'],
            'group_ids' => ['required_if:target,GROUP', 'array'],
            'group_ids.*' => ['integer'],
            'career_ids' => ['required_if:target,CAREER', 'array'],
            'career_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target.in' => 'El destino indicado no es válido.',
            'student_ids.required_if' => 'Debes indicar al menos un alumno.',
            'group_ids.required_if' => 'Debes indicar al menos un grupo.',
            'career_ids.required_if' => 'Debes indicar al menos una carrera.',
        ];
    }
}
