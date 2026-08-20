<?php

namespace App\Http\Requests\Administrador;

use App\Support\DayOfWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
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
            'group_id' => ['required', 'integer'],
            'subject_id' => ['required', 'integer'],
            'teacher_id' => ['required', 'integer'],
            'classroom_id' => ['required', 'integer'],
            'day_of_week' => ['required', Rule::in(DayOfWeek::all())],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'school_year_id.required' => 'Debes indicar el ciclo escolar.',
            'group_id.required' => 'Debes indicar el grupo.',
            'subject_id.required' => 'Debes indicar la materia.',
            'teacher_id.required' => 'Debes indicar el profesor.',
            'classroom_id.required' => 'Debes indicar el salón.',
            'day_of_week.required' => 'Debes indicar el día de la semana.',
            'day_of_week.in' => 'El día indicado no es válido.',
            'start_time.required' => 'Debes indicar la hora de inicio.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'end_time.required' => 'Debes indicar la hora de fin.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:MM.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }
}
