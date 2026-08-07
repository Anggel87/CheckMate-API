<?php

namespace App\Http\Requests\Administrador;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicTutorRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
            'group_ids' => ['sometimes', 'array'],
            'group_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'is_active.required' => 'Debes indicar si el profesor es tutor académico.',
        ];
    }
}
