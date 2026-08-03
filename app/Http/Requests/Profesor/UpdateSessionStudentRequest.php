<?php

namespace App\Http\Requests\Profesor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionStudentRequest extends FormRequest
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
            'status' => ['required', Rule::in(['PRESENTE', 'RETARDO', 'FALTA'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Debes indicar el nuevo estado de asistencia.',
            'status.in' => 'El estado indicado no es válido.',
        ];
    }
}
