<?php

namespace App\Http\Requests\Profesor;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentNotificationRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:3', 'max:90'],
            'message' => ['required', 'string', 'max:350'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Debes indicar un título.',
            'message.required' => 'Debes indicar un mensaje.',
        ];
    }
}
