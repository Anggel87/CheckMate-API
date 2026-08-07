<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGroupRequest extends FormRequest
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
            'school_year_id' => ['sometimes', 'integer'],
            'career_id' => ['sometimes', 'integer'],
            'grade' => ['sometimes', 'string', 'regex:/^[A-Za-z0-9]{1,5}$/'],
            'section' => ['sometimes', 'string', 'regex:/^[A-Za-z0-9]{1,5}$/'],
            'shift' => ['sometimes', 'nullable', 'string', 'in:MATUTINO,VESPERTINO,INGENIERIA'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grade.regex' => 'El grado no tiene un formato válido.',
            'section.regex' => 'La sección no tiene un formato válido.',
            'shift.in' => 'El turno indicado no es válido.',
        ];
    }
}
