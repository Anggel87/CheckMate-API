<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceSettingRequest extends FormRequest
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
            'present_tolerance_minutes' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'late_tolerance_minutes' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'allow_manual_attendance' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
