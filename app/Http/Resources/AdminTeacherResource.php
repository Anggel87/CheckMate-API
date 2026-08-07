<?php

namespace App\Http\Resources;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminTeacherResource extends JsonResource
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
            'is_academic_tutor' => $this->whenLoaded('role', fn () => $this->role->name === 'tutor_academico'),
            'schedules_count' => $this->when(isset($this->schedules_count), $this->schedules_count),
            'tutored_groups' => $this->when(
                $this->relationLoaded('academicTutor') && $this->academicTutor?->relationLoaded('activeGroups'),
                fn () => $this->academicTutor->activeGroups->map(fn (Group $group) => [
                    'id' => $group->id,
                    'grade' => $group->grade,
                    'section' => $group->section,
                ])->values()
            ),
            'temporary_password' => $this->when(isset($this->temporary_password), $this->temporary_password),
        ];
    }
}
