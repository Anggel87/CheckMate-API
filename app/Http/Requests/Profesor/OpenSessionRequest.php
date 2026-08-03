<?php

namespace App\Http\Requests\Profesor;

use Illuminate\Foundation\Http\FormRequest;

class OpenSessionRequest extends FormRequest
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
            'schedule_id' => ['required', 'integer', 'min:1', 'exists:schedules,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'schedule_id.required' => 'Debes indicar el horario de la clase.',
            'schedule_id.exists' => 'El horario indicado no existe.',
            'date.required' => 'Debes indicar la fecha de la sesión.',
            'date.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
        ];
    }
}
