<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class StoreGroupRequest extends FormRequest
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
            'school_year_id' => ['required', 'integer'],
            'career_id' => ['required', 'integer'],
            'grade' => ['required', 'string', 'regex:/^[A-Za-z0-9]{1,5}$/'],
            'section' => ['required', 'string', 'regex:/^[A-Za-z0-9]{1,5}$/'],
            'shift' => ['sometimes', 'nullable', 'string', 'in:MATUTINO,VESPERTINO,INGENIERIA'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'school_year_id.required' => 'Debes indicar el ciclo escolar.',
            'career_id.required' => 'Debes indicar la carrera.',
            'grade.required' => 'Debes indicar el grado.',
            'grade.regex' => 'El grado no tiene un formato válido.',
            'section.required' => 'Debes indicar la sección.',
            'section.regex' => 'La sección no tiene un formato válido.',
            'shift.in' => 'El turno indicado no es válido.',
        ];
    }
}
