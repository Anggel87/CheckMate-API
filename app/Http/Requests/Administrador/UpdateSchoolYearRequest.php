<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolYearRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'status' => ['sometimes', 'string', 'in:PROXIMO,ACTIVO,FINALIZADO'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'El nombre debe tener el formato AAAA-AAAA (ej. 2026-2027).',
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
            'status.in' => 'El estado indicado no es válido.',
        ];
    }
}
