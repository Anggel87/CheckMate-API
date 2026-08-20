<?php

namespace App\Http\Requests\Administrador;

use App\Support\DayOfWeek;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
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
            'group_id' => ['sometimes', 'integer'],
            'subject_id' => ['sometimes', 'integer'],
            'teacher_id' => ['sometimes', 'integer'],
            'classroom_id' => ['sometimes', 'integer'],
            'day_of_week' => ['sometimes', Rule::in(DayOfWeek::all())],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'day_of_week.in' => 'El día indicado no es válido.',
            'start_time.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'end_time.date_format' => 'La hora de fin debe tener el formato HH:MM.',
        ];
    }
}
