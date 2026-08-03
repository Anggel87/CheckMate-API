<?php

namespace App\Http\Requests\Tutor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewJustificationRequest extends FormRequest
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
            'status' => ['required', Rule::in(['ACEPTADO', 'RECHAZADO'])],
            'comment' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Debes indicar si el justificante se aprueba o se rechaza.',
            'status.in' => 'El estado indicado no es válido.',
            'comment.max' => 'El comentario no puede superar los 300 caracteres.',
        ];
    }
}
