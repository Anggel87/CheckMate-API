<?php

namespace App\Http\Resources;

use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminStudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'second_name' => $this->second_name,
            'first_surname' => $this->first_surname,
            'second_surname' => $this->second_surname,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'active' => $this->active,
            'photo_url' => $this->photo ? Storage::url($this->photo) : null,
            'group_id' => $this->group_id,
            'tutors' => $this->whenLoaded('tutors', fn () => $this->tutors->map(fn (Tutor $tutor) => [
                'id' => $tutor->id,
                'full_name' => $tutor->fullName(),
                'phone' => $tutor->phone,
                'relationship' => $tutor->pivot->relationship,
                'is_primary' => (bool) $tutor->pivot->is_primary,
                'receives_notifications' => (bool) $tutor->pivot->receives_notifications,
            ])),
            'temporary_password' => $this->when(isset($this->temporary_password), $this->temporary_password),
        ];
    }
}
