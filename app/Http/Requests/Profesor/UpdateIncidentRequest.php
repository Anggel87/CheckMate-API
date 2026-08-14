<?php

namespace App\Http\Requests\Profesor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El titulo y la severidad pueden dejarse en blanco al editar (igual que al crear);
     * se normalizan a null en vez de fallar la validacion de longitud minima.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('title')) {
            $this->merge(['title' => $this->filled('title') ? $this->input('title') : null]);
        }

        if ($this->has('severity')) {
            $this->merge(['severity' => $this->filled('severity') ? $this->input('severity') : null]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(['FIRE', 'GAS', 'EARTHQUAKE', 'OTHER'])],
            'title' => ['sometimes', 'nullable', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'severity' => ['sometimes', 'nullable', Rule::in(['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])],
            'group_ids' => ['sometimes', 'array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
            'evidence' => ['nullable', 'file'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'El tipo de incidente indicado no es válido.',
            'title.min' => 'El título debe tener al menos 3 caracteres.',
            'title.max' => 'El título no puede superar los 120 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
            'severity.in' => 'La severidad indicada no es válida.',
            'group_ids.*.exists' => 'Uno de los grupos indicados no existe.',
            'evidence.file' => 'La evidencia debe ser un archivo válido.',
        ];
    }
}
