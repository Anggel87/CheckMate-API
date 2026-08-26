<?php

namespace App\Http\Requests\Director;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
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
            'type' => ['required', Rule::in(['FIRE', 'GAS', 'EARTHQUAKE', 'OTHER'])],
            'title' => ['nullable', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'severity' => ['nullable', Rule::in(['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])],
            'responsible_user_id' => ['required', 'integer', 'exists:users,id'],
            'group_ids' => ['nullable', 'array'],
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
            'type.required' => 'Debes indicar el tipo de incidente.',
            'type.in' => 'El tipo de incidente indicado no es válido.',
            'title.min' => 'El título debe tener al menos 3 caracteres.',
            'title.max' => 'El título no puede superar los 120 caracteres.',
            'description.max' => 'La descripción no puede superar los 500 caracteres.',
            'severity.in' => 'La severidad indicada no es válida.',
            'responsible_user_id.required' => 'Debes indicar quién será responsable de pasar la lista.',
            'responsible_user_id.exists' => 'El profesor o tutor indicado no existe.',
            'group_ids.*.exists' => 'Uno de los grupos indicados no existe.',
            'evidence.file' => 'La evidencia debe ser un archivo válido.',
        ];
    }
}
