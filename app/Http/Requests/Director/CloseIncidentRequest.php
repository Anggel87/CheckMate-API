<?php

namespace App\Http\Requests\Director;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CloseIncidentRequest extends FormRequest
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
            'resolution' => ['required', Rule::in(['RESOLVED', 'CANCELLED'])],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resolution.required' => 'Debes indicar la resolución del incidente.',
            'resolution.in' => 'La resolución indicada no es válida.',
            'notes.max' => 'Las notas no pueden superar los 255 caracteres.',
        ];
    }
}
