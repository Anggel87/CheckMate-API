<?php

namespace App\Http\Resources;

use App\Models\Tutor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin User */
class StudentProfileResource extends JsonResource
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
            'group' => $this->whenLoaded('group', fn () => [
                'id' => $this->group->id,
                'grade' => $this->group->grade,
                'section' => $this->group->section,
            ]),
            'career' => $this->whenLoaded('group', fn () => $this->group->relationLoaded('career') ? [
                'id' => $this->group->career->id,
                'name' => $this->group->career->name,
            ] : null),
            'tutors' => $this->whenLoaded('tutors', fn () => $this->tutors->map(fn (Tutor $tutor) => [
                'id' => $tutor->id,
                'full_name' => $tutor->fullName(),
                'phone' => $tutor->phone,
                'relationship' => $tutor->pivot->relationship,
                'is_primary' => (bool) $tutor->pivot->is_primary,
            ])),
            'academic_tutor' => $this->whenLoaded('group', function () {
                if (! $this->group?->relationLoaded('academicTutors')) {
                    return null;
                }

                $academicTutor = $this->group->academicTutors
                    ->firstWhere('pivot.is_active', true) ?? $this->group->academicTutors->first();

                if ($academicTutor === null || ! $academicTutor->relationLoaded('user')) {
                    return null;
                }

                return [
                    'id' => $academicTutor->user->id,
                    'full_name' => $academicTutor->user->fullName(),
                    'phone' => $academicTutor->user->phone,
                ];
            }),
        ];
    }
}
