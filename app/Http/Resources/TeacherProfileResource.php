<?php

namespace App\Http\Resources;

use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class TeacherProfileResource extends JsonResource
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
            'address' => $this->address,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'photo_url' => $this->photo ? Storage::disk('public')->url($this->photo) : null,
            'role' => $this->whenLoaded('role', fn () => $this->role->name),
            'tutored_groups' => $this->when(
                $this->relationLoaded('academicTutor') && $this->academicTutor?->relationLoaded('activeGroups'),
                fn () => $this->academicTutor->activeGroups->map(fn (Group $group) => [
                    'id' => $group->id,
                    'grade' => $group->grade,
                    'section' => $group->section,
                ])->values()
            ),
        ];
    }
}
