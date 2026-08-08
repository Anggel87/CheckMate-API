<?php

namespace App\Http\Requests\Director;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClaimActionRequest extends FormRequest
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
            'action' => ['required', Rule::in(['EN_PROCESO', 'CONTACTADO', 'ACEPTADO', 'RECHAZADO'])],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Debes indicar la acción a realizar.',
            'action.in' => 'La acción indicada no es válida.',
            'comment.max' => 'El comentario no puede superar los 500 caracteres.',
        ];
    }
}
